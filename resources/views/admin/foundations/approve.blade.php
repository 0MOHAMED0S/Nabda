@extends('admin.layouts.master')
@section('title', 'المؤسسات المعتمدة')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
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
    },

    openEditModal(item) {
        this.itemToEdit = { ...item };
        this.editModal = true;
    },

    openViewModal(item) {
        this.previewItem = { ...item };
        this.viewModal = true;
    }
}">

    <div class="mb-10">
        <div class="flex flex-col justify-between items-start gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    المؤسسات المعتمدة
                    <span class="text-slate-400 text-sm font-bold bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg">Approved</span>
                </h2>
                <div class="h-1.5 bg-emerald-500 w-16 mt-3 rounded-full"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي المعتمدة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['total_approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-toggle-on"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">حسابات نشطة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['active'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-toggle-off"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">حسابات موقوفة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['inactive'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white">قائمة المؤسسات العاملة بالمنصة</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث باسم المؤسسة..."
                    class="w-full px-6 py-4 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-20">اللوجو</th>
                        <th class="px-6 py-6">اسم المؤسسة</th>
                        <th class="px-6 py-6">التواصل</th>
                        <th class="px-6 py-6 text-center">التفعيل (دخول المنصة)</th>
                        <th class="px-6 py-6 text-center w-48">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($foundations as $item)
                        <tr x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase())"
                            class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5 text-center">
                                <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-white p-1">
                                    <img src="{{ asset('storage/' . $item->logo) }}" class="w-full h-full object-contain">
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white">{{ $item->name }}</h5>
                                <span class="text-[10px] text-slate-400 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-0.5 rounded mt-1 inline-block">{{ $item->type }}</span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[11px] text-slate-500 font-bold" dir="ltr"><i class="fa-solid fa-phone text-slate-400 mr-1"></i> {{ $item->phone }}</span>
                                    <span class="text-[11px] text-slate-500 font-medium"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> {{ $item->email }}</span>
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

                                    <button type="submit" :disabled="loading" title="{{ $item->status === 'active' ? 'انقر للإيقاف' : 'انقر للتفعيل' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 {{ $item->status === 'active' ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-300 shadow-sm {{ $item->status === 'active' ? '-translate-x-6' : '-translate-x-1' }}"></span>
                                    </button>
                                </form>
                                <span class="block mt-1 text-[10px] font-bold {{ $item->status === 'active' ? 'text-emerald-500' : 'text-slate-400' }}">
                                    {{ $item->status === 'active' ? 'نشط' : 'موقوف' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openViewModal(@js($item))" :disabled="loading" title="معاينة الملف الكامل"
                                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-brand-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-brand-500 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>

                                    <button @click="openEditModal(@js($item))" :disabled="loading" title="تعديل البيانات"
                                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-blue-600 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true" :disabled="loading" title="حذف نهائي"
                                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-rose-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic text-lg">لا يوجد مؤسسات معتمدة حالياً.</td>
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
            <div x-show="viewModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="viewModal = false" x-transition class="bg-white dark:bg-dark-800 w-full max-w-5xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                            ملف المؤسسة
                            <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 text-[10px] px-3 py-1 rounded-full"><i class="fa-solid fa-check-circle ml-1"></i>معتمدة</span>
                        </h3>
                        <button type="button" @click="viewModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-8 items-start">
                        <div class="flex-1 w-full space-y-6">
                            <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                                <div class="w-20 h-20 rounded-2xl border-2 border-slate-100 dark:border-slate-700 p-1 bg-white shrink-0">
                                    <img :src="'/storage/' + previewItem.logo" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h4 class="text-xl font-black text-slate-800 dark:text-white" x-text="previewItem.name"></h4>
                                    <span class="text-xs text-slate-500 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded mt-1 inline-block" x-text="previewItem.type"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-slate-50 dark:bg-dark-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-envelope ml-1"></i> البريد</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.email"></span>
                                </div>
                                <div class="bg-slate-50 dark:bg-dark-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-phone ml-1"></i> الهاتف</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="previewItem.phone"></span>
                                </div>
                                <div class="bg-slate-50 dark:bg-dark-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-id-card ml-1"></i> الترخيص</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.license_number"></span>
                                </div>
                                <div class="bg-slate-50 dark:bg-dark-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-landmark ml-1"></i> الجهة المشرفة</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.supervising_authority"></span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-1/2 shrink-0 bg-slate-50 dark:bg-dark-900 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50">
                            <h5 class="text-sm font-black text-slate-800 dark:text-white mb-4">المستندات الرسمية:</h5>
                            <div class="grid grid-cols-2 gap-4">
                                <a :href="'/storage/' + previewItem.license_image" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-video bg-white">
                                    <img :src="'/storage/' + previewItem.license_image" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-[10px] font-bold">الترخيص</span>
                                    </div>
                                </a>
                                <a :href="'/storage/' + previewItem.commercial_register" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-video bg-white">
                                    <img :src="'/storage/' + previewItem.commercial_register" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-[10px] font-bold">السجل</span>
                                    </div>
                                </a>
                                <a :href="'/storage/' + previewItem.tax_card" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-video bg-white">
                                    <img :src="'/storage/' + previewItem.tax_card" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-[10px] font-bold">البطاقة الضريبية</span>
                                    </div>
                                </a>
                                <a :href="'/storage/' + previewItem.accreditation_letter" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-video bg-white">
                                    <img :src="'/storage/' + previewItem.accreditation_letter" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-[10px] font-bold">خطاب الاعتماد</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (editModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">تعديل بيانات المؤسسة المعتمدة</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')

                        <input type="hidden" name="approval_status" value="approved">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">حالة الحساب في المنصة</label>
                                    <select name="status" x-model="itemToEdit.status"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="active">الحساب نشط (تستطيع الدخول وإضافة مشاريع)</option>
                                        <option value="inactive">موقوف (ممنوعة مؤقتاً من الدخول)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">اسم المؤسسة</label>
                                <input type="text" name="name" x-model="itemToEdit.name"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">البريد الإلكتروني</label>
                                <input type="email" name="email" x-model="itemToEdit.email" dir="ltr"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف</label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">نوع المؤسسة</label>
                                <input type="text" name="type" x-model="itemToEdit.type"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الترخيص</label>
                                <input type="text" name="license_number" x-model="itemToEdit.license_number"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الجهة المشرفة</label>
                                <input type="text" name="supervising_authority" x-model="itemToEdit.supervising_authority"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>
                        </div>

                        <div class="flex gap-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading"
                                class="flex-1 bg-brand-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحفظ...' : 'تحديث البيانات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading"
                                class="px-10 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-dark-600 transition-all disabled:opacity-70">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (deleteModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المؤسسة نهائياً</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد؟ سيتم مسح حساب هذه المؤسسة وكافة مستنداتها من المنصة تماماً.</p>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-4">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-4 rounded-2xl font-black hover:bg-rose-700 shadow-xl shadow-rose-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحذف...' : 'نعم، احذف'"></span>
                            </button>
                            <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all disabled:opacity-70">تراجع</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
