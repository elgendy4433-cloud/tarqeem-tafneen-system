<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Iqama;
use App\Models\License;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $currentOrgId = session('current_organization_id');
        if (!$currentOrgId) {
            $defaultOrg = Organization::where('is_active', true)->first();
            $currentOrgId = $defaultOrg?->id ?? 1;
            session(['current_organization_id' => $currentOrgId]);
        }

        $organization = Organization::with('setting')->findOrFail($currentOrgId);
        $organizations = Organization::where('is_active', true)->get();

        $iqamaThreshold = $organization->setting->iqama_alert_threshold ?? 45;
        $contractThreshold = $organization->setting->contract_alert_threshold ?? 90;

        // إحصائيات الإقامات
        $iqamasTotal = Iqama::count();
        $iqamasExpired = Iqama::expired()->count();
        $iqamasNear = Iqama::nearToExpire($iqamaThreshold)->count();
        $iqamasValid = Iqama::valid($iqamaThreshold)->count();

        // إحصائيات العقود الوظيفية
        $contractsTotal = Contract::count();
        $contractsExpired = Contract::expired()->count();
        $contractsNear = Contract::nearToExpire($contractThreshold)->count();
        $contractsValid = Contract::valid($contractThreshold)->count();

        // إحصائيات التراخيص والشهادات
        $licensesTotal = License::count();
        $licensesExpired = License::expired()->count();
        $licensesNear = License::nearToExpire(60)->count();
        $licensesValid = License::valid(60)->count();

        // السجلات الأكثر إلحاحاً التي قاربت على الانتهاء
        $urgentIqamas = Iqama::where('expiry_date', '>=', Carbon::today())
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get();

        $urgentContracts = Contract::where('contract_end_date', '>=', Carbon::today())
            ->orderBy('contract_end_date', 'asc')
            ->take(5)
            ->get();

        $urgentLicenses = License::where('expiry_date', '>=', Carbon::today())
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'organization',
            'organizations',
            'iqamasTotal', 'iqamasExpired', 'iqamasNear', 'iqamasValid',
            'contractsTotal', 'contractsExpired', 'contractsNear', 'contractsValid',
            'licensesTotal', 'licensesExpired', 'licensesNear', 'licensesValid',
            'urgentIqamas', 'urgentContracts', 'urgentLicenses'
        ));
    }
}
