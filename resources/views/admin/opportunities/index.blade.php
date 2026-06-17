@extends('admin.layouts.master')
@section('title', 'إدارة الفرص التطوعية')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    showModal: false,
    selectedOpp: null,
    loading: false,

    openModal(oppData) {
        this.selectedOpp = JSON.parse(JSON.stringify(oppData));
        this.showModal = true;
    },

    getStatusBadge(status) {
        if(status === 'open') return '<span class=\'text-emerald-600 bg-emerald-50 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>متاحة للتسجيل 🟢</span>';
        if(status === 'closed') return '<span class=\'text-rose-600 bg-rose-50 border border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>مغلقة 🔒</span>';
        if(status === 'completed') return '<span class=\'text-blue-600 bg-blue-50 border border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>مكتملة ✅</span>';
        if(status === 'cancelled') return '<span class=\'text-slate-600 bg-slate-100 border border-slate-200 dark:bg-slate-500/10 dark:border-slate-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>ملغاة ❌</span>';
        return '<span class=\'text-amber-600 bg-amber-50 border border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>قيد المراجعة ⏳</span>';
    }
}">

    <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 mb-2">
                متابعة الفرص التطوعية
                <span class="text-white text-xs font-bold bg-brand-500 px-3 py-1 rounded-xl shadow-sm">{{ $opportunities->total() }} فرصة نشطة</span>
            </h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">إدارة كافة المبادرات التطوعية، متابعة أعداد المسجلين، وعرض التفاصيل بدقة.</p>
            <div class="h-1.5 bg-brand-600 w-16 mt-4 rounded-full"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <form action="{{ route('admin.opportunities.index') }}" method="GET" @submit="loading = true">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

                <div class="relative lg:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">البحث السريع</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بعنوان الفرصة، الموقع..." class="w-full px-5 py-3 pr-10 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-10 text-slate-400"></i>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">المؤسسة المنظمة</label>
                    <select name="foundation_id" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">كل المؤسسات</option>
                        @foreach($foundations as $foundation)
                            <option value="{{ $foundation->id }}" {{ request('foundation_id') == $foundation->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($foundation->name, 25) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">المجال / التصنيف</label>
                    <input type="text" name="category" value="{{ request('category') }}" placeholder="مثال: تعليم، طبي، بيئي..." class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">حالة الفرصة</label>
                    <select name="status" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">كل الحالات</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>متاحة للتسجيل 🟢</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>مغلقة 🔒</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة ✅</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/50">
                @if(request()->hasAny(['search', 'foundation_id', 'category', 'status']) && request()->except('page'))
                    <a href="{{ route('admin.opportunities.index') }}" class="text-xs font-bold text-slate-400 hover:text-rose-500 transition-colors px-4 py-2">
                        <i class="fa-solid fa-rotate-left"></i> مسح الفلاتر
                    </a>
                @endif
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-black px-8 py-3 rounded-2xl transition-all shadow-lg shadow-brand-500/20 flex items-center justify-center gap-2">
                    <span x-show="!loading"><i class="fa-solid fa-filter"></i> تطبيق הפلتر</span>
                    <span x-show="loading" x-cloak><i class="fa-solid fa-circle-notch fa-spin"></i> جاري البحث...</span>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full mb-10">
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6">تفاصيل الفرصة</th>
                        <th class="px-6 py-6">المؤسسة المنظمة</th>
                        <th class="px-6 py-6 text-center">الموقع والتاريخ</th>
                        <th class="px-6 py-6 w-48 text-center">مؤشر الإقبال (المتطوعين)</th>
                        <th class="px-6 py-6 text-center">الحالة</th>
                        <th class="px-6 py-6 text-center">التفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($opportunities as $opp)
                        <tr class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-1 mb-1">
                                    <h5 class="text-sm font-black text-slate-800 dark:text-white" title="{{ $opp->title }}">{{ \Illuminate\Support\Str::limit($opp->title, 40) }}</h5>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded flex items-center gap-1">
                                        <i class="fa-solid fa-hashtag text-[8px]"></i> {{ $opp->category ?? 'عام' }}
                                    </span>
                                    <span class="text-[10px] font-bold text-brand-600 bg-brand-50 dark:bg-brand-900/30 px-2 py-1 rounded flex items-center gap-1">
                                        <i class="fa-regular fa-clock text-[8px]"></i> {{ $opp->total_hours }} ساعة
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center shadow-sm shrink-0">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-black text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($opp->foundation->name ?? 'غير محدد', 25) }}</span>
                                        <span class="text-[10px] font-bold text-slate-400"><i class="fa-solid fa-user-tie text-[9px] mr-0.5"></i> {{ $opp->contact_person ?? 'الإدارة' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-black text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-3 py-1.5 rounded-lg block w-max mx-auto mb-1">
                                    <i class="fa-solid fa-location-dot"></i> {{ \Illuminate\Support\Str::limit($opp->location, 15) }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 flex items-center justify-center gap-1" dir="ltr">
                                    <i class="fa-regular fa-calendar-days text-[9px]"></i> {{ $opp->date ? $opp->date->format('Y-m-d') : 'مستمر' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex justify-between text-[10px] font-black text-slate-500 mb-2 uppercase tracking-wide">
                                    <span>{{ $opp->calculated_percentage }}%</span>
                                    <span class="text-brand-600">{{ $opp->accepted_volunteers_count }} / {{ $opp->required_volunteers }} مسجل</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-dark-900 rounded-full h-2.5 overflow-hidden shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-1000 {{ $opp->calculated_percentage >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-brand-400 to-brand-600' }}" style="width: {{ $opp->calculated_percentage }}%"></div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div x-html="getStatusBadge('{{ $opp->status ?? 'open' }}')"></div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <button @click="openModal(@js($opp))" class="w-10 h-10 rounded-xl bg-white dark:bg-dark-800 text-slate-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-all shadow-sm mx-auto group">
                                    <i class="fa-solid fa-eye group-hover:scale-110 transition-transform"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                                    <i class="fa-solid fa-hands-holding-circle"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">لا توجد فرص تطوعية!</h4>
                                <p class="text-sm text-slate-400 mt-1">لم يتم العثور على أي مبادرات أو فرص تطابق معايير البحث الحالية.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($opportunities->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $opportunities->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
            <div @click.away="showModal = false"
                 x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="bg-white dark:bg-dark-800 w-full max-w-6xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[95vh] border border-slate-100 dark:border-slate-700">

                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-start bg-slate-50/80 dark:bg-dark-900/50 shrink-0">
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white" x-text="selectedOpp?.title"></h3>
                            <div x-html="getStatusBadge(selectedOpp?.status || 'open')"></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-slate-500">
                            <span class="flex items-center gap-1 bg-white dark:bg-dark-800 px-2 py-1 rounded border border-slate-100 dark:border-slate-700"><i class="fa-solid fa-building text-brand-500"></i> <span x-text="selectedOpp?.foundation?.name"></span></span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot text-blue-500"></i> <span x-text="selectedOpp?.location"></span></span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-hashtag text-purple-500"></i> <span x-text="selectedOpp?.category || 'عام'"></span></span>
                        </div>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-dark-800 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all border border-slate-200 dark:border-slate-700 shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-8 overflow-y-auto flex-1 bg-white dark:bg-dark-800 custom-scrollbar">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <div class="lg:col-span-7 space-y-8">

                            <div>
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-file-lines text-brand-500"></i> وصف المبادرة التطوعية
                                </h4>
                                <div class="bg-slate-50 dark:bg-dark-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/50 text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium whitespace-pre-line" x-text="selectedOpp?.description || 'لا يوجد وصف متاح.'"></div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                                    <span class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">التاريخ والوقت</span>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-300">
                                            <i class="fa-regular fa-calendar-days text-blue-500 w-4 text-center"></i>
                                            <span x-text="selectedOpp?.date ? selectedOpp.date.substring(0,10) : 'غير محدد'"></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-300" dir="ltr">
                                            <span class="flex-1 text-right" x-text="(selectedOpp?.start_time ? selectedOpp.start_time.substring(0,5) : '') + ' - ' + (selectedOpp?.end_time ? selectedOpp.end_time.substring(0,5) : '')"></span>
                                            <i class="fa-regular fa-clock text-blue-500 w-4 text-center"></i>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm font-bold text-brand-600 mt-2 pt-2 border-t border-blue-200/50 dark:border-blue-800/30">
                                            <i class="fa-solid fa-stopwatch w-4 text-center"></i>
                                            <span x-text="'إجمالي ' + selectedOpp?.total_hours + ' ساعات عمل'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                                    <span class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">جهة التواصل والإشراف</span>
                                    <div class="space-y-2 text-sm font-bold text-blue-900 dark:text-blue-300">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user-tie text-blue-500 w-4 text-center"></i>
                                            <span x-text="selectedOpp?.contact_person || 'إدارة المؤسسة'"></span>
                                        </div>
                                        <div class="flex items-center gap-2" dir="ltr">
                                            <span class="flex-1 text-right" x-text="selectedOpp?.contact_phone || 'غير متوفر'"></span>
                                            <i class="fa-solid fa-phone text-blue-500 w-4 text-center"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <template x-if="selectedOpp?.requirements && Array.isArray(selectedOpp.requirements) && selectedOpp.requirements.length > 0">
                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                        <i class="fa-solid fa-clipboard-check text-amber-500"></i> الشروط والمتطلبات للانضمام
                                    </h4>
                                    <div class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-3xl border border-amber-100 dark:border-amber-900/30">
                                        <ul class="space-y-3">
                                            <template x-for="req in selectedOpp.requirements">
                                                <li class="flex items-start gap-2 text-sm font-bold text-amber-900 dark:text-amber-400">
                                                    <i class="fa-solid fa-check-circle text-amber-500 mt-1 shrink-0"></i> <span x-text="req"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </template>

                        </div>

                        <div class="lg:col-span-5 space-y-8">

                            <div class="bg-emerald-50 dark:bg-emerald-900/10 p-8 rounded-[2.5rem] border border-emerald-100 dark:border-emerald-900/30 text-center relative overflow-hidden">
                                <i class="fa-solid fa-users absolute -left-4 -bottom-8 text-8xl text-emerald-500/10 rotate-12"></i>
                                <h4 class="text-sm font-black text-emerald-800 dark:text-emerald-400 mb-5 relative z-10 uppercase tracking-widest">إقبال وتسجيل المتطوعين</h4>

                                <div class="flex justify-center items-end gap-2 mb-4 relative z-10">
                                    <span class="text-5xl font-black text-emerald-600 dark:text-emerald-500" x-text="selectedOpp?.calculated_percentage + '%'"></span>
                                </div>

                                <div class="w-full bg-white dark:bg-dark-800 rounded-full h-4 mb-6 overflow-hidden shadow-inner relative z-10 p-1">
                                    <div class="h-full rounded-full bg-gradient-to-l from-emerald-400 to-emerald-600 transition-all duration-1000 shadow-sm" :style="'width: ' + selectedOpp?.calculated_percentage + '%'"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 relative z-10">
                                    <div class="bg-white/60 dark:bg-dark-800/60 p-3 rounded-2xl border border-emerald-100 dark:border-emerald-900/50">
                                        <span class="block text-[10px] text-emerald-600/70 dark:text-emerald-400/70 font-black uppercase mb-1">العدد المطلوب</span>
                                        <span class="text-lg font-black text-emerald-800 dark:text-emerald-300" x-text="selectedOpp?.required_volunteers"></span>
                                    </div>
                                    <div class="bg-emerald-600 text-white p-3 rounded-2xl shadow-md shadow-emerald-600/20">
                                        <span class="block text-[10px] text-emerald-200 font-black uppercase mb-1">تم القبول</span>
                                        <span class="text-lg font-black" x-text="selectedOpp?.accepted_volunteers_count"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                                    <span class="flex items-center gap-2"><i class="fa-solid fa-address-card text-brand-500"></i> أحدث المتطوعين المقبولين</span>
                                    <span class="text-[10px] font-bold bg-brand-50 dark:bg-brand-900/30 text-brand-600 px-2 py-1 rounded">آخر 10</span>
                                </h4>

                                <template x-if="selectedOpp?.volunteers && selectedOpp.volunteers.length > 0">
                                    <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                        <template x-for="volunteer in selectedOpp.volunteers" :key="volunteer.id">
                                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-dark-900 rounded-2xl border border-slate-100 dark:border-slate-700 transition-all hover:shadow-sm">
                                                <div class="w-12 h-12 rounded-2xl bg-white dark:bg-dark-800 text-brand-500 flex items-center justify-center shrink-0 shadow-sm border border-slate-100 dark:border-slate-700">
                                                    <i class="fa-solid fa-user-astronaut"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h6 class="font-black text-sm text-slate-800 dark:text-slate-200 truncate" x-text="volunteer.name"></h6>
                                                    <span class="text-[11px] font-bold text-slate-400 truncate block mt-0.5" dir="ltr" x-text="volunteer.email"></span>
                                                </div>
                                                <div class="shrink-0 text-center">
                                                    <span class="block text-[10px] font-black text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-1 rounded mb-1" x-show="volunteer.pivot.status === 'attended'"><i class="fa-solid fa-check-double"></i> حضر النشاط</span>
                                                    <span class="block text-[10px] font-black text-blue-600 bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded mb-1" x-show="volunteer.pivot.status === 'accepted'">مقبول للمشاركة</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!selectedOpp?.volunteers || selectedOpp.volunteers.length === 0">
                                    <div class="text-center py-10 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-dashed border-slate-200 dark:border-slate-700">
                                        <div class="w-16 h-16 bg-white dark:bg-dark-800 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-slate-100 dark:border-slate-700 text-slate-300">
                                            <i class="fa-solid fa-users-slash text-2xl"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-500">لا يوجد متطوعين مقبولين في هذه الفرصة حتى الآن.</p>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #334155; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
@endsection
