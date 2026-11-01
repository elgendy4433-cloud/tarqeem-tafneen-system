<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $query = Contract::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('profession', 'like', "%{$search}%");
            });
        }

        if ($department = $request->input('department')) {
            $query->where('department', $department);
        }

        if ($status = $request->input('status')) {
            $today = Carbon::today();
            if ($status === 'expired') {
                $query->where('contract_end_date', '<', $today);
            } elseif ($status === 'near') {
                $query->where('contract_end_date', '>=', $today)
                      ->where('contract_end_date', '<=', $today->copy()->addDays(90));
            } elseif ($status === 'valid') {
                $query->where('contract_end_date', '>', $today->copy()->addDays(90));
            }
        }

        $departments = Contract::whereNotNull('department')->distinct()->pluck('department');
        $contracts = $query->orderBy('contract_end_date', 'asc')->paginate(20)->withQueryString();

        return view('contracts.index', compact('contracts', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'required|string|max:50',
            'profession' => 'nullable|string|max:255',
            'skill_classification' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'contractual_relation_start' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'required|date',
            'renewals_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        Contract::create($validated);

        return redirect()->route('contracts.index')->with('success', 'تمت إضافة العقد الوظيفي بنجاح.');
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'national_id' => 'required|string|max:50',
            'profession' => 'nullable|string|max:255',
            'skill_classification' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'contractual_relation_start' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'required|date',
            'renewals_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $contract->update($validated);

        return redirect()->back()->with('success', 'تم تحديث بيانات العقد بنجاح.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'تم حذف العقد بنجاح.');
    }

    public function export(): StreamedResponse
    {
        $records = Contract::orderBy('contract_end_date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contracts_export_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['الاسم', 'رقم الهوية/الإقامة', 'المهنة', 'تصنيف المهارات', 'الإدارة', 'بداية العلاقة التعاقدية', 'بداية العقد', 'نهاية العقد', 'الأيام المتبقية لاتخاذ إجراء', 'عدد التجديدات', 'الحالة', 'ملاحظات']);

            foreach ($records as $item) {
                fputcsv($handle, [
                    $item->name,
                    $item->national_id,
                    $item->profession,
                    $item->skill_classification,
                    $item->department,
                    $item->contractual_relation_start?->format('Y-m-d'),
                    $item->contract_start_date?->format('Y-m-d'),
                    $item->contract_end_date?->format('Y-m-d'),
                    $item->days_remaining,
                    $item->renewals_count,
                    $item->status,
                    $item->notes,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
