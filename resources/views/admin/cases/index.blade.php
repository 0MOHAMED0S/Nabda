@extends('admin.layouts.master')
@section('title', 'إدارة الحالات والمشاريع')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    showModal: false,
    selectedCase: null,
    loading: false,

    openModal(caseData) {
        this.selectedCase = JSON.parse(JSON.stringify(caseData));
        this.showModal = true;
    },

    getPriorityBadge(priority) {
        if(priority === 'emergency') return '<span class=\'text-rose-500 bg-rose-50 px-2 py-0.5 rounded text-[10px]\'>طوارئ 🔥</span>';
        if(priority === 'urgent') return '<span class=\'text-amber-500 bg-amber-50 px-2 py-0.5 rounded text-[10px]\'>عاجل ⚡</span>';
        return '<span class=\'text-blue-500 bg-blue-50 px-2 py-0.5 rounded text-[10px]\'>عادي 🟢</span>';
    }
}">

    <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 mb-2">
                متابعة حالات المؤسسات
                <span class="text-white text-xs font-bold bg-brand-500 px-3 py-1 rounded-xl shadow-sm">{{ $cases->total() }} حالة مسجلة</span>
            </h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">مراقبة تقدم المشاريع والحالات التابعة للمؤسسات لحظة بلحظة.</p>
            <div class="h-1.5 bg-brand-600 w-16 mt-4 rounded-full"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <form action="{{ route('admin.cases.index') }}" method="GET" @submit="loading = true">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

                <div class="relative lg:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">البحث</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم الحالة أو المستفيد..." class="w-full px-5 py-3 pr-10 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-10 text-slate-400"></i>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">المؤسسة</label>
                    <select name="foundation_id" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        @foreach($foundations as $foundation)
                            <option value="{{ $foundation->id }}" {{ request('foundation_id') == $foundation->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($foundation->name, 25) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">الاحتياج</label>
                    <select name="goal_type" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        <option value="financial" {{ request('goal_type') == 'financial' ? 'selected' : '' }}>مالي فقط</option>
                        <option value="in_kind" {{ request('goal_type') == 'in_kind' ? 'selected' : '' }}>عيني فقط</option>
                        <option value="both" {{ request('goal_type') == 'both' ? 'selected' : '' }}>مالي وعيني</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">حالة المشروع</label>
                    <select name="status" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشطة 🟢</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة 🔵</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة 🔴</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/50">
                @if(request()->hasAny(['search', 'foundation_id', 'goal_type', 'status']) && request()->except('page'))
                    <a href="{{ route('admin.cases.index') }}" class="text-xs font-bold text-slate-400 hover:text-rose-500 transition-colors px-4 py-2">
                        <i class="fa-solid fa-rotate-left"></i> إعادة ضبط
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
                        <th class="px-6 py-6">تفاصيل الحالة</th>
                        <th class="px-6 py-6">المؤسسة / التاريخ</th>
                        <th class="px-6 py-6">الاحتياج والهدف</th>
                        <th class="px-6 py-6 w-48 text-center">مؤشر الإنجاز</th>
                        <th class="px-6 py-6 text-center">الحالة</th>
                        <th class="px-6 py-6 text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($cases as $case)
                        <tr class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2 mb-1">
                                    <h5 class="text-sm font-black text-slate-800 dark:text-white">{{ \Illuminate\Support\Str::limit($case->title, 30) }}</h5>
                                    @if($case->priority === 'emergency')
                                        <span class="text-[10px] bg-rose-50 text-rose-500 px-2 py-0.5 rounded font-bold">طوارئ</span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-500 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded-md flex items-center gap-1 w-max">
                                    <i class="fa-solid fa-layer-group text-brand-500"></i> {{ $case->service->title ?? 'خدمة عامة' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-dark-900 text-slate-400 flex items-center justify-center shadow-inner">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-black text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($case->foundation->name ?? 'غير محدد', 20) }}</span>
                                        <span class="text-[10px] font-bold text-slate-400"><i class="fa-regular fa-clock"></i> {{ $case->created_at->format('Y-m-d') }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                @if(in_array($case->goal_type, ['financial', 'both']))
                                    <span class="text-xs font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 rounded-lg block w-max mb-1">
                                        <i class="fa-solid fa-money-bill-wave"></i> {{ number_format($case->target_amount) }} ج.م
                                    </span>
                                @else
                                    <span class="text-xs font-black text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-3 py-1.5 rounded-lg block w-max mb-1">
                                        <i class="fa-solid fa-box-open"></i> دعم عيني
                                    </span>
                                @endif
                                @if($case->goal_type === 'both')
                                    <span class="text-[10px] font-bold text-amber-500 flex items-center gap-1"><i class="fa-solid fa-plus text-[8px]"></i> يقبل التبرع العيني</span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex justify-between text-[10px] font-black text-slate-500 mb-2 uppercase tracking-wide">
                                    <span>{{ $case->calculated_percentage }}%</span>
                                    <span class="text-brand-600">{{ number_format($case->calculated_collected) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-dark-900 rounded-full h-2.5 overflow-hidden shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-1000 {{ $case->calculated_percentage >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-brand-400 to-brand-600' }}" style="width: {{ $case->calculated_percentage }}%"></div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($case->status === 'active')
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm">نشطة</span>
                                @elseif($case->status === 'completed')
                                    <span class="bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm">مكتملة</span>
                                @else
                                    <span class="bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm">ملغاة</span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                <button @click="openModal(@js($case))" class="w-10 h-10 rounded-xl bg-white dark:bg-dark-800 text-slate-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-all shadow-sm mx-auto group">
                                    <i class="fa-solid fa-eye group-hover:scale-110 transition-transform"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                                    <i class="fa-solid fa-folder-open"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">لا توجد نتائج!</h4>
                                <p class="text-sm text-slate-400 mt-1">لم يتم العثور على أي حالات تطابق الفلتر المختار.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cases->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $cases->links() }}
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
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white" x-text="selectedCase?.title"></h3>
                            <div x-html="getPriorityBadge(selectedCase?.priority)"></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-slate-500">
                            <span class="flex items-center gap-1"><i class="fa-solid fa-building text-slate-400"></i> <span x-text="selectedCase?.foundation?.name"></span></span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-layer-group text-slate-400"></i> <span x-text="selectedCase?.service?.title || 'عام'"></span></span>
                            <span class="flex items-center gap-1"><i class="fa-regular fa-calendar text-slate-400"></i> <span x-text="selectedCase?.created_at ? selectedCase.created_at.substring(0,10) : ''"></span></span>
                        </div>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-dark-800 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all border border-slate-200 dark:border-slate-700 shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-8 overflow-y-auto flex-1 bg-white dark:bg-dark-800">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                        <div class="lg:col-span-7 space-y-8">

                            <div>
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-file-lines text-brand-500"></i> وصف الحالة
                                </h4>
                                <div class="bg-slate-50 dark:bg-dark-900/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50 text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-medium" x-text="selectedCase?.main_description || 'لا يوجد وصف متاح.'"></div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                                    <span class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">اسم المستفيد</span>
                                    <span class="text-sm font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                                        <i class="fa-solid fa-user-injured text-blue-500"></i> <span x-text="selectedCase?.beneficiary_name || 'غير معلن'"></span>
                                    </span>
                                </div>
                                <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                                    <span class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">العمر</span>
                                    <span class="text-sm font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                                        <i class="fa-solid fa-cake-candles text-blue-500"></i> <span x-text="selectedCase?.beneficiary_age ? selectedCase.beneficiary_age + ' سنة' : 'غير محدد'"></span>
                                    </span>
                                </div>
                                <div class="sm:col-span-2 bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                                    <span class="block text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">العنوان / المنطقة</span>
                                    <span class="text-sm font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                                        <i class="fa-solid fa-location-dot text-blue-500"></i> <span x-text="selectedCase?.beneficiary_address || 'غير محدد'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-5 space-y-8">

                            <div class="bg-emerald-50 dark:bg-emerald-900/10 p-6 rounded-[2rem] border border-emerald-100 dark:border-emerald-900/30 text-center relative overflow-hidden">
                                <i class="fa-solid fa-chart-pie absolute -right-4 -bottom-4 text-7xl text-emerald-500/10"></i>
                                <h4 class="text-sm font-black text-emerald-700 dark:text-emerald-400 mb-5 relative z-10">مؤشر التقدم والجمع</h4>

                                <div class="flex justify-center items-end gap-2 mb-4 relative z-10">
                                    <span class="text-4xl font-black text-emerald-600 dark:text-emerald-500" x-text="selectedCase?.calculated_percentage + '%'"></span>
                                </div>

                                <div class="w-full bg-white dark:bg-dark-800 rounded-full h-3 mb-5 overflow-hidden shadow-inner relative z-10">
                                    <div class="h-full rounded-full bg-gradient-to-l from-emerald-400 to-emerald-600 transition-all duration-1000" :style="'width: ' + selectedCase?.calculated_percentage + '%'"></div>
                                </div>

                                <div class="flex justify-between text-xs font-bold text-emerald-700/70 dark:text-emerald-400/70 bg-white/50 dark:bg-dark-800/50 p-3 rounded-xl relative z-10">
                                    <div class="text-right">
                                        <span class="block text-[10px] uppercase">المطلوب</span>
                                        <span x-text="(selectedCase?.target_amount > 0 ? selectedCase?.target_amount + ' ج.م' : 'مفتوح')"></span>
                                    </div>
                                    <div class="text-left">
                                        <span class="block text-[10px] uppercase">تم الجمع</span>
                                        <span x-text="selectedCase?.calculated_collected + ' ج.م'"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-2">
                                    <span class="flex items-center gap-2"><i class="fa-solid fa-hand-holding-heart text-brand-500"></i> أحدث التبرعات</span>
                                    <span class="text-[10px] font-bold bg-brand-50 text-brand-600 px-2 py-1 rounded" x-text="(selectedCase?.donors_count || 0) + ' متبرع'"></span>
                                </h4>

                                <template x-if="selectedCase?.donations && selectedCase.donations.length > 0">
                                    <div class="space-y-4 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                                        <template x-for="donation in selectedCase.donations" :key="donation.id">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/30 text-brand-500 flex items-center justify-center shrink-0 mt-0.5 shadow-inner text-xs">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                                <div class="bg-slate-50 dark:bg-dark-900 flex-1 p-3 rounded-2xl rounded-tr-none border border-slate-100 dark:border-slate-700 text-sm">
                                                    <div class="flex justify-between items-center mb-1">
                                                        <h6 class="font-black text-slate-700 dark:text-slate-300" x-text="donation.donor_name || (donation.user ? donation.user.name : 'فاعل خير')"></h6>
                                                        <span class="text-[10px] font-bold text-slate-400" x-text="donation.created_at ? donation.created_at.substring(0,10) : ''"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-black text-emerald-600" x-show="donation.donation_type === 'financial'" x-text="'+ ' + donation.amount + ' ج.م'"></span>
                                                        <span class="font-black text-amber-600 text-xs bg-amber-50 px-2 py-0.5 rounded" x-show="donation.donation_type !== 'financial'"><i class="fa-solid fa-box"></i> تبرع عيني</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!selectedCase?.donations || selectedCase.donations.length === 0">
                                    <div class="text-center py-6 bg-slate-50 dark:bg-dark-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                                        <i class="fa-solid fa-hand-holding text-2xl text-slate-300 mb-2 block"></i>
                                        <p class="text-xs font-bold text-slate-500">لا توجد سجلات تبرع حتى الآن.</p>
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
/* تخصيص شريط التمرير داخل المودال ليصبح أنيقاً */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #334155; }
</style>
@endsection
