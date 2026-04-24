@extends('admin.layouts.master')
@section('title', 'إدارة المؤسسات الخيرية')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    rejectModal: false,
    search: '',

    itemToEdit: {},
    itemToDelete: '',
    itemToReject: '',
    previewItem: null,

    init() {
        @if($foundations->count() > 0)
            this.previewItem = @js($foundations->first());
        @endif

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
    }
}">

    <div class="mb-10">
        <div class="flex flex-col justify-between items-start gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    طلبات المؤسسات الخيرية
                    <span class="text-slate-400 text-sm font-bold bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg">Foundations</span>
                </h2>
                <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي المؤسسات</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['total'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">المعتمدة (مفعلة)</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">قيد المراجعة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['pending'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-12 w-full" x-show="previewItem" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 transform scale-95" x-cloak>
        <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 px-1">
            <h3 class="text-xs md:text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-solid fa-microscope text-brand-500"></i> مراجعة البيانات والمستندات
            </h3>

            <div class="flex items-center gap-3">
                <template x-if="previewItem && previewItem.approval_status !== 'rejected'">
                    <button @click="itemToReject = previewItem.id; rejectModal = true" class="text-xs bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 px-4 py-2.5 rounded-xl font-bold hover:bg-amber-600 hover:text-white transition-colors flex items-center gap-2">
                        رفض الطلب <i class="fa-solid fa-ban"></i>
                    </button>
                </template>

                <button @click="openEditModal(previewItem)" class="text-xs bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400 px-4 py-2.5 rounded-xl font-bold hover:bg-brand-600 hover:text-white transition-colors flex items-center gap-2 shadow-sm">
                    تعديل الحالة <i class="fa-solid fa-arrow-left"></i>
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-[3rem] p-8 md:p-12 border border-slate-200/50 dark:border-slate-700/50 shadow-xl shadow-slate-200/30 dark:shadow-none relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-brand-400 via-brand-600 to-brand-400"></div>

            <div class="flex flex-col md:flex-row gap-10 items-start text-right">
                <div class="flex-1 space-y-6 w-full">
                    <div class="flex items-center justify-end gap-4 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <div>
                            <h4 class="text-2xl font-black text-slate-800 dark:text-white mb-1" x-text="previewItem.name"></h4>
                            <div class="flex items-center justify-end gap-3 text-xs font-bold mt-2 flex-wrap">
                                <span class="bg-slate-100 dark:bg-dark-700 text-slate-500 px-3 py-1 rounded-lg" x-text="previewItem.type"></span>

                                <span class="px-3 py-1 rounded-lg transition-colors"
                                      :class="{
                                          'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20': previewItem.approval_status === 'approved',
                                          'text-amber-500 bg-amber-50 dark:bg-amber-900/20': previewItem.approval_status === 'pending',
                                          'text-rose-500 bg-rose-50 dark:bg-rose-900/20': previewItem.approval_status === 'rejected'
                                      }"
                                      x-text="previewItem.approval_status === 'approved' ? 'معتمدة' : (previewItem.approval_status === 'rejected' ? 'مرفوضة' : 'قيد المراجعة')">
                                </span>

                                <span :class="previewItem.status === 'active' ? 'text-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'text-rose-500 bg-rose-50 dark:bg-rose-900/20'" class="px-3 py-1 rounded-lg" x-text="previewItem.status === 'active' ? 'الحساب نشط' : 'الحساب موقوف'"></span>
                            </div>
                        </div>
                        <div class="w-20 h-20 rounded-2xl border-4 border-slate-50 dark:border-slate-700 shadow-sm overflow-hidden shrink-0 bg-white">
                            <img :src="'/storage/' + previewItem.logo" class="w-full h-full object-contain">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 dark:bg-dark-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/50">
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1">البريد الإلكتروني</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.email"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1">رقم الهاتف</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="previewItem.phone"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1">رقم الترخيص</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.license_number"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1">الجهة المشرفة</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.supervising_authority"></span>
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 dark:border-slate-700/50 pt-4 mt-2">
                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1">تاريخ تقديم الطلب</span>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="new Date(previewItem.created_at).toLocaleDateString('en-GB')"></span>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-96 shrink-0 border-r-0 md:border-r border-slate-100 dark:border-slate-700/50 pr-0 md:pr-10">
                    <h5 class="text-sm font-black text-slate-800 dark:text-white mb-4">المستندات والصور المرفقة:</h5>
                    <div class="grid grid-cols-2 gap-4">
                        <a :href="'/storage/' + previewItem.license_image" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50">
                            <img :src="'/storage/' + previewItem.license_image" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-[10px] font-bold">صورة الترخيص</span>
                            </div>
                        </a>
                        <a :href="'/storage/' + previewItem.commercial_register" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50">
                            <img :src="'/storage/' + previewItem.commercial_register" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-[10px] font-bold">السجل التجاري</span>
                            </div>
                        </a>
                        <a :href="'/storage/' + previewItem.tax_card" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50">
                            <img :src="'/storage/' + previewItem.tax_card" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-[10px] font-bold">البطاقة الضريبية</span>
                            </div>
                        </a>
                        <a :href="'/storage/' + previewItem.accreditation_letter" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-square bg-slate-50">
                            <img :src="'/storage/' + previewItem.accreditation_letter" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-[10px] font-bold text-center leading-tight">خطاب<br>الاعتماد</span>
                            </div>
                        </a>
                        <a :href="'/storage/' + previewItem.headquarters_image" target="_blank" class="col-span-2 block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 h-28 bg-slate-50">
                            <img :src="'/storage/' + previewItem.headquarters_image" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-[10px] font-bold">صورة مقر المؤسسة</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white">سجل المؤسسات</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث باسم المؤسسة أو البريد..."
                    class="w-full px-6 py-4 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-20">اللوجو</th>
                        <th class="px-6 py-6">المؤسسة / التواصل</th>
                        <th class="px-6 py-6 text-center">حالة الاعتماد</th>
                        <th class="px-6 py-6 text-center">حالة الحساب</th>
                        <th class="px-6 py-6 text-center w-48">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($foundations as $item)
                        <tr @click="previewItem = @js($item)"
                            x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($item->email) }}'.includes(search.toLowerCase())"
                            class="transition-all duration-300 cursor-pointer border-r-4 group"
                            :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-50/50 dark:bg-brand-900/20 border-brand-500' : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                            <td class="px-6 py-5 text-center">
                                <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-white p-1"
                                     :class="previewItem && previewItem.id === {{ $item->id }} ? 'ring-2 ring-brand-500' : ''">
                                    <img src="{{ asset('storage/' . $item->logo) }}" class="w-full h-full object-contain">
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <h5 class="text-sm font-black transition-colors" :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-700 dark:text-brand-400' : 'text-slate-800 dark:text-white'">{{ $item->name }}</h5>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] text-slate-400 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-0.5 rounded">{{ $item->type }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $item->email }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($item->approval_status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 rounded-full text-[10px] font-black">
                                        <i class="fa-solid fa-circle-check"></i> معتمدة
                                    </span>
                                @elseif($item->approval_status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 px-3 py-1 rounded-full text-[10px] font-black">
                                        <i class="fa-solid fa-circle-xmark"></i> مرفوضة
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-1 rounded-full text-[10px] font-black">
                                        <i class="fa-solid fa-clock"></i> قيد المراجعة
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                @if($item->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 text-xs font-black">
                                        نشط
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-rose-600 dark:text-rose-400 text-xs font-black">
                                        موقوف
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($item->approval_status !== 'rejected')
                                        <button @click.stop="itemToReject = '{{ $item->id }}'; rejectModal = true" :disabled="loading" title="رفض الطلب"
                                            class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-amber-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-amber-500 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                            <i class="fa-solid fa-ban text-xs"></i>
                                        </button>
                                    @endif

                                    <button @click.stop="openEditModal(@js($item))" :disabled="loading" title="تحديث الحالة"
                                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-brand-600 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-brand-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-sliders text-xs"></i>
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
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic text-lg">لا يوجد طلبات تسجيل للمؤسسات.</td>
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
            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (editModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">تحديث بيانات وحالة المؤسسة</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">حالة الاعتماد (الموافقة)</label>
                                    <select name="approval_status" x-model="itemToEdit.approval_status"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="approved">نعم، معتمدة (مقبولة)</option>
                                        <option value="pending">لا، قيد المراجعة (معلقة)</option>
                                        <option value="rejected">مرفوضة نهائياً</option>
                                    </select>
                                </div>
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">حالة الحساب (الإيقاف)</label>
                                    <select name="status" x-model="itemToEdit.status"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="active">الحساب نشط (يستطيع الدخول)</option>
                                        <option value="inactive">موقوف (ممنوع من الدخول)</option>
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
                                <span x-text="loading ? 'جاري الحفظ...' : 'تحديث واعتماد'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading"
                                class="px-10 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-dark-600 transition-all disabled:opacity-70">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="rejectModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (rejectModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-24 h-24 bg-amber-50 dark:bg-amber-900/20 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                        <i class="fa-solid" :class="loading ? 'fa-circle-notch animate-spin' : 'fa-ban'"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">رفض الطلب وإيقاف الحساب</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد من رفض هذه المؤسسة؟ سيتم تعليق حسابها ولن تتمكن من تسجيل الدخول.</p>

                    <form :action="'{{ url('admin/foundations') }}/' + itemToReject + '/reject'" method="POST" @submit="loading = true">
                        @csrf @method('PUT')
                        <div class="flex gap-4">
                            <button type="submit" :disabled="loading" class="flex-1 bg-amber-500 text-white py-4 rounded-2xl font-black hover:bg-amber-600 shadow-xl shadow-amber-500/20 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الرفض...' : 'نعم، أرفض الطلب'"></span>
                            </button>
                            <button type="button" @click="rejectModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all disabled:opacity-70">تراجع</button>
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
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">سيتم مسح حساب المؤسسة وكافة مستنداتها المرفقة من النظام. لا يمكن التراجع.</p>

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
