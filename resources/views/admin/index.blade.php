@extends('admin.layouts.master')
@section('title', 'لوحة التحكم الرئيسية')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ loading: false }">

    <!-- 🌟 الترحيب والتاريخ والفلاتر -->
    <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 mb-2">
                مرحباً بك مجدداً 👋
            </h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium">إليك ملخص الإحصائيات والأرقام في المنصة بناءً على النطاق الزمني المختار.</p>
            <div class="h-1.5 bg-brand-600 w-16 mt-4 rounded-full"></div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <span class="text-xs font-bold bg-white dark:bg-dark-800 border border-slate-100 dark:border-slate-700 shadow-sm text-slate-500 dark:text-slate-300 px-5 py-3 rounded-2xl flex items-center justify-center gap-2">
                <i class="fa-solid fa-calendar-day text-brand-500"></i> {{ now()->locale('ar')->translatedFormat('d F Y') }}
            </span>

            <form method="GET" action="{{ route('admin.dashboard') }}" class="relative w-full sm:w-48" @submit="loading = true">
                <select name="range" onchange="this.form.submit(); loading = true"
                    class="w-full appearance-none bg-brand-50 dark:bg-brand-900/30 border border-brand-100 dark:border-brand-800 text-brand-700 dark:text-brand-400 text-xs font-black px-5 py-3 pr-10 rounded-2xl cursor-pointer outline-none transition-all hover:bg-brand-100 dark:hover:bg-brand-900/50 shadow-sm">
                    <option value="all" {{ ($range ?? 'all') === 'all' ? 'selected' : '' }}>كل الأوقات 📊</option>
                    <option value="today" {{ ($range ?? '') === 'today' ? 'selected' : '' }}>اليوم فقط ⏳</option>
                    <option value="week" {{ ($range ?? '') === 'week' ? 'selected' : '' }}>هذا الأسبوع 📅</option>
                    <option value="month" {{ ($range ?? '') === 'month' ? 'selected' : '' }}>هذا الشهر 🗓️</option>
                    <option value="year" {{ ($range ?? '') === 'year' ? 'selected' : '' }}>هذا العام 📆</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-brand-500">
                    <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-chevron-down'"></i>
                </div>
            </form>
        </div>
    </div>

    <!-- 💰 القسم المالي والتبرعات بأنواعها -->
    <h3 class="text-lg font-black text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-chart-pie text-emerald-500"></i> ملخص التبرعات
        <span class="text-[10px] font-bold bg-slate-100 dark:bg-dark-800 text-slate-400 px-2 py-1 rounded-lg">حسب الفلتر</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
        <!-- إجمالي المبالغ (يأخذ مساحة عمودين في الشاشات الكبيرة) -->
        <div class="md:col-span-2 bg-gradient-to-br from-emerald-500 to-emerald-700 p-8 rounded-[2rem] shadow-lg shadow-emerald-500/20 text-white relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
            <i class="fa-solid fa-sack-dollar absolute -left-4 -bottom-4 text-9xl opacity-10 group-hover:scale-110 transition-transform duration-500"></i>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-white mb-4">
                        <i class="fa-solid fa-coins text-xl"></i>
                    </div>
                    <p class="text-emerald-100 text-sm font-bold mb-1">إجمالي التبرعات المالية المكتملة</p>
                    <h4 class="text-4xl md:text-5xl font-black">{{ number_format($stats['financial_amount'] ?? 0, 0) }} <span class="text-lg font-bold text-emerald-200">ج.م</span></h4>
                </div>
                <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-4 text-center min-w-[120px]">
                    <span class="block text-emerald-100 text-xs font-bold mb-1">عدد العمليات</span>
                    <span class="text-2xl font-black">{{ number_format($stats['financial_count'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- التبرعات العينية -->
        <div class="bg-white dark:bg-dark-800 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm group hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-center">
            <div class="absolute top-0 right-0 w-2 h-full bg-amber-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center text-amber-500 mb-4 group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-box-open text-xl"></i>
            </div>
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-1">التبرعات العينية (أشياء)</p>
            <h4 class="text-3xl font-black text-slate-800 dark:text-white">{{ number_format($stats['inkind_count'] ?? 0) }} <span class="text-sm font-bold text-slate-400">عملية</span></h4>
        </div>
    </div>

    <!-- 👥 قسم المستخدمين والمؤسسات والمتطوعين -->
    <h3 class="text-lg font-black text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2 mt-12">
        <i class="fa-solid fa-users-gear text-brand-500"></i> نمو المجتمع
        <span class="text-[10px] font-bold bg-slate-100 dark:bg-dark-800 text-slate-400 px-2 py-1 rounded-lg">حسب الفلتر</span>
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
        <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group hover:border-blue-200 dark:hover:border-blue-900/50">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="flex-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">تسجيل مستخدمين</span>
                <span class="text-2xl font-black text-slate-800 dark:text-white">+{{ number_format($stats['users_total'] ?? 0) }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group hover:border-purple-200 dark:hover:border-purple-900/50">
            <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shrink-0">
                <i class="fa-solid fa-building-circle-check"></i>
            </div>
            <div class="flex-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">مؤسسات معتمدة</span>
                <span class="text-2xl font-black text-slate-800 dark:text-white">+{{ number_format($stats['foundations_approved'] ?? 0) }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group hover:border-sky-200 dark:hover:border-sky-900/50">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 dark:bg-sky-900/30 text-sky-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shrink-0">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <div class="flex-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">متطوعين معتمدين</span>
                <span class="text-2xl font-black text-slate-800 dark:text-white">+{{ number_format($stats['volunteers_approved'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <!-- ⚠️ التنبيهات (المهام المعلقة) -->
    @if(($stats['foundations_pending'] ?? 0) > 0 || ($stats['volunteers_pending'] ?? 0) > 0 || ($stats['donations_pending'] ?? 0) > 0)
    <div class="mt-12 pt-10 border-t border-slate-100 dark:border-slate-800">
        <h3 class="text-lg font-black text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 animate-pulse"></i> مهام تشغيلية معلقة
            <span class="text-[10px] font-bold bg-rose-50 dark:bg-rose-900/20 text-rose-600 px-2 py-1 rounded-lg">عاجل</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-right">

            <!-- تنبيه التبرعات المعلقة -->
            @if(($stats['donations_pending'] ?? 0) > 0)
            <a href="#" class="block bg-rose-50 dark:bg-rose-900/10 p-6 rounded-[2rem] border border-rose-200 dark:border-rose-900/50 shadow-sm transition-all hover:bg-rose-100 dark:hover:bg-rose-900/20 group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-xl shadow-inner font-black">
                            {{ $stats['donations_pending'] }}
                        </div>
                        <div>
                            <h4 class="font-black text-rose-900 dark:text-rose-500 text-lg">تبرعات معلقة</h4>
                            <p class="text-xs font-bold text-rose-600 dark:text-rose-600/70 mt-1">بانتظار التأكيد أو الاستلام</p>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if(($stats['foundations_pending'] ?? 0) > 0)
            <a href="{{ route('admin.foundations.index') }}" class="block bg-amber-50 dark:bg-amber-900/10 p-6 rounded-[2rem] border border-amber-200 dark:border-amber-900/50 shadow-sm transition-all hover:bg-amber-100 dark:hover:bg-amber-900/20 group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-inner font-black">
                            {{ $stats['foundations_pending'] }}
                        </div>
                        <div>
                            <h4 class="font-black text-amber-900 dark:text-amber-500 text-lg">طلبات مؤسسات جديدة</h4>
                            <p class="text-xs font-bold text-amber-600 dark:text-amber-600/70 mt-1">تنتظر المراجعة والاعتماد</p>
                        </div>
                    </div>
                </div>
            </a>
            @endif

            @if(($stats['volunteers_pending'] ?? 0) > 0)
            <a href="{{ route('admin.volunteers.index') }}" class="block bg-amber-50 dark:bg-amber-900/10 p-6 rounded-[2rem] border border-amber-200 dark:border-amber-900/50 shadow-sm transition-all hover:bg-amber-100 dark:hover:bg-amber-900/20 group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-inner font-black">
                            {{ $stats['volunteers_pending'] }}
                        </div>
                        <div>
                            <h4 class="font-black text-amber-900 dark:text-amber-500 text-lg">طلبات تطوع معلقة</h4>
                            <p class="text-xs font-bold text-amber-600 dark:text-amber-600/70 mt-1">بانتظار تفعيل الحسابات</p>
                        </div>
                    </div>
                </div>
            </a>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
