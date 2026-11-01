<?php

namespace App\Console\Commands;

use App\Services\AlertNotificationService;
use Illuminate\Console\Command;

class SendExpirationAlertsCommand extends Command
{
    protected $signature = 'alerts:send';

    protected $description = 'فحص وإرسال التنبيهات الفورية للإقامات والعقود التي شارفت على الانتهاء';

    public function handle(AlertNotificationService $alertService): int
    {
        $this->info('بدء فحص سجلات الإقامات والعقود لجميع المؤسسات...');

        $result = $alertService->checkAndSendAlerts();

        $this->info("تم إرسال {$result['iqama_alerts_sent']} تنبيه للإقامات.");
        $this->info("تم إرسال {$result['contract_alerts_sent']} تنبيه للعقود.");

        if (!empty($result['errors'])) {
            $this->warn('حدثت بعض الأخطاء أثناء الإرسال:');
            foreach ($result['errors'] as $error) {
                $this->error("- {$error}");
            }
        }

        $this->info('اكتملت عملية فحص التنبيهات بنجاح.');
        return Command::SUCCESS;
    }
}
