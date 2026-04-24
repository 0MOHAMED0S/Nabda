<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="{
    darkMode: localStorage.getItem('dark') === 'true',
    sidebarOpen: false
}" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | نبضة خير</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo/logo.webp') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        brand: { 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9' }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }

        .sidebar-item.active {
            background: linear-gradient(90deg, rgba(124, 58, 237, 0.1) 0%, rgba(124, 58, 237, 0) 100%);
            border-right: 4px solid #7c3aed;
            color: #7c3aed;
        }
        .dark .sidebar-item.active {
            background: linear-gradient(90deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0) 100%);
            border-right-color: #8b5cf6;
            color: #ddd6fe;
        }

        /* أنيميشن شريط المؤقت لرسائل الفلاش */
        @keyframes shrink-timer {
            from { width: 100%; }
            to { width: 0%; }
        }
        .animate-timer {
            animation: shrink-timer 5s linear forwards;
        }
    </style>
</head>

<body class="bg-[#f8fafc] dark:bg-dark-900 text-slate-900 dark:text-slate-100 min-h-screen transition-colors duration-300">

    <div x-data="{
        show: false,
        message: '',
        type: 'success',
        init() {
            @if (session('success')) this.showToast('{{ session('success') }}', 'success');
            @elseif(session('error')) this.showToast('{{ session('error') }}', 'error');
            @elseif($errors->any()) this.showToast('{{ $errors->first() }}', 'error'); @endif
        },
        showToast(msg, type) {
            this.message = msg;
            this.type = type;
            this.show = true;
            setTimeout(() => { this.show = false }, 5000);
        }
    }" x-show="show" x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-x-12"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-12"
        class="fixed top-6 right-6 z-[9999] w-full max-w-[340px] pointer-events-none">

        <div class="pointer-events-auto relative overflow-hidden bg-white dark:bg-dark-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700/50 p-4 flex items-start gap-4">

            <div :class="type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-sm">
                <i class="fa-solid" :class="type === 'success' ? 'fa-circle-check text-lg' : 'fa-circle-exclamation text-lg'"></i>
            </div>

            <div class="flex-1 text-right pt-0.5">
                <h4 class="text-[10px] font-black uppercase tracking-widest mb-0.5"
                    :class="type === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                    x-text="type === 'success' ? 'نجاح العملية' : 'تنبيه'"></h4>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200 leading-relaxed" x-text="message"></p>
            </div>

            <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition-colors mt-1">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="absolute bottom-0 left-0 w-full h-1 bg-slate-100 dark:bg-slate-700/50">
                <div class="h-full animate-timer" :class="type === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"></div>
            </div>
        </div>
    </div>

    <div class="flex min-h-screen">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-sm"></div>

        <aside id="main-sidebar" :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
            class="fixed lg:sticky top-0 right-0 w-72 bg-white dark:bg-dark-800 border-l border-slate-200 dark:border-slate-700/50 h-screen z-50 flex flex-col shadow-xl transition-transform duration-300">

            <div class="p-6 mb-4">
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                        <img src="{{ asset('logo/logo.webp') }}" alt="نبضة خير" class="h-12 w-auto object-contain transition-transform group-hover:scale-105">
                        <span class="text-lg font-black tracking-tight text-slate-800 dark:text-white">نبضة خير</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-rose-500">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto pb-8 scroll-smooth" id="sidebar-nav">
                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-black px-4 mb-4 mt-2">إدارة المنصة</p>

@php
                    $navItems = [
                        ['is_group' => false, 'route' => 'admin.dashboard', 'icon' => 'fa-grid-2', 'label' => 'الرئيسية والإحصائيات'],
                        ['is_group' => false, 'route' => 'admin.profile', 'icon' => 'fa-user-circle', 'label' => 'إعدادات الحساب'],

                        ['is_group' => true, 'label' => 'محتوى الواجهة', 'icon' => 'fa-clapperboard', 'active_routes' => ['admin.hero.edit', 'admin.tickers.index'], 'children' => [
                            ['route' => 'admin.hero.edit', 'label' => 'القسم الرئيسي (Hero)'],
                            ['route' => 'admin.tickers.index', 'label' => 'الشريط الإخباري'],
                        ]],

                        ['is_group' => true, 'label' => 'عن المنصة', 'icon' => 'fa-circle-info', 'active_routes' => ['admin.about_us.index', 'admin.about_visions.index', 'admin.about_goals1.index', 'admin.about_goals2.index', 'admin.about_histories.index', 'admin.team.index'], 'children' => [
                            ['route' => 'admin.about_us.index', 'label' => 'من نحن'],
                            ['route' => 'admin.about_visions.index', 'label' => 'الرؤية والمهمة'],
                            ['route' => 'admin.about_goals1.index', 'label' => 'الأهداف (1)'],
                            ['route' => 'admin.about_goals2.index', 'label' => 'الأهداف (2)'],
                            ['route' => 'admin.about_histories.index', 'label' => 'تاريخنا'],
                            ['route' => 'admin.team.index', 'label' => 'فريق العمل'],
                        ]],

                        // -------- المجموعة الجديدة: إدارة المؤسسات --------
                        ['is_group' => true, 'label' => 'إدارة المؤسسات', 'icon' => 'fa-building-columns', 'active_routes' => ['admin.foundations.index', 'admin.foundations.approved'], 'children' => [
                            ['route' => 'admin.foundations.index', 'label' => 'طلبات التسجيل والمراجعة'],
                            ['route' => 'admin.foundations.approved', 'label' => 'المؤسسات المعتمدة'], // الرابط الجديد
                        ]],
                        // ---------------------------------------------------

                        ['is_group' => true, 'label' => 'المشاريع والخدمات', 'icon' => 'fa-hand-holding-heart', 'active_routes' => ['admin.categories.index', 'admin.services.index'], 'children' => [
                            ['route' => 'admin.categories.index', 'label' => 'تصنيفات الخدمات'],
                            ['route' => 'admin.services.index', 'label' => 'قائمة الخدمات'],
                        ]],

                        ['is_group' => false, 'route' => 'admin.articles.index', 'icon' => 'fa-newspaper', 'label' => 'المركز الإعلامي'],
                        ['is_group' => false, 'route' => 'admin.zakat.index', 'icon' => 'fa-calculator', 'label' => 'شروط الزكاة'],
                        ['is_group' => false, 'route' => 'admin.faqs.index', 'icon' => 'fa-question-circle', 'label' => 'الأسئلة الشائعة'],

                        ['is_group' => true, 'label' => 'بيانات التواصل', 'icon' => 'fa-address-book', 'active_routes' => ['admin.contact_info.edit', 'admin.branches.index', 'admin.reviews.index'], 'children' => [
                            ['route' => 'admin.contact_info.edit', 'label' => 'معلومات الاتصال'],
                            ['route' => 'admin.branches.index', 'label' => 'الفروع والمواقع'],
                            ['route' => 'admin.reviews.index', 'label' => 'آراء المبدعين'],
                        ]],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @if ($item['is_group'])
                        @php $isGroupActive = in_array(request()->route()->getName(), $item['active_routes']); @endphp
                        <div x-data="{ open: {{ $isGroupActive ? 'true' : 'false' }} }" class="mb-1">
                            <button @click="open = !open" :class="open ? 'bg-slate-50 dark:bg-dark-700/40 text-brand-600' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-dark-700/30'"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all group">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid {{ $item['icon'] }} w-5 text-center"></i>
                                    <span class="font-bold text-sm">{{ $item['label'] }}</span>
                                </div>
                                <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300" :class="open ? '-rotate-90' : ''"></i>
                            </button>
                            <div x-show="open" x-collapse>
                                <div class="mt-1 mr-4 pr-3 border-r-2 border-slate-100 dark:border-slate-700 flex flex-col gap-1 py-1">
                                    @foreach ($item['children'] as $child)
                                        @php $isChildActive = request()->routeIs($child['route']); @endphp
                                        <a href="{{ route($child['route']) }}" id="{{ $isChildActive ? 'active-link' : '' }}"
                                            class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm transition-all {{ $isChildActive ? 'text-brand-600 font-bold bg-brand-50/50 dark:bg-brand-500/10' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        @php $isActive = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}" id="{{ $isActive ? 'active-link' : '' }}"
                            class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl transition-all {{ $isActive ? 'active font-bold' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-dark-700/30' }}">
                            <i class="fa-solid {{ $item['icon'] }} w-5 text-center"></i>
                            <span class="text-sm">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="p-4 border-t border-slate-100 dark:border-slate-700/50">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all font-bold text-sm group">
                        <i class="fa-solid fa-right-from-bracket group-hover:-translate-x-1 transition-transform"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            <header class="h-20 bg-white/80 dark:bg-dark-800/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/50 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-40">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-500"><i class="fa-solid fa-bars-staggered text-xl"></i></button>
                    <button @click="darkMode = !darkMode; localStorage.setItem('dark', darkMode)" class="p-2.5 rounded-xl bg-slate-100 dark:bg-dark-700 text-slate-600 dark:text-amber-400 transition-all">
                        <i class="fa-solid" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.profile') }}" class="flex items-center gap-4 group cursor-pointer">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-800 dark:text-white leading-none mb-1 group-hover:text-brand-600 transition-colors">{{ auth('admin')->user()->name ?? 'مدير النظام' }}</p>
                            <span class="text-[10px] text-brand-600 font-bold uppercase tracking-tighter">إعدادات الحساب</span>
                        </div>
                        <img src="https://ui-avatars.com/api/?name={{ auth('admin')->user()->name ?? 'Admin' }}&background=7c3aed&color=fff&bold=true" class="w-10 h-10 rounded-xl border-2 border-white dark:border-slate-700 shadow-sm group-hover:border-brand-500 transition-all">
                    </a>
                </div>
            </header>

            <div class="p-4 sm:p-8 lg:p-10">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        // Smart Focus: التمرير للعنصر النشط عند تحميل الصفحة
        window.onload = function() {
            const activeLink = document.getElementById('active-link');
            if (activeLink) {
                // الانتظار قليلاً لضمان تحميل Alpine.js وفتح القوائم المنسدلة إن وجدت
                setTimeout(() => {
                    activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        };
    </script>
</body>
</html>
