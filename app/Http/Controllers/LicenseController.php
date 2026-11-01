<?php

namespace App\Http\Controllers;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = License::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('document_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('document_type', 'like', "%{$search}%");
            });
        }

        if ($branch = $request->input('branch')) {
            $query->where('branch', $branch);
        }

        if ($status = $request->input('status')) {
            $today = Carbon::today();
            if ($status === 'expired') {
                $query->where('expiry_date', '<', $today);
            } elseif ($status === 'near') {
                $query->where('expiry_date', '>=', $today)
                      ->where('expiry_date', '<=', $today->copy()->addDays(60));
            } elseif ($status === 'valid') {
                $query->where('expiry_date', '>', $today->copy()->addDays(60));
            }
        }

        $branches = License::whereNotNull('branch')->distinct()->pluck('branch');
        $licenses = $query->orderBy('expiry_date', 'asc')->paginate(20)->withQueryString();

        return view('licenses.index', compact('licenses', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_name' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'document_number' => 'required|string|max:100',
            'branch' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        License::create($validated);

        return redirect()->route('licenses.index')->with('success', 'تمت إضافة الترخيص/الوثيقة بنجاح.');
    }

    public function update(Request $request, License $license): RedirectResponse
    {
        $validated = $request->validate([
            'document_name' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'document_number' => 'required|string|max:100',
            'branch' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $license->update($validated);

        return redirect()->back()->with('success', 'تم تحديث بيانات الوثيقة بنجاح.');
    }

    public function destroy(License $license): RedirectResponse
    {
        $license->delete();
        return redirect()->route('licenses.index')->with('success', 'تم حذف الترخيص بنجاح.');
    }

    public function export(): StreamedResponse
    {
        $records = License::orderBy('expiry_date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="licenses_export_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['اسم الوثيقة', 'النوع', 'رقم الوثيقة', 'الفرع', 'تاريخ الإصدار', 'تاريخ الانتهاء', 'الأيام المتبقية', 'الحالة', 'ملاحظات']);

            foreach ($records as $item) {
                fputcsv($handle, [
                    $item->document_name,
                    $item->document_type,
                    $item->document_number,
                    $item->branch,
                    $item->issue_date?->format('Y-m-d'),
                    $item->expiry_date?->format('Y-m-d'),
                    $item->days_remaining,
                    $item->status,
                    $item->notes,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
