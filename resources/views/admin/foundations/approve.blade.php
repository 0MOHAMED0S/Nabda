@extends('admin.layouts.master')
@section('title', 'المؤسسات المعتمدة')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    createModal: false,
    editModal: false,
    viewModal: false,
    deleteModal: false,
    search: '',

    itemToEdit: {},
    itemToDelete: '',
    previewItem: {},

    init() {
        @if ($errors->any() && session('edit_id'))
            @php $current = $foundations->firstWhere('id', session('edit_id')); @endphp
            @if ($current)
                this.openEditModal(@js($current));
            @endif
        @endif

        // فتح مودال الإضافة تلقائياً إذا كان هناك أخطاء
        @if ($errors->any() && session('form_type') === 'create')
            this.createModal = true;
        @endif
    },

    openEditModal(item) {
        this.itemToEdit = { ...item };
        this.editModal = true;
    },

    openViewModal(item) {
        this.previewItem = { ...item };
        this.viewModal = true;
    },

    formatMoney(amount) {
        return Number(amount || 0).toLocaleString('en-US');
    }
}">

    <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    المؤسسات المعتمدة والنشطة
                    <span class="text-emerald-600 text-xs font-bold bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1 rounded-xl shadow-sm"><i class="fa-solid fa-check-circle mr-1"></i> شركاء المنصة</span>
                </h2>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mt-2">إدارة حسابات المؤسسات المعتمدة، ومتابعة إحصائيات الإنجاز والمبالغ التي تم جمعها لكل مؤسسة.</p>
                <div class="h-1.5 bg-emerald-500 w-16 mt-3 rounded-full"></div>
            </div>

            <button @click="createModal = true" class="bg-brand-600 text-white px-6 py-3.5 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-1 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> إضافة مؤسسة مباشرة
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">إجمالي المؤسسات المعتمدة</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['total_approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-toggle-on"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">مسموح لها بدخول المنصة</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['active'] }} <span class="text-sm text-slate-400 font-bold">نشطة</span></span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-toggle-off"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">ممنوعة من دخول المنصة</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['inactive'] }} <span class="text-sm text-slate-400 font-bold">موقوفة</span></span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full relative">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-list-check text-brand-500"></i> سجل المؤسسات والإنجازات</h3>
            <div class="w-full md:w-96 relative">
                <input type="text" x-model="search" placeholder="ابحث باسم المؤسسة..."
                    class="w-full px-6 py-3 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-24">هوية المؤسسة</th>
                        <th class="px-6 py-6">بيانات التواصل</th>
                        <th class="px-6 py-6 text-center">مؤشرات التأثير (KPIs)</th>
                        <th class="px-6 py-6 text-center">حالة الدخول</th>
                        <th class="px-6 py-6 text-center w-48">إدارة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($foundations as $item)
                        <tr x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase())"
                            class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-white p-1 shrink-0">
                                        <img src="{{ asset('storage/' . $item->logo) }}" class="w-full h-full object-contain" alt="لوجو">
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black text-slate-800 dark:text-white" title="{{ $item->name }}">{{ \Illuminate\Support\Str::limit($item->name, 20) }}</h5>
                                        <span class="text-[10px] text-slate-400 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-0.5 rounded mt-1 inline-block">{{ $item->type }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-1.5">
                                    <span class="text-[11px] text-slate-600 dark:text-slate-300 font-bold" dir="ltr">
                                        <i class="fa-solid fa-phone text-slate-400 w-4"></i> {{ $item->phone }}
                                    </span>
                                    <span class="text-[11px] text-slate-600 dark:text-slate-300 font-medium">
                                        <i class="fa-solid fa-envelope text-slate-400 w-4"></i> {{ \Illuminate\Support\Str::limit($item->email, 25) }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <span class="bg-emerald-50 border border-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-800/50 dark:text-emerald-400 px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm" title="إجمالي المبالغ المجموعة">
                                        <i class="fa-solid fa-sack-dollar text-emerald-500 mr-1"></i> {{ number_format($item->total_collected_amount ?? 0) }} ج.م
                                    </span>
                                    <span class="bg-blue-50 border border-blue-100 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800/50 dark:text-blue-400 px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm" title="الحالات النشطة">
                                        <i class="fa-solid fa-briefcase-medical text-blue-500 mr-1"></i> {{ $item->active_cases_count }} حالة
                                    </span>
                                    <span class="bg-amber-50 border border-amber-100 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800/50 dark:text-amber-400 px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm" title="الفرص التطوعية المتاحة">
                                        <i class="fa-solid fa-handshake-angle text-amber-500 mr-1"></i> {{ $item->active_opportunities_count }} فرصة
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <form action="{{ route('admin.foundations.update', $item->id) }}" method="POST" @submit="loading = true" class="inline-block">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name" value="{{ $item->name }}">
                                    <input type="hidden" name="email" value="{{ $item->email }}">
                                    <input type="hidden" name="phone" value="{{ $item->phone }}">
                                    <input type="hidden" name="type" value="{{ $item->type }}">
                                    <input type="hidden" name="approval_status" value="{{ $item->approval_status }}">
                                    <input type="hidden" name="license_number" value="{{ $item->license_number }}">
                                    <input type="hidden" name="supervising_authority" value="{{ $item->supervising_authority }}">
                                    <input type="hidden" name="status" value="{{ $item->status === 'active' ? 'inactive' : 'active' }}">

                                    <button type="submit" :disabled="loading" title="{{ $item->status === 'active' ? 'إيقاف حساب المؤسسة' : 'تفعيل حساب المؤسسة' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 {{ $item->status === 'active' ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-slate-200 dark:bg-slate-700' }} shadow-sm cursor-pointer disabled:opacity-50">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-300 shadow-sm {{ $item->status === 'active' ? '-translate-x-6' : '-translate-x-1' }}"></span>
                                    </button>
                                </form>
                                <span class="block mt-1 text-[10px] font-bold {{ $item->status === 'active' ? 'text-emerald-500' : 'text-slate-400' }}">
                                    {{ $item->status === 'active' ? 'دخول مسموح' : 'دخول موقوف' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openViewModal(@js($item))" :disabled="loading" title="الملف الشامل والإحصائيات"
                                        class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-brand-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-brand-500 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>

                                    <button @click="openEditModal(@js($item))" :disabled="loading" title="تعديل البيانات الأساسية"
                                        class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-blue-600 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true" :disabled="loading" title="حذف المؤسسة نهائياً"
                                        class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-rose-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner border border-slate-100 dark:border-slate-700">
                                    <i class="fa-solid fa-building-circle-xmark"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">لا توجد مؤسسات معتمدة</h4>
                                <p class="text-sm text-slate-400 mt-1">لم يتم اعتماد أي مؤسسات للعمل في المنصة بعد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($foundations->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $foundations->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div>

            <div x-show="createModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
                <div @click.away="!loading && (createModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh] custom-scrollbar">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-building-circle-arrow-right text-brand-500"></i> إضافة مؤسسة (مباشرة كمعتمدة)</h3>
                        <button type="button" @click="createModal = false" :disabled="loading" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors shadow-sm"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>

                    <form action="{{ route('admin.foundations.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="form_type" value="create">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">اسم المؤسسة <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">نوع المؤسسة <span class="text-rose-500">*</span></label>
                                <input type="text" name="type" value="{{ old('type') }}" required placeholder="مثال: جمعية خيرية" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" dir="ltr" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone') }}" dir="ltr" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الترخيص <span class="text-rose-500">*</span></label>
                                <input type="text" name="license_number" value="{{ old('license_number') }}" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الجهة المشرفة <span class="text-rose-500">*</span></label>
                                <input type="text" name="supervising_authority" value="{{ old('supervising_authority') }}" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">كلمة المرور للدخول <span class="text-rose-500">*</span></label>
                                <input type="password" name="password" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500" dir="ltr">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">تأكيد كلمة المرور <span class="text-rose-500">*</span></label>
                                <input type="password" name="password_confirmation" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500" dir="ltr">
                            </div>

                            <div class="md:col-span-2 mt-4 p-6 bg-slate-50/50 dark:bg-dark-900/50 rounded-3xl border border-slate-200 dark:border-slate-700">
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4"><i class="fa-solid fa-folder-open text-brand-500"></i> المستندات والصور (مطلوبة للتوثيق)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">لوجو المؤسسة</label><input type="file" name="logo" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">صورة المقر</label><input type="file" name="headquarters_image" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">صورة الترخيص</label><input type="file" name="license_image" accept="image/*,.pdf" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">السجل التجاري</label><input type="file" name="commercial_register" accept="image/*,.pdf" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">البطاقة الضريبية</label><input type="file" name="tax_card" accept="image/*,.pdf" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">خطاب الاعتماد</label><input type="file" name="accreditation_letter" accept="image/*,.pdf" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-3.5 rounded-2xl font-black shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الإنشاء...' : 'حفظ واعتماد المؤسسة'"></span>
                            </button>
                            <button type="button" @click="createModal = false" :disabled="loading" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
                <div @click.away="!loading && (editModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh] custom-scrollbar">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-blue-500"></i> تعديل بيانات المؤسسة</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors shadow-sm"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')
                        <input type="hidden" name="approval_status" value="approved">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 p-6 bg-blue-50/50 dark:bg-blue-900/10 rounded-3xl border border-blue-100 dark:border-blue-900/30 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mr-1"><i class="fa-solid fa-shield-halved"></i> صلاحية الدخول للمنصة</label>
                                    <select name="status" x-model="itemToEdit.status" class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-blue-200 dark:border-blue-800 outline-none font-bold transition-all text-sm focus:border-blue-500 shadow-sm cursor-pointer text-slate-700 dark:text-slate-300">
                                        <option value="active">حساب نشط (مسموح بالدخول والإدارة)</option>
                                        <option value="inactive">حساب موقوف (ممنوع من الدخول)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">اسم المؤسسة</label>
                                <input type="text" name="name" x-model="itemToEdit.name" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">البريد الإلكتروني</label>
                                <input type="email" name="email" x-model="itemToEdit.email" dir="ltr" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الهاتف</label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">نوع المؤسسة</label>
                                <input type="text" name="type" x-model="itemToEdit.type" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الترخيص</label>
                                <input type="text" name="license_number" x-model="itemToEdit.license_number" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الجهة المشرفة</label>
                                <input type="text" name="supervising_authority" x-model="itemToEdit.supervising_authority" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                        </div>

                        <div class="flex gap-4 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-blue-600 text-white py-3.5 rounded-2xl font-black shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التحديثات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="viewModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
                <div @click.away="viewModal = false" x-transition class="bg-slate-50 dark:bg-dark-900 w-full max-w-6xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[95vh] border border-slate-200 dark:border-slate-700">

                    <div class="px-8 py-6 bg-white dark:bg-dark-800 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl border-2 border-slate-100 dark:border-slate-700 p-1.5 bg-white shrink-0 shadow-sm">
                                <img :src="'/storage/' + previewItem.logo" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2" x-text="previewItem.name"></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-bold bg-slate-100 dark:bg-dark-700 text-slate-500 px-2 py-0.5 rounded shadow-sm" x-text="previewItem.type"></span>
                                    <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded shadow-sm" :class="previewItem.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'" x-text="previewItem.status === 'active' ? 'نشط في المنصة' : 'موقوف'"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="viewModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-dark-700 text-slate-500 hover:text-white hover:bg-rose-500 transition-all shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1 custom-scrollbar">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                            <div class="lg:col-span-7 space-y-6">

                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-chart-line text-brand-500"></i> إحصائيات النشاط والتأثير</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2 bg-gradient-to-br from-emerald-500 to-emerald-700 p-6 rounded-3xl shadow-md shadow-emerald-500/20 text-white relative overflow-hidden">
                                            <i class="fa-solid fa-hand-holding-dollar absolute -left-4 -bottom-4 text-7xl opacity-10"></i>
                                            <span class="block text-[10px] font-black uppercase tracking-widest text-emerald-100 mb-1">إجمالي التبرعات المجمعة</span>
                                            <h4 class="text-3xl font-black" x-text="formatMoney(previewItem.total_collected_amount) + ' ج.م'"></h4>
                                        </div>

                                        <div class="bg-white dark:bg-dark-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center relative overflow-hidden">
                                            <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex items-center justify-center mb-3 absolute top-4 left-4"><i class="fa-solid fa-briefcase-medical"></i></div>
                                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">إجمالي الحالات</span>
                                            <div class="flex items-end gap-2">
                                                <h4 class="text-2xl font-black text-slate-800 dark:text-white" x-text="previewItem.total_cases_count || 0"></h4>
                                                <span class="text-xs font-bold text-blue-500 mb-1" x-text="'منها ' + (previewItem.active_cases_count || 0) + ' نشطة'"></span>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-dark-800 p-5 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col justify-center relative overflow-hidden">
                                            <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center mb-3 absolute top-4 left-4"><i class="fa-solid fa-handshake-angle"></i></div>
                                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">الفرص التطوعية (النشطة)</span>
                                            <div class="flex items-end gap-2">
                                                <h4 class="text-2xl font-black text-slate-800 dark:text-white" x-text="previewItem.active_opportunities_count || 0"></h4>
                                                <span class="text-xs font-bold text-amber-500 mb-1">فرصة</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-address-card text-brand-500"></i> بيانات الاتصال والتسجيل</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-envelope ml-1"></i> البريد الإلكتروني</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.email"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-phone ml-1"></i> رقم الهاتف</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="previewItem.phone"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-id-card ml-1"></i> رقم الترخيص</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="previewItem.license_number"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-landmark ml-1"></i> الجهة المشرفة</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.supervising_authority"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-5 bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm h-fit">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-folder-open text-brand-500"></i> المستندات الرسمية للتوثيق</h5>
                                <div class="grid grid-cols-2 gap-4">
                                    <a :href="'/storage/' + previewItem.license_image" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.license_image" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> الترخيص</span>
                                        </div>
                                    </a>
                                    <a :href="'/storage/' + previewItem.commercial_register" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.commercial_register" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> السجل التجاري</span>
                                        </div>
                                    </a>
                                    <a :href="'/storage/' + previewItem.tax_card" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.tax_card" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> البطاقة الضريبية</span>
                                        </div>
                                    </a>
                                    <a :href="'/storage/' + previewItem.accreditation_letter" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.accreditation_letter" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> خطاب الاعتماد</span>
                                        </div>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (deleteModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المؤسسة نهائياً</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد؟ سيتم مسح حساب هذه المؤسسة وكافة مستنداتها من المنصة تماماً ولن تتمكن من التراجع.</p>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-4">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-4 rounded-2xl font-black hover:bg-rose-700 shadow-xl shadow-rose-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحذف...' : 'نعم، احذف'"></span>
                            </button>
                            <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-dark-600 transition-all disabled:opacity-70">تراجع</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </template>
</div>

<style>
/* شريط التمرير للنوافذ المنبثقة */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #334155; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
@endsection
