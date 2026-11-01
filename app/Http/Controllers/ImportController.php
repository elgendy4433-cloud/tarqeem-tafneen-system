<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Organization;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        protected ExcelImportService $importService
    ) {}

    public function index(): View
    {
        $currentOrgId = session('current_organization_id', 1);
        $organizations = Organization::where('is_active', true)->get();
        $recentLogs = ImportLog::where('organization_id', $currentOrgId)
            ->latest()
            ->take(10)
            ->get();

        return view('imports.index', compact('organizations', 'recentLogs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'module_type' => 'required|in:iqamas,contracts,licenses',
            'organization_id' => 'required|exists:organizations,id',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $module = $request->input('module_type');
        $orgId = (int) $request->input('organization_id');
        $file = $request->file('file');
        $userId = auth()->id();

        $result = match ($module) {
            'iqamas' => $this->importService->importIqamas($file, $orgId, $userId),
            'contracts' => $this->importService->importContracts($file, $orgId, $userId),
            'licenses' => $this->importService->importLicenses($file, $orgId, $userId),
        };

        $msg = "اكتمل الاستيراد! الإجمالي: {$result['total']}، تمت الإضافة: {$result['inserted']}، تم التحديث: {$result['updated']}، المتعثر: {$result['failed']}";

        return redirect()->route('imports.index')
            ->with('import_summary', $result)
            ->with('success', $msg);
    }

    public function downloadTemplate(string $type): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"template_{$type}.csv\"",
        ];

        return response()->stream(function () use ($type) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'iqamas') {
                fputcsv($handle, ['الاسم', 'رقم الإقامة', 'المهنة', 'الفرع', 'تاريخ الانتهاء (YYYY-MM-DD)', 'ملاحظات', 'ملاحظات النقل والتجديد']);
                fputcsv($handle, ['محمد أحمد علي', '2345678901', 'مهندس برمجيات', 'فرع الرياض', '2026-12-31', 'عقد ساري', 'لا يوجد نقل']);
            } elseif ($type === 'contracts') {
                fputcsv($handle, ['الاسم', 'رقم الهوية / الإقامة', 'المهنة', 'تصنيف المهارات', 'الإدارة', 'بداية العلاقة التعاقدية (YYYY-MM-DD)', 'بداية العقد (YYYY-MM-DD)', 'نهاية العقد (YYYY-MM-DD)', 'عدد مرات التجديد', 'ملاحظات']);
                fputcsv($handle, ['سالم عبد الله', '1098765432', 'محاسب مالي', 'محترف', 'الإدارة المالية', '2023-01-01', '2026-01-01', '2027-01-01', '2', 'تجديد سنوي تلقائي']);
            } elseif ($type === 'licenses') {
                fputcsv($handle, ['اسم الوثيقة', 'النوع', 'رقم الوثيقة', 'الفرع', 'تاريخ الإصدار (YYYY-MM-DD)', 'تاريخ الانتهاء (YYYY-MM-DD)', 'ملاحظات']);
                fputcsv($handle, ['رخصة البلدية المركزية', 'بلدي', '789456123', 'فرع جدة', '2024-01-01', '2026-11-30', 'مجدد']);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
