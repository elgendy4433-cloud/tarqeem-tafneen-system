@extends('layouts.app')

@section('title', 'إدارة العقود الوظيفية')

@php
    $currentOrg = \App\Models\Organization::find(session('current_organization_id', 1)) ?? \App\Models\Organization::first();
    $isTarqeem = ($currentOrg?->code === 'tarqeem');
    $cardBg = $isTarqeem ? 'bg-[#0e1736] border-[#1b2b5a]' : 'bg-[#0d1424] border-[#1a273f]';
@endphp

@section('content')
<div class="space-y-6" x-data="{ addModal: false, editModal: false, activeItem: {} }">

    <!-- شريط العنوان -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-black text-white">سجل العقود الوظيفية</h1>
            <p class="text-xs text-slate-400 mt-1">
                متابعة تواريخ نهاية العقود وفترات الإشعار دون أخطاء حسابية (تنبيه عند &le; 90 يوماً)
            </p>
        </div>
        <div class="flex items-center space-x-2 space-x-reverse">
            <a href="{{ route('contracts.export') }}" class="inline-flex items-center space-x-1.5 space-x-reverse bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl shadow-md transition">
                <span>📊</span><span>تصدير Excel/CSV</span>
            </a>
            <button @click="addModal = true" class="inline-flex items-center space-x-1.5 space-x-reverse {{ $isTarqeem ? 'bg-gradient-to-r from-cyan-400 to-sky-600 text-slate-950 font-black shadow-[0_0_15px_rgba(77,195,247,0.3)]' : 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-bold border border-[#4eb535]/50 shadow-[0_0_15px_rgba(37,99,235,0.35)]' }} text-xs px-4 py-2.5 rounded-xl transition">
                <span>➕</span><span>إضافة عقد جديد</span>
            </button>
        </div>
    </div>

    <!-- فلاتر البحث -->
    <div class="{{ $cardBg }} p-4 rounded-2xl border shadow-xl">
        <form method="GET" action="{{ route('contracts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">بحث بالاسم أو رقم الهوية</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="أدخل الاسم أو الهوية..." class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-2.5 border focus:border-cyan-400">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">الإدارة</label>
                <select name="department" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-2.5 border focus:border-cyan-400">
                    <option value="">جميع الإدارات</option>
                    @foreach($departments as $d)
                    <option value="{{ $d }}" {{ request('department') == $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">الحالة</label>
                <select name="status" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-2.5 border focus:border-cyan-400">
                    <option value="">جميع الحالات</option>
                    <option value="valid" {{ request('status') == 'valid' ? 'selected' : '' }}>🟢 ساري (أكثر من 90 يوم)</option>
                    <option value="near" {{ request('status') == 'near' ? 'selected' : '' }}>🟡 قريب الانتهاء (&le; 90 يوم)</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>🔴 منتهي</option>
                </select>
            </div>

            <div class="flex items-end space-x-2 space-x-reverse">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-700 transition">تطبيق الفلترة</button>
                <a href="{{ route('contracts.index') }}" class="bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-bold px-3 py-2.5 rounded-xl border border-slate-800 transition">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <!-- جدول عرض العقود -->
    <div class="{{ $cardBg }} rounded-2xl border shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-xs text-right">
                <thead class="bg-slate-950/70 text-slate-300 font-bold border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">الاسم الكامل</th>
                        <th class="px-4 py-3.5">رقم الهوية / الإقامة</th>
                        <th class="px-4 py-3.5">المهنة</th>
                        <th class="px-4 py-3.5">تصنيف المهارات</th>
                        <th class="px-4 py-3.5">الإدارة</th>
                        <th class="px-4 py-3.5">بداية العقد</th>
                        <th class="px-4 py-3.5">نهاية العقد</th>
                        <th class="px-4 py-3.5">الأيام لاتخاذ إجراء</th>
                        <th class="px-4 py-3.5">الحالة</th>
                        <th class="px-4 py-3.5 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($contracts as $item)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-4 py-3 font-bold text-white">{{ $item->name }}</td>
                        <td class="px-4 py-3 font-mono font-bold text-cyan-400">{{ $item->national_id }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $item->profession ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $item->skill_classification ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $item->department ?: '-' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-400">{{ $item->contract_start_date?->format('Y-m-d') ?: '-' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-300">{{ $item->contract_end_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 font-bold font-mono text-slate-200">
                            {{ $item->days_remaining }} يوم
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $item->status_badge['class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $item->status_badge['dot'] }}"></span>
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center space-x-1 space-x-reverse whitespace-nowrap">
                            <button @click="editModal = true; activeItem = {{ json_encode($item) }}" class="text-slate-300 hover:text-cyan-400 px-2 py-1 transition" title="تعديل">
                                ✏️
                            </button>
                            <form action="{{ route('contracts.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-300 hover:text-rose-400 px-2 py-1 transition" title="حذف">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-slate-500">
                            لا توجد سجلات عقود وظيفية مطابقة.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
        <div class="p-4 border-t border-slate-800 bg-slate-950/60">
            {{ $contracts->links() }}
        </div>
        @endif
    </div>

    <!-- نافذة إضافة عقد جديد (Dark Modal) -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
        <div @click.away="addModal = false" class="{{ $cardBg }} rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 text-right border border-slate-700">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="font-bold text-sm text-white">إضافة عقد وظيفي جديد</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-white font-bold text-xl">&times;</button>
            </div>
            <form action="{{ route('contracts.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-300 mb-1">اسم الموظف *</label>
                    <input type="text" name="name" required class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">رقم الهوية / الإقامة *</label>
                    <input type="text" name="national_id" required class="w-full bg-slate-900 border border-slate-700 text-white font-mono rounded-xl p-2.5">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">المهنة</label>
                        <input type="text" name="profession" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">الإدارة</label>
                        <input type="text" name="department" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">بداية العقد</label>
                        <input type="date" name="contract_start_date" class="w-full bg-slate-900 border border-slate-700 text-white font-mono rounded-xl p-2.5">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-300 mb-1">نهاية العقد *</label>
                        <input type="date" name="contract_end_date" required class="w-full bg-slate-900 border border-slate-700 text-white font-mono rounded-xl p-2.5">
                    </div>
                </div>
                <div class="flex justify-end space-x-2 space-x-reverse pt-3 border-t border-slate-800">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl">إلغاء</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg">حفظ العقد</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
