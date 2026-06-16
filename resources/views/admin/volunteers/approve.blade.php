@extends('admin.layouts.master')
@section('title', 'المتطوعون المعتمدون')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    createModal: false,
    editModal: false,
    viewModal: false,
    deleteModal: false,
    search: '',

    itemToCreate: { volunteer_type: '{{ old('volunteer_type', 'general') }}' },
    itemToEdit: {},
    itemToDelete: '',
    previewItem: {},

    init() {
        @if ($errors->any() && session('edit_id'))
            @php $current = $volunteers->firstWhere('id', session('edit_id')); @endphp
            @if ($current)
                this.openEditModal(@js($current));
            @endif
        @endif

        // 🎯 فتح مودال الإضافة تلقائياً إذا كان هناك أخطاء في التحقق عند إضافة متطوع جديد
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
    }
}">

    <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    المتطوعون المعتمدون
                    <span class="text-slate-400 text-sm font-bold bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg">Approved</span>
                </h2>
                <div class="h-1.5 bg-emerald-500 w-16 mt-3 rounded-full"></div>
            </div>

            <button @click="createModal = true" class="bg-brand-600 text-white px-6 py-3 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> إضافة متطوع
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي المعتمدين</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['total_approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">تطوع عام</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['general'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-user"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">تابع لمؤسسة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['affiliated'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white">قائمة المتطوعين المعتمدين بالمنصة</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث باسم المتطوع، البريد، أو الرقم القومي..."
                    class="w-full px-6 py-4 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-20">الصورة</th>
                        <th class="px-6 py-6">اسم المتطوع / التواصل</th>
                        <th class="px-6 py-6 text-center">نوع التطوع</th>
                        <th class="px-6 py-6 text-center">الرقم القومي</th>
                        <th class="px-6 py-6 text-center w-48">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($volunteers as $item)
                        <tr x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($item->email) }}'.includes(search.toLowerCase()) || '{{ $item->national_id }}'.includes(search)"
                            class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5 text-center">
                                <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-slate-100 flex items-center justify-center p-1">
                                    @if($item->avatar)
                                        <img src="{{ asset('storage/' . $item->avatar) }}" class="w-full h-full object-cover rounded-lg">
                                    @else
                                        <i class="fa-solid fa-user text-slate-300"></i>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white">{{ $item->name }}</h5>
                                <div class="flex flex-col gap-1 mt-1">
                                    <span class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> {{ $item->email }}</span>
                                    <span class="text-[10px] text-slate-500 font-bold" dir="ltr"><i class="fa-solid fa-phone text-slate-400 mr-1"></i> {{ $item->phone }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-[10px] font-bold px-3 py-1 rounded-lg {{ $item->volunteer_type === 'general' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/20' }}">
                                    {{ $item->volunteer_type === 'general' ? 'تطوع عام' : 'تابع لمؤسسة' }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-dark-900 px-3 py-1 rounded">{{ $item->national_id }}</span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openViewModal(@js($item))" :disabled="loading" title="معاينة الملف الكامل"
                                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-brand-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-brand-500 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>

                                    <button @click="openEditModal(@js($item))" :disabled="loading" title="تعديل البيانات أو تغيير الحالة"
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
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic text-lg">لا يوجد متطوعين معتمدين حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($volunteers->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $volunteers->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="createModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (createModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">إضافة متطوع جديد</h3>
                        <button type="button" @click="createModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form action="{{ route('admin.volunteers.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="form_type" value="create">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">نوع التطوع <span class="text-rose-500">*</span></label>
                                    <select name="volunteer_type" x-model="itemToCreate.volunteer_type" required
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="general">تطوع عام للجميع</option>
                                        <option value="affiliated">تطوع تابع لمؤسسة محددة</option>
                                    </select>
                                </div>

                                <div class="flex-1 space-y-2" x-show="itemToCreate.volunteer_type === 'affiliated'" x-transition>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">معرف المؤسسة (ID) <span class="text-rose-500">*</span></label>
                                    <input type="number" name="foundation_id" value="{{ old('foundation_id') }}" placeholder="أدخل رقم الـ ID للمؤسسة"
                                        class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                </div>
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الرقم القومي <span class="text-rose-500">*</span></label>
                                <input type="text" name="national_id" value="{{ old('national_id') }}" required maxlength="14" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" dir="ltr" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone') }}" dir="ltr" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">العنوان السكني <span class="text-rose-500">*</span></label>
                                <input type="text" name="address" value="{{ old('address') }}" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">كلمة المرور <span class="text-rose-500">*</span></label>
                                <input type="password" name="password" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">تأكيد كلمة المرور <span class="text-rose-500">*</span></label>
                                <input type="password" name="password_confirmation" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                            </div>

                            <div class="md:col-span-2 mt-4 p-6 bg-slate-50/50 dark:bg-dark-900/50 rounded-3xl border border-slate-200 dark:border-slate-700">
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4">هوية المتطوع والمرفقات</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">صورة شخصية (اختياري)</label>
                                        <input type="file" name="avatar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">صورة البطاقة (الوجه الأمامي) <span class="text-rose-500">*</span></label>
                                        <input type="file" name="national_id_front" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">صورة البطاقة (الوجه الخلفي) <span class="text-rose-500">*</span></label>
                                        <input type="file" name="national_id_back" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الإنشاء...' : 'حفظ واعتماد المتطوع'"></span>
                            </button>
                            <button type="button" @click="createModal = false" :disabled="loading" class="px-10 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-dark-600 transition-all disabled:opacity-70">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="viewModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="viewModal = false" x-transition class="bg-white dark:bg-dark-800 w-full max-w-5xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                            ملف المتطوع
                            <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 text-[10px] px-3 py-1 rounded-full"><i class="fa-solid fa-check-circle ml-1"></i>حساب معتمد</span>
                        </h3>
                        <button type="button" @click="viewModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-8 items-start">
                        <div class="flex-1 w-full space-y-6">
                            <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                                <div class="w-20 h-20 rounded-2xl border-2 border-slate-100 dark:border-slate-700 p-1 bg-slate-50 shrink-0 flex items-center justify-center overflow-hidden">
                                    <template x-if="previewItem.avatar">
                                        <img :src="'/storage/' + previewItem.avatar" class="w-full h-full object-cover rounded-xl">
                                    </template>
                                    <template x-if="!previewItem.avatar">
                                        <i class="fa-solid fa-user text-3xl text-slate-300"></i>
                                    </template>
                                </div>
                                <div>
                                    <h4 class="text-xl font-black text-slate-800 dark:text-white" x-text="previewItem.name"></h4>
                                    <span class="text-xs text-slate-500 font-bold bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded mt-1 inline-block" x-text="previewItem.volunteer_type === 'general' ? 'تطوع عام' : 'تابع لمؤسسة'"></span>
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
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-id-card ml-1"></i> الرقم القومي</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.national_id"></span>
                                </div>
                                <div class="bg-slate-50 dark:bg-dark-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-map-location-dot ml-1"></i> العنوان</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.address"></span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-1/2 shrink-0 bg-slate-50 dark:bg-dark-900 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50">
                            <h5 class="text-sm font-black text-slate-800 dark:text-white mb-4">هوية المتطوع المرفقة:</h5>
                            <div class="flex flex-col gap-4">
                                <a :href="'/storage/' + previewItem.national_id_front" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-[16/9] bg-white">
                                    <img :src="'/storage/' + previewItem.national_id_front" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-xs font-bold">بطاقة الرقم القومي (أمامي)</span>
                                    </div>
                                </a>
                                <a :href="'/storage/' + previewItem.national_id_back" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-[16/9] bg-white">
                                    <img :src="'/storage/' + previewItem.national_id_back" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-xs font-bold">بطاقة الرقم القومي (خلفي)</span>
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
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">تعديل بيانات المتطوع المعتمد</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/volunteers') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">حالة الاعتماد والحساب</label>
                                    <select name="status" x-model="itemToEdit.status"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="approved">نعم، معتمد (حساب نشط)</option>
                                        <option value="pending">إرجاع للمراجعة (معلق)</option>
                                        <option value="rejected">مرفوض / موقوف</option>
                                    </select>
                                    @error('status')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                                </div>
                                <div class="flex-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">نوع التطوع</label>
                                    <select name="volunteer_type" x-model="itemToEdit.volunteer_type"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                        <option value="general">تطوع عام للجميع</option>
                                        <option value="affiliated">تطوع تابع لمؤسسة</option>
                                    </select>
                                    @error('volunteer_type')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الاسم الكامل</label>
                                <input type="text" name="name" x-model="itemToEdit.name"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('name')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2 mt-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الرقم القومي</label>
                                <input type="text" name="national_id" x-model="itemToEdit.national_id"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('national_id')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">البريد الإلكتروني</label>
                                <input type="email" name="email" x-model="itemToEdit.email" dir="ltr"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('email')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف</label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('phone')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">العنوان السكني</label>
                                <input type="text" name="address" x-model="itemToEdit.address"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('address')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
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
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المتطوع نهائياً</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد؟ سيتم مسح حساب هذا المتطوع وكافة مستنداته المرفقة من المنصة تماماً.</p>

                    <form :action="'{{ url('admin/volunteers') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
