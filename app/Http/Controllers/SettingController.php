<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SystemSetting;
use App\Services\AlertNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $currentOrgId = session('current_organization_id', 1);
        $organization = Organization::with('setting')->findOrFail($currentOrgId);

        $setting = $organization->setting ?? SystemSetting::firstOrCreate([
            'organization_id' => $organization->id,
        ], [
            'iqama_alert_threshold' => 45,
            'contract_alert_threshold' => 90,
            'is_alert_enabled' => true,
        ]);

        return view('settings.index', compact('organization', 'setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $currentOrgId = session('current_organization_id', 1);

        $validated = $request->validate([
            'iqama_alert_threshold' => 'required|integer|min:1|max:365',
            'contract_alert_threshold' => 'required|integer|min:1|max:365',
            'alert_emails' => 'nullable|string',
            'is_alert_enabled' => 'boolean',
        ]);

        $setting = SystemSetting::updateOrCreate(
            ['organization_id' => $currentOrgId],
            [
                'iqama_alert_threshold' => $validated['iqama_alert_threshold'],
                'contract_alert_threshold' => $validated['contract_alert_threshold'],
                'alert_emails' => $validated['alert_emails'] ?? '',
                'is_alert_enabled' => $request->has('is_alert_enabled'),
            ]
        );

        return redirect()->back()->with('success', 'تم حفظ إعدادات التنبيهات بنجاح.');
    }

    public function runAlertsNow(AlertNotificationService $alertService): RedirectResponse
    {
        $result = $alertService->checkAndSendAlerts();
        $msg = "تم فحص التنبيهات وإرسالها! الإقامات: {$result['iqama_alerts_sent']}، العقود: {$result['contract_alerts_sent']}";

        return redirect()->back()->with('success', $msg);
    }
}
