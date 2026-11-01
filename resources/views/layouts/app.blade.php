<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'نظام المتابعة | ترقيم & تفنين')</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'Segoe UI', 'Tahoma', 'sans-serif'] },
                    colors: {
                        trqemNavy: '#151e48',
                        trqemDeep: '#080d1e',
                        trqemSurface: '#0e1736',
                        trqemCyan: '#4dc3f7',
                        tafneenDark: '#060910',
                        tafneenSurface: '#0d1424',
                        tafneenBlue: '#1d4ed8',
                        tafneenRoyal: '#2563eb',
                        tafneenGreen: '#4eb535',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style> 
        body { font-family: 'Cairo', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0b1120; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
@php
    $currentOrg = \App\Models\Organization::find(session('current_organization_id', 1)) ?? \App\Models\Organization::first();
    $allOrgs = \App\Models\Organization::where('is_active', true)->get();
    $isTarqeem = ($currentOrg?->code === 'tarqeem');
@endphp
<body class="{{ $isTarqeem ? 'bg-[#070d1e]' : 'bg-[#060910]' }} text-slate-100 h-full flex flex-col selection:bg-cyan-500 selection:text-slate-950 transition-colors duration-300">

    <!-- شريط التنقل العلوي بالوضع الليلي -->
    <header class="{{ $isTarqeem ? 'bg-gradient-to-b from-[#0a1128] to-[#070d1e] border-b border-[#1b2b5a]' : 'bg-gradient-to-b from-[#0a0f1c] to-[#060910] border-b border-[#1c2942]' }} shadow-2xl sticky top-0 z-50 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- شعار وهوية النظام -->
                <div class="flex items-center space-x-3.5 space-x-reverse">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 space-x-reverse group">
                        <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-tr from-cyan-500 via-blue-600 to-indigo-600 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg ring-2 ring-cyan-400/30 group-hover:scale-105 transition-transform">
                            م
                            <span class="absolute -bottom-1 -left-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <span class="text-lg font-black tracking-tight text-white block">نظام المتابعة</span>
                                <span class="text-[10px] bg-slate-800 text-cyan-300 border border-cyan-500/30 px-2 py-0.5 rounded-full font-bold">🌙 الوضع الليلي</span>
                            </div>
                            <span class="text-xs text-slate-400 block -mt-0.5">متابعة العقود • الإقامات • التراخيص والشهادات</span>
                        </div>
                    </a>

                    <!-- روابط التبويب -->
                    <nav class="hidden md:flex items-center space-x-1 space-x-reverse mr-6 bg-slate-950/60 p-1.5 rounded-xl border border-slate-800/80">
                        <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ request()->routeIs('dashboard') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            📊 لوحة المؤشرات
                        </a>
                        <a href="{{ route('iqamas.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('iqamas.*') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            🪪 الإقامات
                        </a>
                        <a href="{{ route('contracts.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('contracts.*') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            📑 العقود الوظيفية
                        </a>
                        <a href="{{ route('licenses.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('licenses.*') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            📜 التراخيص والشهادات
                        </a>
                        <a href="{{ route('imports.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('imports.*') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            📥 استيراد Excel
                        </a>
                        <a href="{{ route('settings.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ request()->routeIs('settings.*') ? ($isTarqeem ? 'bg-[#151e48] text-cyan-400 border border-cyan-500/40 shadow-sm' : 'bg-[#101b33] text-[#4eb535] border border-[#4eb535]/50 shadow-sm') : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                            ⚙️ الإعدادات والتنبيهات
                        </a>
                    </nav>
                </div>

                <!-- أزرار هويتي ترقيم وتفنين الرسمية طبق الأصل المتكيفة مع الوضع الليلي -->
                <div class="flex items-center space-x-2 space-x-reverse">
                    @foreach($allOrgs as $org)
                    <form action="{{ route('organizations.switch', $org->id) }}" method="POST">
                        @csrf
                        @if($org->code === 'tarqeem')
                        <!-- هوية ترقيم TRQEM الرسمية المظلمة -->
                        <button type="submit" class="flex items-center space-x-2.5 space-x-reverse px-3.5 py-1.5 rounded-xl transition border {{ $currentOrg?->code === 'tarqeem' ? 'border-[#4dc3f7] shadow-lg bg-[#151e48] ring-2 ring-[#4dc3f7]/50 shadow-[0_0_15px_rgba(77,195,247,0.35)]' : 'border-slate-800 bg-slate-900/80 hover:bg-slate-800 text-slate-400' }}">
                            <div class="flex flex-col justify-between w-3.5 h-3.5">
                                <div class="w-2 h-1.5 bg-[#4dc3f7] rounded-[1px] shadow-[0_0_6px_#4dc3f7]"></div>
                                <div class="w-2 h-1.5 bg-[#4dc3f7] rounded-[1px] mr-auto shadow-[0_0_6px_#4dc3f7]"></div>
                            </div>
                            <div class="flex flex-col text-right leading-none">
                                <span class="text-[9px] font-mono tracking-wider font-extrabold text-[#4dc3f7]">TRQEM</span>
                                <span class="text-xs font-black text-white mt-0.5">تـرقـيـم</span>
                            </div>
                        </button>
                        @else
                        <!-- هوية تفنين TAFNEEN الرسمية المظلمة -->
                        <button type="submit" class="flex items-center space-x-2.5 space-x-reverse px-3.5 py-1.5 rounded-xl transition border {{ $currentOrg?->code === 'tafneen' ? 'border-[#4eb535] shadow-lg bg-[#0f172a] ring-2 ring-[#4eb535]/50 shadow-[0_0_15px_rgba(78,181,53,0.35)]' : 'border-slate-800 bg-slate-900/80 hover:bg-slate-800 text-slate-400' }}">
                            <div class="w-2.5 h-2.5 rounded-full bg-[#4eb535] shadow-[0_0_6px_#4eb535]"></div>
                            <div class="flex flex-col text-right leading-none">
                                <span class="text-[9px] font-mono tracking-wider font-extrabold text-[#4eb535]">TAFNEEN</span>
                                <span class="text-xs font-black text-white mt-0.5">تَفْنِين</span>
                            </div>
                        </button>
                        @endif
                    </form>
                    @endforeach
                </div>

            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
        <div class="mb-6 bg-slate-900 border border-cyan-500/40 text-cyan-300 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xl backdrop-blur-md" role="alert">
            <div class="flex items-center space-x-2.5 space-x-reverse">
                <span class="text-cyan-400 text-lg font-black">✓</span>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="{{ $isTarqeem ? 'bg-[#0a1128] border-t border-[#1b2b5a]' : 'bg-[#0a0f1c] border-t border-[#1c2942]' }} py-6 text-center text-xs text-slate-400 mt-auto">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-2 space-x-reverse">
                <span class="font-black text-white">نظام المتابعة</span>
                <span class="text-slate-600">•</span>
                <span>الوضع الليلي الفاخر (V13 Engine) &copy; {{ date('Y') }}</span>
            </div>
            <div class="flex items-center space-x-3 space-x-reverse text-slate-500 font-mono text-[11px]">
                <span>مؤسسة ترقيم TRQEM</span>
                <span>•</span>
                <span>مؤسسة تفنين TAFNEEN</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>