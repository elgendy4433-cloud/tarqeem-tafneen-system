@extends('layouts.app')

@section('title', 'استيراد البيانات من Excel')

@php
    $currentOrg = \App\Models\Organization::find(session('current_organization_id', 1)) ?? \App\Models\Organization::first();
    $isTarqeem = ($currentOrg?->code === 'tarqeem');
    $cardBg = $isTarqeem ? 'bg-[#0e1736] border-[#1b2b5a]' : 'bg-[#0d1424] border-[#1a273f]';
@endphp

@section('content')
<div class="space-y-8">

    <!-- شريط العنوان -->
    <div class="pb-4 border-b border-slate-800">
        <h1 class="text-2xl font-black text-white">محرك استيراد ملفات Excel الذكي</h1>
        <p class="text-xs text-slate-400 mt-1">
            رفع ملفات المؤسستين، معالجة تواريخ الإكسل الرقمية (مثل 46438)، ومنع التكرار بالتحديث التلقائي (Upsert)
        </p>
    </div>

    <!-- بطاقات تحميل القوالب الجاهزة -->
    <div>
        <h2 class="text-sm font-bold text-white mb-3">📄 تحميل قوالب Excel الفارغة الجاهزة:</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('imports.template', 'iqamas') }}" class="flex items-center p-4 {{ $cardBg }} rounded-2xl border hover:border-cyan-400 transition group shadow-md">
                <span class="text-3xl ml-3">🪪</span>
                <div>
                    <span class="font-bold text-xs text-white group-hover:text-cyan-400 block">قالب الإقامات (.csv)</span>
                    <span class="text-[11px] text-slate-400">الاسم، رقم الإقامة، المهنة، الفرع، الانتهاء...</span>
                </div>
            </a>

            <a href="{{ route('imports.template', 'contracts') }}" class="flex items-center p-4 {{ $cardBg }} rounded-2xl border hover:border-indigo-400 transition group shadow-md">
                <span class="text-3xl ml-3">📑</span>
                <div>
                    <span class="font-bold text-xs text-white group-hover:text-indigo-400 block">قالب العقود الوظيفية (.csv)</span>
                    <span class="text-[11px] text-slate-400">الهوية، المهنة، الإدارة، تواريخ العقد، التجديد...</span>
                </div>
            </a>

            <a href="{{ route('imports.template', 'licenses') }}" class="flex items-center p-4 {{ $cardBg }} rounded-2xl border hover:border-teal-400 transition group shadow-md">
                <span class="text-3xl ml-3">📜</span>
                <div>
                    <span class="font-bold text-xs text-white group-hover:text-teal-400 block">قالب التراخيص والشهادات (.csv)</span>
                    <span class="text-[11px] text-slate-400">اسم الوثيقة، النوع، الرقم، الفرع، التواريخ...</span>
                </div>
            </a>
        </div>
    </div>

    <!-- تقرير نتيجة آخر عملية استيراد إن وجد -->
    @if(session('import_summary'))
    @php $summary = session('import_summary'); @endphp
    <div class="{{ $cardBg }} rounded-2xl p-6 border {{ $summary['failed'] > 0 ? 'border-amber-500/40' : 'border-emerald-500/40' }} shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-white text-sm">📊 ملخص نتائج عملية الاستيراد:</h3>
            <span class="text-xs bg-slate-900 text-slate-400 px-3 py-1 rounded-full font-mono border border-slate-800">سجل رقم #{{ $summary['log_id'] }}</span>
        </div>

        <div class="grid grid-cols-4 gap-3 text-center text-xs">
            <div class="bg-slate-900 p-3 rounded-xl border border-slate-800">
                <span class="block text-2xl font-black text-white">{{ $summary['total'] }}</span>
                <span class="text-[11px] text-slate-400 font-semibold">إجمالي الصفوف</span>
            </div>
            <div class="bg-emerald-950/40 p-3 rounded-xl border border-emerald-500/30">
                <span class="block text-2xl font-black text-emerald-400">{{ $summary['inserted'] }}</span>
                <span class="text-[11px] text-emerald-300 font-semibold">سجلات جديدة</span>
            </div>
            <div class="bg-blue-950/40 p-3 rounded-xl border border-blue-500/30">
                <span class="block text-2xl font-black text-blue-400">{{ $summary['updated'] }}</span>
                <span class="text-[11px] text-blue-300 font-semibold">سجلات محدثة</span>
            </div>
            <div class="bg-rose-950/40 p-3 rounded-xl border border-rose-500/30">
                <span class="block text-2xl font-black text-rose-400">{{ $summary['failed'] }}</span>
                <span class="text-[11px] text-rose-300 font-semibold">أخطاء/استثناء</span>
            </div>
        </div>

        @if(!empty($summary['errors']))
        <div class="bg-rose-950/30 p-4 rounded-xl border border-rose-500/40 text-xs text-rose-300">
            <h4 class="font-bold mb-2">قائمة التنبيهات والأخطاء أثناء المعالجة:</h4>
            <ul class="list-disc list-inside space-y-1 font-mono text-[11px]">
                @foreach($summary['errors'] as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- نموذج رفع الملف -->
    <div class="{{ $cardBg }} rounded-2xl p-6 border shadow-xl">
        <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- اختيار المؤسسة -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2">المؤسسة المراد استيراد البيانات إليها *</label>
                    <select name="organization_id" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-3 border focus:border-cyan-400">
                        @foreach(\App\Models\Organization::all() as $org)
                        <option value="{{ $org->id }}" {{ $currentOrg?->id == $org->id ? 'selected' : '' }}>
                            {{ $org->name }} ({{ strtoupper($org->code) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- اختيار الوحدة -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-2">نوع الوحدة والبيانات المستوردة *</label>
                    <select name="module_type" class="w-full text-xs rounded-xl bg-slate-900 border-slate-700 text-white p-3 border focus:border-cyan-400">
                        <option value="iqamas">🪪 الإقامات (تنبيهات &le; 45 يوم)</option>
                        <option value="contracts">📑 العقود الوظيفية (تنبيهات &le; 90 يوم)</option>
                        <option value="licenses">📜 التراخيص والشهادات</option>
                    </select>
                </div>
            </div>

            <!-- منطقة سحب وإفلات الملف -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-2">ملف Excel أو CSV *</label>
                <div class="border-2 border-dashed border-slate-700 hover:border-cyan-400 rounded-2xl p-8 text-center bg-slate-950/40 transition">
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full text-xs text-slate-400 file:ml-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-500 file:text-slate-950 hover:file:bg-cyan-400 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-2">الملفات المدعومة: .xlsx, .xls, .csv بحجم أقصى 10 ميجابايت</p>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="{{ $isTarqeem ? 'bg-gradient-to-r from-cyan-400 to-sky-600 text-slate-950 font-black shadow-[0_0_15px_rgba(77,195,247,0.3)]' : 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-bold border border-[#4eb535]/50 shadow-[0_0_15px_rgba(37,99,235,0.35)]' }} text-xs px-6 py-3 rounded-xl transition">
                    بدء استيراد ومعالجة الملف الآن 🚀
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
