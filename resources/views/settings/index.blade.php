@extends('layouts.app')

@section('title', 'إعدادات التنبيهات والبريد الإلكتروني')

@php
    $currentOrg = \App\Models\Organization::find(session('current_organization_id', 1)) ?? \App\Models\Organization::first();
    $isTarqeem = ($currentOrg?->code === 'tarqeem');
    $cardBg = $isTarqeem ? 'bg-[#0e1736] border-[#1b2b5a]' : 'bg-[#0d1424] border-[#1a273f]';
@endphp

@section('content')
<div class="space-y-8 max-w-4xl">

    <!-- شريط العنوان -->
    <div class="pb-4 border-b border-slate-800">
        <h1 class="text-2xl font-black text-white">إعدادات النظام والتنبيهات الفورية</h1>
        <p class="text-xs text-slate-400 mt-1">
            تخصيص معايير التنبيهات وقوائم البريد الإلكتروني لمؤسسة <span class="font-bold text-white">{{ $organization->name }}</span>
        </p>
    </div>

    <!-- نموذج حفظ الإعدادات -->
    <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl">
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- حالة التفعيل -->
            <div class="flex items-center justify-between pb-6 border-b border-slate-800">
                <div>
                    <span class="block font-bold text-sm text-white">تفعيل نظام التنبيهات الآلي</span>
                    <span class="text-xs text-slate-400">إرسال إيميلات التنبيه الفورية عند دخول السجلات في النطاق الحرج</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_alert_enabled" value="1" {{ $setting->is_alert_enabled ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- عتبات الأيام -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        حد تنبيه الإقامات بالأيام (افتراضي: 45) *
                    </label>
                    <div class="relative">
                        <input type="number" name="iqama_alert_threshold" value="{{ $setting->iqama_alert_threshold }}" required min="1" max="365" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-3 border focus:border-cyan-400">
                        <span class="absolute left-3 top-3 text-xs text-slate-400">يوم</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">يُرسل تنبيه فوري لأي إقامة يتبقى على انتهائها 45 يوماً أو أقل.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">
                        حد تنبيه العقود بالأيام (افتراضي: 90) *
                    </label>
                    <div class="relative">
                        <input type="number" name="contract_alert_threshold" value="{{ $setting->contract_alert_threshold }}" required min="1" max="365" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-3 border focus:border-cyan-400">
                        <span class="absolute left-3 top-3 text-xs text-slate-400">يوم</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">يُرسل تنبيه فوري لأي عقد عمل يتبقى على نهايته 90 يوماً أو أقل لاتخاذ إجراء التجديد أو الإنهاء.</p>
                </div>
            </div>

            <!-- قائمة البريد الإلكتروني -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">
                    قائمة البريد الإلكتروني المستلمة للتنبيهات (مفصولة بفواصل) *
                </label>
                <textarea name="notification_emails" rows="3" required class="w-full text-xs font-mono rounded-xl bg-slate-900 border-slate-700 text-white p-3 border focus:border-cyan-400" placeholder="hr@domain.sa, manager@domain.sa">{{ is_array($setting->notification_emails) ? implode(', ', $setting->notification_emails) : $setting->notification_emails }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">يمكنك إضافة أكثر من بريد إلكتروني، سيتم إرسال جداول السجلات المنتهية أو القريبة لهم فوراً.</p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                <button type="submit" class="{{ $isTarqeem ? 'bg-gradient-to-r from-cyan-400 to-sky-600 text-slate-950 font-black shadow-[0_0_15px_rgba(77,195,247,0.3)]' : 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-bold border border-[#4eb535]/50 shadow-[0_0_15px_rgba(37,99,235,0.35)]' }} text-xs font-bold px-6 py-3 rounded-xl transition">
                    حفظ التغييرات والإعدادات
                </button>
            </div>
        </form>
    </div>

    <!-- بطاقة الفحص اليدوي للتنبيهات -->
    <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl flex items-center justify-between">
        <div>
            <h3 class="font-bold text-white text-sm">⚡ فحص وتوليد التنبيهات الفورية يدوياً</h3>
            <p class="text-xs text-slate-400 mt-1">فحص كافة السجلات في قاعدة البيانات وإرسال التنبيهات للسجلات الحرجة دون انتظار مهمة الجدولة التلقائية.</p>
        </div>
        <form action="{{ route('settings.triggerAlerts') }}" method="POST">
            @csrf
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition">
                تشغيل الفحص الآن
            </button>
        </form>
    </div>

</div>
@endsection
