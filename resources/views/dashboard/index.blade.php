@extends('layouts.app')

@section('title', 'لوحة التحكم الرئيسية - ' . $organization->name)

@php
    $isTarqeem = ($organization->code === 'tarqeem');
    $cardBg = $isTarqeem ? 'bg-[#0e1736] border-[#1b2b5a]' : 'bg-[#0d1424] border-[#1a273f]';
    $brandAccent = $isTarqeem ? 'text-cyan-400' : 'text-[#4eb535]';
    $brandBtn = $isTarqeem 
        ? 'bg-gradient-to-r from-cyan-400 to-sky-600 text-slate-950 shadow-[0_0_15px_rgba(77,195,247,0.3)]' 
        : 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white border border-[#4eb535]/50 shadow-[0_0_15px_rgba(37,99,235,0.35)]';
@endphp

@section('content')
<div class="space-y-8">

    <!-- ترويسة لوحة التحكم مع شارة الهوية بالوضع الليلي -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-800 gap-4">
        <div class="flex items-center space-x-4 space-x-reverse">
            @if($isTarqeem)
            <div class="flex items-center space-x-3 space-x-reverse bg-[#151e48] px-4 py-2.5 rounded-2xl shadow-xl border border-[#4dc3f7]/50 ring-1 ring-[#4dc3f7]/30 shadow-[0_0_20px_rgba(77,195,247,0.2)]">
                <div class="flex flex-col justify-between w-4 h-4">
                    <div class="w-2.5 h-2 bg-[#4dc3f7] rounded-[1px] shadow-[0_0_8px_#4dc3f7]"></div>
                    <div class="w-2.5 h-2 bg-[#4dc3f7] rounded-[1px] mr-auto shadow-[0_0_8px_#4dc3f7]"></div>
                </div>
                <div class="flex flex-col text-right leading-none">
                    <span class="text-[10px] font-mono tracking-widest font-black text-[#4dc3f7]">TRQEM</span>
                    <span class="text-sm font-black text-white mt-0.5 tracking-wide">تـرقـيـم</span>
                </div>
            </div>
            @else
            <div class="flex items-center space-x-3 space-x-reverse bg-[#0d1527] px-4 py-2.5 rounded-2xl shadow-xl border border-[#2563eb]/60 ring-1 ring-[#4eb535]/50 shadow-[0_0_20px_rgba(37,99,235,0.25)]">
                <div class="w-2.5 h-2.5 rounded-full bg-[#4eb535] shadow-[0_0_8px_#4eb535]"></div>
                <div class="flex flex-col text-right leading-none">
                    <span class="text-[10px] font-mono tracking-widest font-black text-[#4eb535]">TAFNEEN</span>
                    <span class="text-sm font-black text-white mt-0.5 tracking-wide">تَفْنِين</span>
                </div>
            </div>
            @endif

            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                    <span>نظام المتابعة والمؤشرات اللحظية</span>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md {{ $isTarqeem ? 'bg-cyan-950 text-cyan-400 border border-cyan-800' : 'bg-emerald-950 text-emerald-400 border border-emerald-800' }}">
                        {{ $isTarqeem ? 'TRQEM MODE' : 'TAFNEEN MODE' }}
                    </span>
                </h1>
                <p class="text-sm text-slate-400 mt-0.5">
                    متابعة العقود والإقامات والتراخيص لمؤسسة <span class="font-bold text-white">{{ $organization->name }}</span>
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-3 space-x-reverse">
            <a href="{{ route('imports.index') }}" class="inline-flex items-center space-x-2 space-x-reverse {{ $brandBtn }} text-sm font-bold px-4 py-2.5 rounded-xl transition">
                <span>📥</span><span>استيراد ملف جديد</span>
            </a>
            <form action="{{ route('settings.triggerAlerts') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center space-x-2 space-x-reverse bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-sm font-bold px-4 py-2.5 rounded-xl shadow-md transition">
                    <span>⚡</span><span>فحص التنبيهات الآن</span>
                </button>
            </form>
        </div>
    </div>

    <!-- وحدات الداشبورد الثلاث مع بطاقات KPI لكل منها (Dark Mode) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ١. وحدة الإقامات -->
        <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-xl font-bold">
                            🪪
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">الإقامات</h2>
                            <p class="text-xs text-slate-400">تنبيه عند &le; {{ $organization->setting->iqama_alert_threshold ?? 45 }} يوماً</p>
                        </div>
                    </div>
                    <a href="{{ route('iqamas.index') }}" class="text-xs {{ $brandAccent }} hover:underline font-bold">عرض الكل &larr;</a>
                </div>

                <div class="grid grid-cols-3 gap-2.5 text-center mt-6">
                    <div class="bg-emerald-950/40 rounded-xl p-3 border border-emerald-500/30">
                        <span class="block text-2xl font-black text-emerald-400">{{ $iqamasValid }}</span>
                        <span class="text-xs font-bold text-emerald-300">ساري</span>
                    </div>
                    <div class="bg-amber-950/40 rounded-xl p-3 border border-amber-500/30">
                        <span class="block text-2xl font-black text-amber-400">{{ $iqamasNear }}</span>
                        <span class="text-xs font-bold text-amber-300">قريب الانتهاء</span>
                    </div>
                    <div class="bg-rose-950/40 rounded-xl p-3 border border-rose-500/30">
                        <span class="block text-2xl font-black text-rose-400">{{ $iqamasExpired }}</span>
                        <span class="text-xs font-bold text-rose-300">منتهي</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span>إجمالي الإقامات المسجلة:</span>
                <span class="font-black text-white text-sm">{{ $iqamasTotal }}</span>
            </div>
        </div>

        <!-- ٢. وحدة العقود الوظيفية -->
        <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl font-bold">
                            📑
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">العقود الوظيفية</h2>
                            <p class="text-xs text-slate-400">تنبيه عند &le; {{ $organization->setting->contract_alert_threshold ?? 90 }} يوماً</p>
                        </div>
                    </div>
                    <a href="{{ route('contracts.index') }}" class="text-xs {{ $brandAccent }} hover:underline font-bold">عرض الكل &larr;</a>
                </div>

                <div class="grid grid-cols-3 gap-2.5 text-center mt-6">
                    <div class="bg-emerald-950/40 rounded-xl p-3 border border-emerald-500/30">
                        <span class="block text-2xl font-black text-emerald-400">{{ $contractsValid }}</span>
                        <span class="text-xs font-bold text-emerald-300">ساري</span>
                    </div>
                    <div class="bg-amber-950/40 rounded-xl p-3 border border-amber-500/30">
                        <span class="block text-2xl font-black text-amber-400">{{ $contractsNear }}</span>
                        <span class="text-xs font-bold text-amber-300">قريب الانتهاء</span>
                    </div>
                    <div class="bg-rose-950/40 rounded-xl p-3 border border-rose-500/30">
                        <span class="block text-2xl font-black text-rose-400">{{ $contractsExpired }}</span>
                        <span class="text-xs font-bold text-rose-300">منتهي</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span>إجمالي العقود المسجلة:</span>
                <span class="font-black text-white text-sm">{{ $contractsTotal }}</span>
            </div>
        </div>

        <!-- ٣. وحدة التراخيص والشهادات -->
        <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center text-xl font-bold">
                            📜
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">التراخيص والشهادات</h2>
                            <p class="text-xs text-slate-400">متابعة الفروع والصلاحيات</p>
                        </div>
                    </div>
                    <a href="{{ route('licenses.index') }}" class="text-xs {{ $brandAccent }} hover:underline font-bold">عرض الكل &larr;</a>
                </div>

                <div class="grid grid-cols-3 gap-2.5 text-center mt-6">
                    <div class="bg-emerald-950/40 rounded-xl p-3 border border-emerald-500/30">
                        <span class="block text-2xl font-black text-emerald-400">{{ $licensesValid }}</span>
                        <span class="text-xs font-bold text-emerald-300">ساري</span>
                    </div>
                    <div class="bg-amber-950/40 rounded-xl p-3 border border-amber-500/30">
                        <span class="block text-2xl font-black text-amber-400">{{ $licensesNear }}</span>
                        <span class="text-xs font-bold text-amber-300">قريب الانتهاء</span>
                    </div>
                    <div class="bg-rose-950/40 rounded-xl p-3 border border-rose-500/30">
                        <span class="block text-2xl font-black text-rose-400">{{ $licensesExpired }}</span>
                        <span class="text-xs font-bold text-rose-300">منتهي</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span>إجمالي الوثائق والتراخيص:</span>
                <span class="font-black text-white text-sm">{{ $licensesTotal }}</span>
            </div>
        </div>

    </div>

    <!-- الرسم البياني العام للحالات بالوضع الليلي -->
    <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-2 border-b border-slate-800/80 pb-4">
            <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>📊</span><span>مقارنة الحالات عبر الوحدات الثلاث (Valid / Near / Expired)</span>
                </h3>
                <span class="text-xs text-slate-400">يتم التحديث لحظياً مع كل عملية استيراد أو تعديل</span>
            </div>
            <span class="text-xs text-slate-400 flex items-center gap-3">
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full {{ $isTarqeem ? 'bg-emerald-400' : 'bg-[#4eb535]' }}"></span> ساري</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> قريب</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> منتهي</span>
            </span>
        </div>
        <div class="h-64 sm:h-72">
            <canvas id="overviewChart"></canvas>
        </div>
    </div>

    <!-- السجلات الأكثر إلحاحاً التي تتطلب تدخلاً (Dark Mode) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- إقامات شارفت على الانتهاء -->
        <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl">
            <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span>🪪</span><span>أقرب الإقامات للانتهاء</span>
                </h3>
                <a href="{{ route('iqamas.index', ['status' => 'near']) }}" class="text-xs {{ $brandAccent }} hover:underline font-bold">عرض المزيد</a>
            </div>
            <div class="divide-y divide-slate-800/70">
                @forelse($urgentIqamas as $item)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-sm text-white block">{{ $item->name }}</span>
                        <span class="text-xs text-slate-400">{{ $item->profession }} • إقامة: {{ $item->iqama_number }}</span>
                    </div>
                    <div class="text-left">
                        <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-amber-950/80 text-amber-400 border border-amber-500/40">
                            باقي {{ $item->days_remaining }} يوم
                        </span>
                        <span class="block text-[11px] text-slate-400 mt-1 font-mono">{{ $item->expiry_date?->format('Y-m-d') }}</span>
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-sm text-slate-500">لا توجد إقامات قريبة من الانتهاء حالياً 🎉</div>
                @endforelse
            </div>
        </div>

        <!-- عقود شارفت على الانتهاء -->
        <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl">
            <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                <h3 class="text-sm font-black text-white flex items-center gap-2">
                    <span>📑</span><span>أقرب العقود الوظيفية للانتهاء</span>
                </h3>
                <a href="{{ route('contracts.index', ['status' => 'near']) }}" class="text-xs {{ $brandAccent }} hover:underline font-bold">عرض المزيد</a>
            </div>
            <div class="divide-y divide-slate-800/70">
                @forelse($urgentContracts as $item)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-sm text-white block">{{ $item->name }}</span>
                        <span class="text-xs text-slate-400">{{ $item->department ?? 'عام' }} • هوية: {{ $item->national_id }}</span>
                    </div>
                    <div class="text-left">
                        <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-amber-950/80 text-amber-400 border border-amber-500/40">
                            باقي {{ $item->days_remaining }} يوم
                        </span>
                        <span class="block text-[11px] text-slate-400 mt-1 font-mono">{{ $item->contract_end_date?->format('Y-m-d') }}</span>
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-sm text-slate-500">لا توجد عقود قريبة من الانتهاء حالياً 🎉</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('overviewChart').getContext('2d');
        const validColor = '{{ $isTarqeem ? "#10b981" : "#4eb535" }}';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['الإقامات', 'العقود الوظيفية', 'التراخيص والشهادات'],
                datasets: [
                    {
                        label: 'ساري (Valid)',
                        data: [{{ $iqamasValid }}, {{ $contractsValid }}, {{ $licensesValid }}],
                        backgroundColor: validColor,
                        borderRadius: 6
                    },
                    {
                        label: 'قريب الانتهاء (Near)',
                        data: [{{ $iqamasNear }}, {{ $contractsNear }}, {{ $licensesNear }}],
                        backgroundColor: '#f59e0b',
                        borderRadius: 6
                    },
                    {
                        label: 'منتهي (Expired)',
                        data: [{{ $iqamasExpired }}, {{ $contractsExpired }}, {{ $licensesExpired }}],
                        backgroundColor: '#ef4444',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            color: '#cbd5e1',
                            font: { family: 'Cairo', weight: 'bold', size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        borderColor: '#334155',
                        borderWidth: 1,
                        rtl: true
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', font: { family: 'Cairo', weight: 'bold' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { stepSize: 1, color: '#94a3b8', font: { family: 'Cairo' } }
                    }
                }
            }
        });
    });
</script>
@endpush