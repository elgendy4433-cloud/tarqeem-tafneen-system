<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\ImportLog;
use App\Models\Iqama;
use App\Models\License;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelImportService
{
    /**
     * تحويل تواريخ الإكسل الرقمية (مثل 46438) أو النصية إلى كائن Carbon
     */
    public static function parseExcelDate(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            // إذا كان الرقم قيمة تسلسلية لإكسل (Excel Serial Number)
            if (is_numeric($value)) {
                $dateTime = ExcelDate::excelToDateTimeObject($value);
                return Carbon::instance($dateTime);
            }

            // إذا كان نصاً منسقاً
            $cleanDate = trim((string) $value);
            return Carbon::parse($cleanDate);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * استيراد ملف الإقامات
     * الأعمدة المتوقعة: الاسم، رقم الإقامة، المهنة، الفرع، تاريخ الانتهاء، ملاحظات، ملاحظات النقل والتجديد
     */
    public function importIqamas(UploadedFile $file, int $organizationId, ?int $userId = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $total = 0;
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        // تخطي صف العناوين الأول
        $isFirst = true;
        foreach ($rows as $rowIndex => $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            // فحص هل الصف فارغ
            if (empty(array_filter($row))) {
                continue;
            }

            $total++;

            // قراءة الحقول بحسب الترتيب الشائع في ملفات الإقامات
            $name = trim($row['A'] ?? '');
            $iqamaNumber = trim($row['B'] ?? '');
            $profession = trim($row['C'] ?? '');
            $branch = trim($row['D'] ?? '');
            $rawExpiry = $row['E'] ?? ($row['F'] ?? null);
            $notes = trim($row['G'] ?? '');
            $transferNotes = trim($row['H'] ?? '');

            if (empty($iqamaNumber)) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex}: رقم الإقامة مفقود.";
                continue;
            }

            $expiryDate = self::parseExcelDate($rawExpiry);
            if (!$expiryDate) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex} (إقامة {$iqamaNumber}): تاريخ الانتهاء غير صالح أو مفقود ({$rawExpiry}).";
                continue;
            }

            // إنشاء الموظف أو تحديثه
            $employee = Employee::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'national_id' => $iqamaNumber,
                ],
                [
                    'name' => $name ?: 'بدون اسم',
                    'profession' => $profession,
                    'branch' => $branch,
                ]
            );

            // Upsert ذكي للإقامة
            $existing = Iqama::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('iqama_number', $iqamaNumber)
                ->first();

            $data = [
                'employee_id' => $employee->id,
                'name' => $name,
                'profession' => $profession,
                'branch' => $branch,
                'expiry_date' => $expiryDate->format('Y-m-d'),
                'notes' => $notes,
                'transfer_renewal_notes' => $transferNotes,
            ];

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Iqama::withoutGlobalScopes()->create(array_merge($data, [
                    'organization_id' => $organizationId,
                    'iqama_number' => $iqamaNumber,
                ]));
                $inserted++;
            }
        }

        $log = ImportLog::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'module_type' => 'iqamas',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $total,
            'inserted_rows' => $inserted,
            'updated_rows' => $updated,
            'failed_rows' => $failed,
            'error_details' => $errors,
        ]);

        return [
            'total' => $total,
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
            'log_id' => $log->id,
        ];
    }

    /**
     * استيراد ملف العقود الوظيفية
     * الأعمدة: الاسم، رقم الهوية، المهنة، تصنيف المهارات، الإدارة، بداية العلاقة التعاقدية، بداية العقد، نهاية العقد، عدد مرات التجديد، ملاحظات
     */
    public function importContracts(UploadedFile $file, int $organizationId, ?int $userId = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $total = 0;
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $isFirst = true;
        foreach ($rows as $rowIndex => $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            if (empty(array_filter($row))) {
                continue;
            }

            $total++;

            $name = trim($row['A'] ?? '');
            $nationalId = trim($row['B'] ?? '');
            $profession = trim($row['C'] ?? '');
            $skillClassification = trim($row['D'] ?? '');
            $department = trim($row['E'] ?? '');
            $rawRelationStart = $row['F'] ?? null;
            $rawContractStart = $row['G'] ?? null;
            $rawContractEnd = $row['H'] ?? null;
            $renewalsCount = (int) ($row['I'] ?? 0);
            $notes = trim($row['J'] ?? '');

            if (empty($nationalId)) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex}: رقم الهوية/الإقامة مفقود.";
                continue;
            }

            $contractEnd = self::parseExcelDate($rawContractEnd);
            if (!$contractEnd) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex} (عقد {$nationalId}): تاريخ نهاية العقد غير صالح ({$rawContractEnd}).";
                continue;
            }

            $relationStart = self::parseExcelDate($rawRelationStart);
            $contractStart = self::parseExcelDate($rawContractStart);

            $employee = Employee::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'national_id' => $nationalId,
                ],
                [
                    'name' => $name ?: 'بدون اسم',
                    'profession' => $profession,
                    'skill_classification' => $skillClassification,
                    'department' => $department,
                ]
            );

            $existing = Contract::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('national_id', $nationalId)
                ->first();

            $data = [
                'employee_id' => $employee->id,
                'name' => $name,
                'profession' => $profession,
                'skill_classification' => $skillClassification,
                'department' => $department,
                'contractual_relation_start' => $relationStart?->format('Y-m-d'),
                'contract_start_date' => $contractStart?->format('Y-m-d'),
                'contract_end_date' => $contractEnd->format('Y-m-d'),
                'renewals_count' => $renewalsCount,
                'notes' => $notes,
            ];

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Contract::withoutGlobalScopes()->create(array_merge($data, [
                    'organization_id' => $organizationId,
                    'national_id' => $nationalId,
                ]));
                $inserted++;
            }
        }

        $log = ImportLog::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'module_type' => 'contracts',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $total,
            'inserted_rows' => $inserted,
            'updated_rows' => $updated,
            'failed_rows' => $failed,
            'error_details' => $errors,
        ]);

        return [
            'total' => $total,
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
            'log_id' => $log->id,
        ];
    }

    /**
     * استيراد ملف التراخيص والشهادات
     * الأعمدة: الوثيقة، النوع، الرقم، الفرع، تاريخ الإصدار، تاريخ الانتهاء، ملاحظات
     */
    public function importLicenses(UploadedFile $file, int $organizationId, ?int $userId = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $total = 0;
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $isFirst = true;
        foreach ($rows as $rowIndex => $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            if (empty(array_filter($row))) {
                continue;
            }

            $total++;

            $docName = trim($row['A'] ?? '');
            $docType = trim($row['B'] ?? '');
            $docNumber = trim($row['C'] ?? '');
            $branch = trim($row['D'] ?? '');
            $rawIssueDate = $row['E'] ?? null;
            $rawExpiryDate = $row['F'] ?? null;
            $notes = trim($row['G'] ?? '');

            if (empty($docNumber)) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex}: رقم الوثيقة مفقود.";
                continue;
            }

            $expiryDate = self::parseExcelDate($rawExpiryDate);
            if (!$expiryDate) {
                $failed++;
                $errors[] = "الصف رقم {$rowIndex} (وثيقة {$docNumber}): تاريخ الانتهاء غير صالح ({$rawExpiryDate}).";
                continue;
            }

            $issueDate = self::parseExcelDate($rawIssueDate);

            $existing = License::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('document_number', $docNumber)
                ->first();

            $data = [
                'document_name' => $docName,
                'document_type' => $docType,
                'branch' => $branch,
                'issue_date' => $issueDate?->format('Y-m-d'),
                'expiry_date' => $expiryDate->format('Y-m-d'),
                'notes' => $notes,
            ];

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                License::withoutGlobalScopes()->create(array_merge($data, [
                    'organization_id' => $organizationId,
                    'document_number' => $docNumber,
                ]));
                $inserted++;
            }
        }

        $log = ImportLog::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'module_type' => 'licenses',
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $total,
            'inserted_rows' => $inserted,
            'updated_rows' => $updated,
            'failed_rows' => $failed,
            'error_details' => $errors,
        ]);

        return [
            'total' => $total,
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
            'log_id' => $log->id,
        ];
    }
}
