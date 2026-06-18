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
    }
}">

    <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    المتطوعون المعتمدون
                    <span class="text-emerald-600 text-xs font-bold bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1 rounded-xl shadow-sm"><i class="fa-solid fa-user-check mr-1"></i> فريق المنصة</span>
                </h2>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-sm mt-2">إدارة حسابات المتطوعين، ومتابعة إجمالي الساعات التطوعية والأنشطة التي شاركوا بها.</p>
                <div class="h-1.5 bg-emerald-500 w-16 mt-3 rounded-full"></div>
            </div>

            <button @click="createModal = true" class="bg-brand-600 text-white px-6 py-3.5 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-1 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> إضافة متطوع مباشرة
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">إجمالي المتطوعين المعتمدين</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['total_approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">حسابات (تطوع عام)</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['general'] }} <span class="text-sm text-slate-400 font-bold">متطوع</span></span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-building-user"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">تابع لمؤسسات محددة</span>
                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ $stats['affiliated'] }} <span class="text-sm text-slate-400 font-bold">متطوع</span></span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full relative">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-address-book text-brand-500"></i> سجل المتطوعين</h3>
            <div class="w-full md:w-96 relative">
                <input type="text" x-model="search" placeholder="ابحث بالاسم، البريد، أو الرقم القومي..."
                    class="w-full px-6 py-3 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-20">الصورة</th>
                        <th class="px-6 py-6">بيانات المتطوع</th>
                        <th class="px-6 py-6 text-center">نوع التطوع</th>
                        <th class="px-6 py-6 text-center">الإنجازات (KPIs)</th>
                        <th class="px-6 py-6 text-center w-48">إدارة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($volunteers as $item)
                        <tr x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($item->email) }}'.includes(search.toLowerCase()) || '{{ $item->national_id }}'.includes(search)"
                            class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5 text-center">
                                <div class="w-12 h-12 mx-auto rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-slate-100 dark:bg-dark-900 flex items-center justify-center p-0.5 shrink-0">
                                    @if($item->avatar)
                                        <img src="{{ asset('storage/' . $item->avatar) }}" class="w-full h-full object-cover rounded-xl">
                                    @else
                                        <i class="fa-solid fa-user text-xl text-slate-300"></i>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white mb-1.5">{{ $item->name }}</h5>
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] text-slate-500 font-medium"><i class="fa-solid fa-envelope text-slate-400 w-3 text-center"></i> {{ $item->email }}</span>
                                    <span class="text-[10px] text-slate-500 font-bold" dir="ltr"><i class="fa-solid fa-id-card text-slate-400 w-3 text-center"></i> {{ $item->national_id }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-[10px] font-bold px-3 py-1.5 rounded-lg border shadow-sm {{ $item->volunteer_type === 'general' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:border-emerald-800/50' : 'bg-blue-50 border-blue-100 text-blue-600 dark:bg-blue-900/20 dark:border-blue-800/50' }}">
                                    <i class="fa-solid {{ $item->volunteer_type === 'general' ? 'fa-globe' : 'fa-building' }} mr-1"></i>
                                    {{ $item->volunteer_type === 'general' ? 'تطوع عام' : 'تابع لمؤسسة' }}
                                </span>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <span class="bg-amber-50 border border-amber-100 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800/50 dark:text-amber-400 px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm" title="إجمالي الساعات التطوعية المعتمدة">
                                        <i class="fa-solid fa-clock text-amber-500 mr-1"></i> {{ $item->total_hours ?? 0 }} ساعة
                                    </span>
                                    <span class="bg-purple-50 border border-purple-100 text-purple-700 dark:bg-purple-900/20 dark:border-purple-800/50 dark:text-purple-400 px-2.5 py-1 rounded-lg text-[10px] font-black shadow-sm" title="الأنشطة والمشاركات">
                                        <i class="fa-solid fa-list-check text-purple-500 mr-1"></i> {{ $item->activities_count ?? 0 }} نشاط
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openViewModal(@js($item))" :disabled="loading" title="الملف الشامل والإحصائيات"
                                        class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-brand-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-brand-500 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>

                                    <button @click="openEditModal(@js($item))" :disabled="loading" title="تعديل البيانات أو تغيير الحالة"
                                        class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-blue-600 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>

                                    <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true" :disabled="loading" title="حذف نهائي"
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
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">لا يوجد متطوعين معتمدين</h4>
                                <p class="text-sm text-slate-400 mt-1">لم يتم اعتماد أي متطوع للعمل في المنصة بعد.</p>
                            </td>
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

            <div x-show="createModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
                <div @click.away="!loading && (createModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh] custom-scrollbar">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-user-plus text-brand-500"></i> إضافة متطوع (مباشرة كمعتمد)</h3>
                        <button type="button" @click="createModal = false" :disabled="loading" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors shadow-sm"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>

                    <form action="{{ route('admin.volunteers.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="form_type" value="create">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row gap-6">
                                <div class="flex-1 space-y-2">
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">نوع التطوع <span class="text-rose-500">*</span></label>
                                    <select name="volunteer_type" x-model="itemToCreate.volunteer_type" required
                                        class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-slate-200 dark:border-slate-600 outline-none font-bold transition-all text-sm focus:border-brand-500 cursor-pointer">
                                        <option value="general">تطوع عام للجميع</option>
                                        <option value="affiliated">تطوع تابع لمؤسسة محددة</option>
                                    </select>
                                </div>

                                <div class="flex-1 space-y-2" x-show="itemToCreate.volunteer_type === 'affiliated'" x-transition>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">المؤسسة التابع لها <span class="text-rose-500">*</span></label>
                                    @if(isset($foundations) && count($foundations) > 0)
                                        <select name="foundation_id" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 outline-none font-bold transition-all text-sm focus:border-brand-500 cursor-pointer">
                                            <option value="">-- اختر المؤسسة --</option>
                                            @foreach($foundations as $foundation)
                                                <option value="{{ $foundation->id }}" {{ old('foundation_id') == $foundation->id ? 'selected' : '' }}>{{ $foundation->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="number" name="foundation_id" value="{{ old('foundation_id') }}" placeholder="أدخل رقم الـ ID للمؤسسة"
                                            class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 outline-none font-bold transition-all text-sm focus:border-brand-500">
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الرقم القومي <span class="text-rose-500">*</span></label>
                                <input type="text" name="national_id" value="{{ old('national_id') }}" required maxlength="14" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" dir="ltr" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone') }}" dir="ltr" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">العنوان السكني <span class="text-rose-500">*</span></label>
                                <input type="text" name="address" value="{{ old('address') }}" required class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
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
                                <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4"><i class="fa-solid fa-folder-open text-brand-500"></i> هوية المتطوع والمرفقات</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">صورة شخصية (اختياري)</label><input type="file" name="avatar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">صورة البطاقة (أمام) <span class="text-rose-500">*</span></label><input type="file" name="national_id_front" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                    <div class="space-y-1"><label class="text-[10px] font-black text-slate-500 uppercase">صورة البطاقة (خلف) <span class="text-rose-500">*</span></label><input type="file" name="national_id_back" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-3.5 rounded-2xl font-black shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الإنشاء...' : 'حفظ واعتماد المتطوع'"></span>
                            </button>
                            <button type="button" @click="createModal = false" :disabled="loading" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
                <div @click.away="!loading && (editModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh] custom-scrollbar">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-user-pen text-blue-500"></i> تعديل بيانات المتطوع المعتمد</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/volunteers') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="md:col-span-2 p-6 bg-blue-50/50 dark:bg-blue-900/10 rounded-3xl border border-blue-100 dark:border-blue-900/30 flex flex-col gap-6">

                                <div class="flex flex-col md:flex-row gap-6 w-full">
                                    <div class="flex-1 space-y-2">
                                        <label class="block text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mr-1"><i class="fa-solid fa-shield-halved"></i> حالة الاعتماد والحساب</label>
                                        <select name="status" x-model="itemToEdit.status"
                                            class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-blue-200 dark:border-blue-800 outline-none font-bold transition-all text-sm focus:border-blue-500 shadow-sm cursor-pointer text-slate-700 dark:text-slate-300">
                                            <option value="approved">نعم، معتمد (حساب نشط)</option>
                                            <option value="pending">إرجاع للمراجعة (معلق)</option>
                                            <option value="rejected">مرفوض / موقوف نهائياً</option>
                                        </select>
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <label class="block text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mr-1"><i class="fa-solid fa-network-wired"></i> نوع التطوع</label>
                                        <select name="volunteer_type" x-model="itemToEdit.volunteer_type"
                                            class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-blue-200 dark:border-blue-800 outline-none font-bold transition-all text-sm focus:border-blue-500 shadow-sm cursor-pointer text-slate-700 dark:text-slate-300">
                                            <option value="general">تطوع عام للجميع</option>
                                            <option value="affiliated">تطوع تابع لمؤسسة</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="w-full space-y-2 pt-4 border-t border-blue-200/50 dark:border-blue-800/50" x-show="itemToEdit.volunteer_type === 'affiliated'" x-transition>
                                    <label class="block text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mr-1">المؤسسة التابع لها <span class="text-rose-500">*</span></label>
                                    @if(isset($foundations) && count($foundations) > 0)
                                        <select name="foundation_id" x-model="itemToEdit.foundation_id" class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-blue-200 dark:border-blue-800 outline-none font-bold transition-all text-sm focus:border-blue-500 shadow-sm cursor-pointer text-slate-700 dark:text-slate-300">
                                            <option value="">-- الرجاء اختيار المؤسسة --</option>
                                            @foreach($foundations as $foundation)
                                                <option value="{{ $foundation->id }}">{{ $foundation->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="number" name="foundation_id" x-model="itemToEdit.foundation_id" placeholder="أدخل رقم الـ ID للمؤسسة التابع لها"
                                            class="w-full px-5 py-3.5 rounded-2xl border-2 bg-white dark:bg-dark-800 border-blue-200 dark:border-blue-800 outline-none font-bold transition-all text-sm focus:border-blue-500 shadow-sm text-slate-700 dark:text-slate-300">
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الاسم الكامل</label>
                                <input type="text" name="name" x-model="itemToEdit.name" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2 mt-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">الرقم القومي</label>
                                <input type="text" name="national_id" x-model="itemToEdit.national_id" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">البريد الإلكتروني</label>
                                <input type="email" name="email" x-model="itemToEdit.email" dir="ltr" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">رقم الهاتف</label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mr-1">العنوان السكني</label>
                                <input type="text" name="address" x-model="itemToEdit.address" class="w-full px-5 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm focus:border-brand-500">
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
                            <div class="w-16 h-16 rounded-2xl border-2 border-slate-100 dark:border-slate-700 p-1 bg-slate-50 dark:bg-dark-900 shrink-0 shadow-sm flex items-center justify-center overflow-hidden">
                                <template x-if="previewItem.avatar">
                                    <img :src="'/storage/' + previewItem.avatar" class="w-full h-full object-cover rounded-xl">
                                </template>
                                <template x-if="!previewItem.avatar">
                                    <i class="fa-solid fa-user text-2xl text-slate-300"></i>
                                </template>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2" x-text="previewItem.name"></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-bold bg-slate-100 dark:bg-dark-700 text-slate-500 px-2 py-0.5 rounded shadow-sm" x-text="previewItem.volunteer_type === 'general' ? 'متطوع عام' : 'متطوع مؤسسة'"></span>
                                    <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded shadow-sm bg-emerald-500"><i class="fa-solid fa-check-circle ml-0.5"></i> حساب معتمد</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="viewModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-dark-700 text-slate-500 hover:text-white hover:bg-rose-500 transition-all shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1 custom-scrollbar">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                            <div class="lg:col-span-7 space-y-6">

                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-chart-line text-brand-500"></i> إحصائيات النشاط والإنجاز</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-amber-500 to-amber-600 p-5 rounded-3xl shadow-md shadow-amber-500/20 text-white relative overflow-hidden flex flex-col justify-center">
                                            <i class="fa-solid fa-clock absolute -left-2 -bottom-2 text-6xl opacity-20"></i>
                                            <span class="block text-[10px] font-black uppercase tracking-widest text-amber-100 mb-1 relative z-10">إجمالي الساعات المعتمدة</span>
                                            <div class="flex items-end gap-2 relative z-10">
                                                <h4 class="text-3xl font-black" x-text="previewItem.total_hours || 0"></h4>
                                                <span class="text-sm font-bold text-amber-200 mb-1">ساعة</span>
                                            </div>
                                        </div>

                                        <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-purple-500 to-purple-600 p-5 rounded-3xl shadow-md shadow-purple-500/20 text-white relative overflow-hidden flex flex-col justify-center">
                                            <i class="fa-solid fa-list-check absolute -left-2 -bottom-2 text-6xl opacity-20"></i>
                                            <span class="block text-[10px] font-black uppercase tracking-widest text-purple-100 mb-1 relative z-10">إجمالي الأنشطة</span>
                                            <div class="flex items-end gap-2 relative z-10">
                                                <h4 class="text-3xl font-black" x-text="previewItem.activities_count || 0"></h4>
                                                <span class="text-sm font-bold text-purple-200 mb-1">نشاط / فرصة</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-address-card text-brand-500"></i> بيانات الاتصال والهوية</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-envelope ml-1"></i> البريد الإلكتروني</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="previewItem.email"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-phone ml-1"></i> رقم الهاتف</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" dir="ltr" x-text="previewItem.phone"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-id-card ml-1"></i> الرقم القومي</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono tracking-wider" x-text="previewItem.national_id"></span>
                                        </div>
                                        <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                                            <span class="block text-[10px] text-slate-400 font-bold uppercase mb-1"><i class="fa-solid fa-map-location-dot ml-1"></i> العنوان السكني</span>
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="previewItem.address"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-5 bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm h-fit">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-id-badge text-brand-500"></i> وثائق الهوية الرسمية</h5>
                                <div class="flex flex-col gap-4">
                                    <a :href="'/storage/' + previewItem.national_id_front" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-[16/9] bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.national_id_front" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> بطاقة الرقم القومي (الوجه الأمامي)</span>
                                        </div>
                                    </a>
                                    <a :href="'/storage/' + previewItem.national_id_back" target="_blank" class="block relative group rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 aspect-[16/9] bg-slate-50 dark:bg-dark-900 shadow-sm">
                                        <img :src="'/storage/' + previewItem.national_id_back" class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                            <span class="text-white text-xs font-bold"><i class="fa-solid fa-expand mb-1 block text-center"></i> بطاقة الرقم القومي (الوجه الخلفي)</span>
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
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المتطوع نهائياً</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد؟ سيتم مسح حساب هذا المتطوع وكافة مستنداته وسجلاته من المنصة تماماً.</p>

                    <form :action="'{{ url('admin/volunteers') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
