@extends('admin.layouts.master')
@section('title', 'إدارة المستخدمين')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    createModal: false,
    editModal: false,
    deleteModal: false,
    search: '',

    itemToEdit: {},
    itemToDelete: '',

    init() {
        @if ($errors->any() && session('edit_id'))
            @php $current = $users->firstWhere('id', session('edit_id')); @endphp
            @if ($current)
                this.openEditModal(@js($current));
            @endif
        @endif

        @if ($errors->any() && session('form_type') === 'create')
            this.createModal = true;
        @endif
    },

    openEditModal(item) {
        this.itemToEdit = { ...item };
        this.editModal = true;
    }
}">

    <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    إدارة المستخدمين
                    <span class="text-slate-400 text-sm font-bold bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg">Users</span>
                </h2>
                <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            </div>

            <button @click="createModal = true" class="bg-brand-600 text-white px-6 py-3 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> إضافة مستخدم
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي المستخدمين</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['total'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
            <h3 class="text-xl font-black text-slate-800 dark:text-white">قائمة المستخدمين</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث بالاسم أو البريد..."
                    class="w-full px-6 py-4 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 text-center w-20">الصورة</th>
                        <th class="px-6 py-6">الاسم / البريد</th>
                        <th class="px-6 py-6 text-center">إحصائيات العطاء</th>
                        <th class="px-6 py-6 text-center w-48">تاريخ التسجيل</th>
                        <th class="px-6 py-6 text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($users as $item)
                        <tr x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($item->email) }}'.includes(search.toLowerCase())"
                            class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40">

                            <td class="px-6 py-5 text-center">
                                <div class="w-12 h-12 mx-auto rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 bg-slate-100 flex items-center justify-center p-1">
                                    @if($item->avatar)
                                        @if(str_starts_with($item->avatar, 'http'))
                                            <img src="{{ $item->avatar }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <img src="{{ asset('storage/' . $item->avatar) }}" class="w-full h-full object-cover rounded-lg">
                                        @endif
                                    @else
                                        <i class="fa-solid fa-user text-slate-300"></i>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <h5 class="text-sm font-black text-slate-800 dark:text-white">
                                    @if($item->title)
                                        <span class="text-slate-400 text-xs">{{ $item->title }} / </span>
                                    @endif
                                    {{ $item->name }}
                                </h5>
                                <div class="flex flex-col gap-1 mt-1">
                                    <span class="text-[11px] text-slate-500 font-medium"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> {{ $item->email }}</span>
                                    @if($item->phone)
                                        <span class="text-[11px] text-slate-500 font-bold" dir="ltr"><i class="fa-solid fa-phone text-slate-400 mr-1"></i> {{ $item->phone }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex justify-center gap-2">
                                    <span class="text-[10px] font-black text-brand-600 bg-brand-50 dark:bg-brand-900/30 px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <i class="fa-solid fa-hand-holding-dollar"></i> {{ $item->completed_donations_count }} تبرع
                                    </span>
                                    <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <i class="fa-solid fa-heart"></i> {{ $item->helped_cases_count }} حالة
                                    </span>
                                    <span class="text-[10px] font-black text-amber-500 bg-amber-50 dark:bg-amber-900/30 px-3 py-1.5 rounded-lg flex items-center gap-1">
                                        <i class="fa-solid fa-star"></i> {{ $item->completed_donations_count * 10 }} نقطة
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-dark-900 px-3 py-1 rounded">
                                    {{ $item->created_at->format('Y-m-d') }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
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
                            <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic text-lg">لا يوجد مستخدمين مسجلين حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10 flex justify-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="createModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (createModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">إضافة مستخدم جديد</h3>
                        <button type="button" @click="createModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="form_type" value="create">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">اللقب (اختياري)</label>
                                <input type="text" name="title" value="{{ old('title') }}" placeholder="مثال: المهندس، الدكتور" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('title')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('name')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" dir="ltr" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('email')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف (اختياري)</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" dir="ltr" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('phone')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">كلمة المرور <span class="text-rose-500">*</span></label>
                                <input type="password" name="password" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                                @error('password')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">تأكيد كلمة المرور <span class="text-rose-500">*</span></label>
                                <input type="password" name="password_confirmation" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">صورة شخصية (اختياري)</label>
                                <input type="file" name="avatar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                @error('avatar')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="flex gap-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الإنشاء...' : 'حفظ المستخدم'"></span>
                            </button>
                            <button type="button" @click="createModal = false" :disabled="loading" class="px-10 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 dark:hover:bg-dark-600 transition-all disabled:opacity-70">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (editModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">تعديل بيانات المستخدم</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/users') }}/' + itemToEdit.id" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">اللقب</label>
                                <input type="text" name="title" x-model="itemToEdit.title" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('title')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">الاسم الكامل <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" x-model="itemToEdit.name" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('name')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" x-model="itemToEdit.email" dir="ltr" required class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('email')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف</label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500">
                                @error('phone')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">تحديث الصورة (اختياري)</label>
                                <input type="file" name="avatar" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                @error('avatar')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                            </div>

                            <div class="md:col-span-2 p-6 bg-amber-50/50 dark:bg-amber-900/10 rounded-3xl border border-amber-100 dark:border-amber-900/30">
                                <p class="text-xs font-bold text-amber-600 dark:text-amber-500 mb-4"><i class="fa-solid fa-circle-info ml-1"></i> اترك حقول كلمة المرور فارغة إذا لم تكن ترغب في تغييرها.</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">كلمة المرور الجديدة</label>
                                        <input type="password" name="password" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                                        @error('password')<span class="text-rose-500 text-xs font-bold">{{ $message }}</span>@enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">تأكيد كلمة المرور</label>
                                        <input type="password" name="password_confirmation" class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 outline-none font-bold transition-all text-sm h-[60px] focus:border-brand-500" dir="ltr">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التحديثات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (deleteModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المستخدم</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد؟ سيتم مسح حساب هذا المستخدم من المنصة بشكل نهائي.</p>

                    <form :action="'{{ url('admin/users') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
