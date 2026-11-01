<?php

namespace App\Http\Controllers;

use App\Models\Iqama;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IqamaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Iqama::query();

        // بحث بالاسم أو برقم الإقامة
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('iqama_number', 'like', "%{$search}%")
                  ->orWhere('profession', 'like', "%{$search}%");
            });
        }

        // فلترة بالفرع
        if ($branch = $request->input('branch')) {
            $query->where('branch', $branch);
        }

        // فلترة بالحالة
        if ($status = $request->input('status')) {
            $today = Carbon::today();
            if ($status === 'expired') {
                $query->where('expiry_date', '<', $today);
            } elseif ($status === 'near') {
                $query->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(45));
            } elseif ($status === 'valid') {
                $query->where('expiry_date', '>', $today->copy()->addDays(45));
            }
        }

        $branches = Iqama::whereNotNull('branch')->distinct()->pluck('branch');
        $iqamas = $query->orderBy('expiry_date', 'asc')->paginate(20)->withQueryString();

        return view('iqamas.index', compact('iqamas', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iqama_number' => 'required|string|max:50',
            'profession' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
            'transfer_renewal_notes' => 'nullable|string',
        ]);

        Iqama::create($validated);

        return redirect()->route('iqamas.index')->with('success', 'تمت إضافة الإقامة بنجاح.');
    }

    public function update(Request $request, Iqama $iqama): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iqama_number' => 'required|string|max:50',
            'profession' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
            'transfer_renewal_notes' => 'nullable|string',
        ]);

        $iqama->update($validated);

        return redirect()->back()->with('success', 'تم تحديث بيانات الإقامة بنجاح.');
    }

    public function destroy(Iqama $iqama): RedirectResponse
    {
        $iqama->delete();
        return redirect()->route('iqamas.index')->with('success', 'تم حذف الإقامة بنجاح.');
    }

    public function export(): StreamedResponse
    {
        $records = Iqama::orderBy('expiry_date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="iqamas_export_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 للعربية
            fputcsv($handle, ['الاسم', 'رقم الإقامة', 'المهنة', 'الفرع', 'تاريخ الانتهاء', 'الأيام المتبقية', 'الحالة', 'ملاحظات', 'ملاحظات النقل والتجديد']);

            foreach ($records as $item) {
                fputcsv($handle, [
                    $item->name,
                    $item->iqama_number,
                    $item->profession,
                    $item->branch,
                    $item->expiry_date?->format('Y-m-d'),
                    $item->days_remaining,
                    $item->status,
                    $item->notes,
                    $item->transfer_renewal_notes,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
