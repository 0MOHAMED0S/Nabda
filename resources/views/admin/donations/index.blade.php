@extends('admin.layouts.master')
@section('title', 'سجل التبرعات')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    showModal: false,
    selectedDonation: null,
    loading: false,

    openModal(donationData) {
        this.selectedDonation = JSON.parse(JSON.stringify(donationData));
        this.showModal = true;
    },

    getStatusBadge(status) {
        if(status === 'completed') return '<span class=\'text-emerald-600 bg-emerald-50 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>مكتمل ✅</span>';
        if(status === 'pending') return '<span class=\'text-amber-600 bg-amber-50 border border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>قيد المراجعة ⏳</span>';
        if(status === 'failed') return '<span class=\'text-rose-600 bg-rose-50 border border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>فشل الدفع ❌</span>';
        if(status === 'cancelled') return '<span class=\'text-slate-600 bg-slate-100 border border-slate-200 dark:bg-slate-500/10 dark:border-slate-500/20 px-3 py-1 rounded-lg text-xs font-black shadow-sm\'>ملغي 🚫</span>';
        return '<span class=\'text-slate-600 bg-slate-100 px-3 py-1 rounded-lg text-xs font-black\'>غير معروف</span>';
    }
}">

    <div class="mb-10">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 mb-2">
            سجل التبرعات والمساهمات
            <span class="text-white text-xs font-bold bg-brand-500 px-3 py-1 rounded-xl shadow-sm">{{ $donations->total() }} حركة</span>
        </h2>
        <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">متابعة كافة التحويلات المالية بوابات الدفع (Paymob) والمساهمات العينية، وتحديث حالات الاستلام.</p>
        <div class="h-1.5 bg-brand-600 w-16 mt-4 rounded-full mb-8"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 p-6 rounded-[2rem] shadow-lg shadow-emerald-500/20 text-white flex items-center gap-5 relative overflow-hidden transition-transform hover:-translate-y-1">
                <i class="fa-solid fa-sack-dollar absolute -left-4 -bottom-4 text-7xl opacity-10"></i>
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl shrink-0"><i class="fa-solid fa-coins"></i></div>
                <div>
                    <span class="block text-emerald-100 text-[10px] font-black uppercase tracking-widest mb-1">إجمالي التبرعات المالية (المكتملة)</span>
                    <h4 class="text-3xl font-black">{{ number_format($stats['total_financial']) }} <span class="text-lg font-bold text-emerald-200">ج.م</span></h4>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-amber-50 dark:bg-amber-900/30 text-amber-500 rounded-2xl flex items-center justify-center text-3xl shrink-0"><i class="fa-solid fa-box-open"></i></div>
                <div>
                    <span class="block text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">المساهمات العينية (المكتملة)</span>
                    <h4 class="text-3xl font-black text-slate-800 dark:text-white">{{ number_format($stats['total_inkind']) }} <span class="text-lg font-bold text-slate-400">تبرع</span></h4>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/30 text-rose-500 rounded-2xl flex items-center justify-center text-3xl shrink-0"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div>
                    <span class="block text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">المهام العاجلة (قيد المراجعة)</span>
                    <h4 class="text-3xl font-black text-rose-600 dark:text-rose-500">{{ number_format($stats['pending_count']) }} <span class="text-lg font-bold text-slate-400">حركة</span></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <form action="{{ route('admin.donations.index') }}" method="GET" @submit="loading = true">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

                <div class="relative lg:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">البحث السريع</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="رقم الطلب، رقم Paymob، اسم المتبرع، الهاتف..." class="w-full px-5 py-3 pr-10 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-10 text-slate-400"></i>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">وجهة التبرع (المؤسسة)</label>
                    <select name="foundation_id" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">كل المؤسسات</option>
                        @foreach($foundations as $foundation)
                            <option value="{{ $foundation->id }}" {{ request('foundation_id') == $foundation->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($foundation->name, 25) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">نوع التبرع</label>
                    <select name="donation_type" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        <option value="financial" {{ request('donation_type') == 'financial' ? 'selected' : '' }}>دعم مالي 💸</option>
                        <option value="in_kind" {{ request('donation_type') == 'in_kind' ? 'selected' : '' }}>دعم عيني 📦</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">حالة العملية</label>
                    <select name="status" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة ✅</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلقة ⏳</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>فشلت ❌</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة 🚫</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/50">
                @if(request()->hasAny(['search', 'foundation_id', 'donation_type', 'status']) && request()->except('page'))
                    <a href="{{ route('admin.donations.index') }}" class="text-xs font-bold text-slate-400 hover:text-rose-500 transition-colors px-4 py-2">
                        <i class="fa-solid fa-rotate-left"></i> مسح الفلاتر
                    </a>
                @endif
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-black px-8 py-3 rounded-2xl transition-all shadow-lg shadow-brand-500/20 flex items-center justify-center gap-2">
                    <span x-show="!loading"><i class="fa-solid fa-filter"></i> تطبيق الفلتر</span>
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
                        <th class="px-6 py-6 w-16">رقم</th>
                        <th class="px-6 py-6">بيانات المتبرع</th>
                        <th class="px-6 py-6">الوجهة والمشروع</th>
                        <th class="px-6 py-6 text-center">النوع والقيمة</th>
                        <th class="px-6 py-6 text-center">التاريخ والوقت</th>
                        <th class="px-6 py-6 text-center">الحالة</th>
                        <th class="px-6 py-6 text-center">تفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($donations as $donation)
                        <tr class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5">
                                <span class="text-xs font-black text-slate-400">#{{ $donation->id }}</span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl {{ $donation->user_id ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'bg-slate-100 text-slate-400 dark:bg-dark-900' }} flex items-center justify-center shadow-inner shrink-0">
                                        <i class="fa-solid {{ $donation->user_id ? 'fa-user-check' : 'fa-user-secret' }}"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black text-slate-800 dark:text-white">{{ $donation->donor_name ?: 'فاعل خير' }}</h5>
                                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                                            <i class="fa-solid fa-phone text-[8px]"></i> <span dir="ltr">{{ $donation->donor_phone ?: 'بدون هاتف' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <span class="block text-sm font-black text-slate-700 dark:text-slate-300 mb-1">
                                    <i class="fa-solid fa-building text-purple-500 w-4"></i> {{ \Illuminate\Support\Str::limit($donation->foundation->name ?? 'غير محدد', 25) }}
                                </span>
                                @if($donation->case_id)
                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-briefcase-medical text-brand-500"></i> {{ \Illuminate\Support\Str::limit($donation->foundationCase->title ?? 'مجهول', 25) }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-globe"></i> تبرع عام للأنشطة
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($donation->donation_type === 'financial')
                                    <span class="text-sm font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-3 py-1.5 rounded-lg block w-max mx-auto mb-1 shadow-sm">
                                        {{ number_format($donation->amount) }} ج.م
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400"><i class="fa-brands fa-cc-visa text-emerald-400"></i> {{ $donation->payment_method ?? 'بوابة دفع' }}</span>
                                @else
                                    <span class="text-sm font-black text-amber-600 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 px-3 py-1.5 rounded-lg block w-max mx-auto mb-1 shadow-sm">
                                        <i class="fa-solid fa-box-open"></i> {{ \Illuminate\Support\Str::limit($donation->item_category ?? 'مواد عينية', 15) }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400">تبرع عيني</span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="block text-xs font-black text-slate-600 dark:text-slate-300" dir="ltr">{{ $donation->created_at->format('Y-m-d') }}</span>
                                <span class="text-[10px] font-bold text-slate-400" dir="ltr">{{ $donation->created_at->format('h:i A') }}</span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div x-html="getStatusBadge('{{ $donation->status }}')"></div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <button @click="openModal(@js($donation))" class="w-10 h-10 rounded-xl bg-white dark:bg-dark-800 text-slate-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-all shadow-sm mx-auto group">
                                    <i class="fa-solid fa-file-invoice group-hover:scale-110 transition-transform"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner border border-slate-100 dark:border-slate-700">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">لا توجد سجلات!</h4>
                                <p class="text-sm text-slate-400 mt-1">لم يتم العثور على أي تبرعات تطابق الفلتر المختار.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($donations->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $donations->links() }}
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
                 class="bg-white dark:bg-dark-800 w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[95vh] border border-slate-100 dark:border-slate-700">

                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/80 dark:bg-dark-900/50 shrink-0">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-file-invoice text-brand-500"></i> تفاصيل العملية <span class="text-brand-600 font-mono" x-text="'#' + selectedDonation?.id"></span>
                            </h3>
                            <div x-html="getStatusBadge(selectedDonation?.status)"></div>
                        </div>
                        <p class="text-xs font-bold text-slate-400 flex items-center gap-2">
                            <i class="fa-regular fa-clock"></i> تم التسجيل في: <span dir="ltr" x-text="selectedDonation?.created_at ? selectedDonation.created_at.replace('T', ' ').substring(0,16) : ''"></span>
                        </p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-dark-800 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all border border-slate-200 dark:border-slate-700 shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="p-8 overflow-y-auto flex-1 bg-white dark:bg-dark-800 custom-scrollbar">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/10 p-6 rounded-3xl border border-blue-100 dark:border-blue-900/30">
                            <h4 class="text-sm font-black text-blue-600 mb-4 flex items-center justify-between">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-user-check"></i> معلومات المتبرع</span>
                                <span class="px-2 py-1 rounded text-[10px] text-white font-bold" :class="selectedDonation?.user_id ? 'bg-blue-500 shadow-sm' : 'bg-slate-400'" x-text="selectedDonation?.user_id ? 'حساب مسجل' : 'متبرع زائر'"></span>
                            </h4>
                            <ul class="space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                <li class="flex justify-between border-b border-blue-200/50 dark:border-blue-800/30 pb-2">
                                    <span class="text-slate-400">الاسم:</span> <span x-text="selectedDonation?.donor_name || 'فاعل خير'"></span>
                                </li>
                                <li class="flex justify-between border-b border-blue-200/50 dark:border-blue-800/30 pb-2">
                                    <span class="text-slate-400">البريد الإلكتروني:</span> <span dir="ltr" class="text-blue-600" x-text="selectedDonation?.donor_email || 'غير متوفر'"></span>
                                </li>
                                <li class="flex justify-between border-b border-blue-200/50 dark:border-blue-800/30 pb-2">
                                    <span class="text-slate-400">رقم الهاتف:</span> <span dir="ltr" x-text="selectedDonation?.donor_phone || 'غير متوفر'"></span>
                                </li>
                                <li class="flex justify-between pt-1">
                                    <span class="text-slate-400">العنوان المستلم منه:</span> <span class="truncate max-w-[150px]" :title="selectedDonation?.donor_address" x-text="selectedDonation?.donor_address || 'غير محدد'"></span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-purple-50 dark:bg-purple-900/10 p-6 rounded-3xl border border-purple-100 dark:border-purple-900/30">
                            <h4 class="text-sm font-black text-purple-600 mb-4 flex items-center justify-between">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-bullseye"></i> وجهة التبرع</span>
                                <span class="px-2 py-1 rounded text-[10px] text-white font-bold" :class="selectedDonation?.case_id ? 'bg-brand-500 shadow-sm' : 'bg-purple-500 shadow-sm'" x-text="selectedDonation?.case_id ? 'حالة مخصصة' : 'تبرع عام'"></span>
                            </h4>
                            <ul class="space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                                <li class="flex justify-between border-b border-purple-200/50 dark:border-purple-800/30 pb-2">
                                    <span class="text-slate-400">المؤسسة المستفيدة:</span> <span x-text="selectedDonation?.foundation?.name || 'غير محدد'"></span>
                                </li>
                                <li class="flex justify-between border-b border-purple-200/50 dark:border-purple-800/30 pb-2">
                                    <span class="text-slate-400">رقم المؤسسة:</span> <span class="font-mono" x-text="'#' + (selectedDonation?.foundation_id || '---')"></span>
                                </li>
                                <li class="flex flex-col gap-1 pt-1">
                                    <span class="text-slate-400">المشروع / الحالة:</span>
                                    <div class="bg-white dark:bg-dark-800 p-2 rounded-lg border border-purple-100 dark:border-purple-900/50 mt-1" :class="selectedDonation?.case_id ? 'text-brand-600' : 'text-purple-600'">
                                        <i class="fa-solid" :class="selectedDonation?.case_id ? 'fa-briefcase-medical' : 'fa-globe'"></i>
                                        <span x-text="selectedDonation?.case_id ? (selectedDonation?.foundation_case?.title || 'حالة محذوفة') : 'تبرع عام لصالح المؤسسة'"></span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <template x-if="selectedDonation?.donation_type === 'financial'">
                            <div class="bg-emerald-50 dark:bg-emerald-900/10 p-6 rounded-3xl border border-emerald-100 dark:border-emerald-900/30">
                                <h4 class="text-sm font-black text-emerald-600 mb-6 flex items-center gap-2"><i class="fa-solid fa-money-bill-transfer"></i> بيانات التحويل المالي (Paymob Details)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                                    <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-800/50">
                                        <span class="block text-[10px] font-black text-emerald-400 uppercase mb-2">المبلغ المدفوع</span>
                                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-500" x-text="selectedDonation?.amount + ' ج.م'"></span>
                                    </div>
                                    <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-800/50">
                                        <span class="block text-[10px] font-black text-emerald-400 uppercase mb-2">طريقة الدفع</span>
                                        <span class="text-lg font-black text-slate-700 dark:text-slate-300" x-text="selectedDonation?.payment_method || 'غير محدد'"></span>
                                    </div>
                                    <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-800/50">
                                        <span class="block text-[10px] font-black text-emerald-400 uppercase mb-2">رقم الطلب (Order ID)</span>
                                        <span class="text-sm font-black text-slate-700 dark:text-slate-300 font-mono" x-text="selectedDonation?.paymob_order_id || '---'"></span>
                                    </div>
                                    <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-800/50">
                                        <span class="block text-[10px] font-black text-emerald-400 uppercase mb-2">رقم العملية (Transaction ID)</span>
                                        <span class="text-sm font-black text-slate-700 dark:text-slate-300 font-mono" x-text="selectedDonation?.paymob_transaction_id || '---'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedDonation?.donation_type !== 'financial'">
                            <div class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-3xl border border-amber-100 dark:border-amber-900/30">
                                <h4 class="text-sm font-black text-amber-600 mb-6 flex items-center gap-2"><i class="fa-solid fa-box-open"></i> تفاصيل المساهمة العينية</h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                                    <div class="space-y-4">
                                        <ul class="space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-dark-800 p-5 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-900/50">
                                            <li class="flex justify-between border-b border-amber-100 dark:border-amber-800/30 pb-2">
                                                <span class="text-slate-400"><i class="fa-solid fa-tags text-amber-400 w-5"></i> التصنيف:</span>
                                                <span class="text-amber-700 dark:text-amber-500" x-text="selectedDonation?.item_category || 'غير محدد'"></span>
                                            </li>
                                            <li class="flex justify-between border-b border-amber-100 dark:border-amber-800/30 pb-2">
                                                <span class="text-slate-400"><i class="fa-solid fa-star text-amber-400 w-5"></i> حالة الأشياء:</span>
                                                <span x-text="selectedDonation?.item_condition || 'غير محدد'"></span>
                                            </li>
                                            <li class="flex justify-between border-b border-amber-100 dark:border-amber-800/30 pb-2">
                                                <span class="text-slate-400"><i class="fa-solid fa-truck-fast text-amber-400 w-5"></i> طريقة التسليم:</span>
                                                <span class="bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded text-xs" x-text="selectedDonation?.delivery_method === 'pickup' ? 'استلام من عنوان المتبرع' : 'توصيل إلى مقر المؤسسة'"></span>
                                            </li>
                                            <li class="flex justify-between pt-1" x-show="selectedDonation?.delivery_method === 'pickup'">
                                                <span class="text-slate-400"><i class="fa-regular fa-clock text-amber-400 w-5"></i> الوقت المفضل:</span>
                                                <span dir="ltr" class="font-mono" x-text="selectedDonation?.pickup_time ? selectedDonation.pickup_time.replace('T', ' ').substring(0,16) : 'غير محدد'"></span>
                                            </li>
                                        </ul>
                                        <div>
                                            <span class="block text-[10px] font-black text-amber-500 uppercase mb-2 ml-1">وصف الأشياء المتبرع بها</span>
                                            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-900/30 min-h-[80px] whitespace-pre-line leading-relaxed" x-text="selectedDonation?.item_description || 'لا يوجد وصف مرفق.'"></p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-4">
                                        <div>
                                            <span class="block text-[10px] font-black text-amber-500 uppercase mb-2 ml-1">الصورة المرفقة (إن وجدت)</span>
                                            <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-900/30 overflow-hidden h-48 flex items-center justify-center">
                                                <template x-if="selectedDonation?.donation_image">
                                                    <img :src="selectedDonation.donation_image.startsWith('http') ? selectedDonation.donation_image : '{{ asset('storage') }}/' + selectedDonation.donation_image" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 cursor-pointer" alt="صورة التبرع العيني">
                                                </template>
                                                <template x-if="!selectedDonation?.donation_image">
                                                    <div class="text-center text-slate-300">
                                                        <i class="fa-regular fa-image text-4xl mb-2 block"></i>
                                                        <span class="text-xs font-bold">لا توجد صورة مرفقة</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-dark-800 p-5 rounded-2xl shadow-sm border border-brand-100 dark:border-brand-900/30 mt-auto">
                                            <h4 class="text-xs font-black text-brand-600 mb-3"><i class="fa-solid fa-arrows-rotate"></i> تحديث الإجراء التشغيلي</h4>
                                            <form :action="'{{ url('admin/donations') }}/' + selectedDonation?.id + '/status'" method="POST" class="flex flex-col sm:flex-row gap-3">
                                                @csrf
                                                @method('PUT')
                                                <select name="status" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 shadow-sm cursor-pointer">
                                                    <option value="pending" :selected="selectedDonation?.status === 'pending'">قيد الانتظار / ترتيب الاستلام ⏳</option>
                                                    <option value="completed" :selected="selectedDonation?.status === 'completed'">تم الاستلام بنجاح ✅</option>
                                                    <option value="cancelled" :selected="selectedDonation?.status === 'cancelled'">إلغاء العملية ❌</option>
                                                </select>
                                                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-black px-6 py-2.5 rounded-xl transition-all shadow-md shadow-brand-500/20 text-sm flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-save"></i> حفظ
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
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
