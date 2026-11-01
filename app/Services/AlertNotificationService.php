<?php

namespace App\Services;

use App\Mail\ExpirationAlertMail;
use App\Models\AlertLog;
use App\Models\Contract;
use App\Models\Iqama;
use App\Models\Organization;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * فحص التنبيهات الفورية وإرسالها لكل مؤسسة
     */
    public function checkAndSendAlerts(): array
    {
        $organizations = Organization::where('is_active', true)->with('setting')->get();
        $summary = [
            'iqama_alerts_sent' => 0,
            'contract_alerts_sent' => 0,
            'errors' => [],
        ];

        $today = Carbon::today();

        foreach ($organizations as $org) {
            $setting = $org->setting;
            if (!$setting || !$setting->is_alert_enabled) {
                continue;
            }

            $recipients = $setting->recipients_list;
            if (empty($recipients)) {
                continue;
            }

            $iqamaThreshold = $setting->iqama_alert_threshold ?? 45;
            $contractThreshold = $setting->contract_alert_threshold ?? 90;

            // ١. فحص الإقامات التي بلغت النطاق الحرج (٤٥ يوماً أو أقل) ولم يتم تنبيهها لتاريخ الانتهاء الحالي
            $iqamasToAlert = Iqama::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->where('expiry_date', '<=', $today->copy()->addDays($iqamaThreshold))
                ->where(function ($query) {
                    $query->whereNull('alerted_for_expiry')
                          ->orWhereColumn('alerted_for_expiry', '!=', 'expiry_date');
                })
                ->get();

            foreach ($iqamasToAlert as $iqama) {
                $days = $iqama->days_remaining;
                $subject = "🚨 تنبيه فوري: اقتراب انتهاء إقامة ({$iqama->name}) - مؤسسة {$org->name}";

                foreach ($recipients as $recipient) {
                    try {
                        Mail::to($recipient)->send(new ExpirationAlertMail(
                            organization: $org,
                            moduleType: 'iqama',
                            title: $subject,
                            recordName: $iqama->name,
                            identifier: $iqama->iqama_number,
                            profession: $iqama->profession ?? 'غير محدد',
                            branch: $iqama->branch ?? 'الرئيسي',
                            expiryDate: $iqama->expiry_date->format('Y-m-d'),
                            daysRemaining: $days,
                            notes: $iqama->notes . ($iqama->transfer_renewal_notes ? " | ملاحظات النقل: {$iqama->transfer_renewal_notes}" : '')
                        ));

                        AlertLog::create([
                            'organization_id' => $org->id,
                            'module_type' => 'iqama',
                            'record_id' => $iqama->id,
                            'record_name' => $iqama->name,
                            'record_identifier' => $iqama->iqama_number,
                            'days_remaining_at_send' => $days,
                            'recipient_email' => $recipient,
                            'subject' => $subject,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        $summary['iqama_alerts_sent']++;
                    } catch (Exception $e) {
                        Log::error("Failed to send iqama alert email: " . $e->getMessage());
                        AlertLog::create([
                            'organization_id' => $org->id,
                            'module_type' => 'iqama',
                            'record_id' => $iqama->id,
                            'record_name' => $iqama->name,
                            'record_identifier' => $iqama->iqama_number,
                            'days_remaining_at_send' => $days,
                            'recipient_email' => $recipient,
                            'subject' => $subject,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'sent_at' => now(),
                        ]);
                        $summary['errors'][] = "الإقامة {$iqama->iqama_number}: " . $e->getMessage();
                    }
                }

                // تحديث السجل لمنع التكرار
                $iqama->update([
                    'alert_sent_at' => now(),
                    'alerted_for_expiry' => $iqama->expiry_date,
                ]);
            }

            // ٢. فحص العقود التي بلغت النطاق الحرج (٩٠ يوماً أو أقل)
            $contractsToAlert = Contract::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->where('contract_end_date', '<=', $today->copy()->addDays($contractThreshold))
                ->where(function ($query) {
                    $query->whereNull('alerted_for_expiry')
                          ->orWhereColumn('alerted_for_expiry', '!=', 'contract_end_date');
                })
                ->get();

            foreach ($contractsToAlert as $contract) {
                $days = $contract->days_remaining;
                $subject = "🚨 تنبيه فوري: اقتراب انتهاء عقد عمل ({$contract->name}) - مؤسسة {$org->name}";

                foreach ($recipients as $recipient) {
                    try {
                        Mail::to($recipient)->send(new ExpirationAlertMail(
                            organization: $org,
                            moduleType: 'contract',
                            title: $subject,
                            recordName: $contract->name,
                            identifier: $contract->national_id,
                            profession: $contract->profession ?? 'غير محدد',
                            branch: $contract->department ?? $contract->branch ?? 'عام',
                            expiryDate: $contract->contract_end_date->format('Y-m-d'),
                            daysRemaining: $days,
                            notes: $contract->notes ?? 'لا توجد ملاحظات إضافية'
                        ));

                        AlertLog::create([
                            'organization_id' => $org->id,
                            'module_type' => 'contract',
                            'record_id' => $contract->id,
                            'record_name' => $contract->name,
                            'record_identifier' => $contract->national_id,
                            'days_remaining_at_send' => $days,
                            'recipient_email' => $recipient,
                            'subject' => $subject,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        $summary['contract_alerts_sent']++;
                    } catch (Exception $e) {
                        Log::error("Failed to send contract alert email: " . $e->getMessage());
                        AlertLog::create([
                            'organization_id' => $org->id,
                            'module_type' => 'contract',
                            'record_id' => $contract->id,
                            'record_name' => $contract->name,
                            'record_identifier' => $contract->national_id,
                            'days_remaining_at_send' => $days,
                            'recipient_email' => $recipient,
                            'subject' => $subject,
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'sent_at' => now(),
                        ]);
                        $summary['errors'][] = "العقد {$contract->national_id}: " . $e->getMessage();
                    }
                }

                $contract->update([
                    'alert_sent_at' => now(),
                    'alerted_for_expiry' => $contract->contract_end_date,
                ]);
            }
        }

        return $summary;
    }
}
