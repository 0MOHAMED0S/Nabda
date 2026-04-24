@extends('admin.layouts.master')
@section('title', 'أقسام الخدمات')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
        loading: false,
        editModal: false,
        deleteModal: false,
        search: '',
        item: { id: '', name: '' },

        init() {
            // إعادة فتح نافذة التعديل تلقائياً في حال وجود خطأ
            @if ($errors->any() && session('edit_id')) this.item = {
                id: '{{ session('edit_id') }}',
                name: '{{ old('name') }}'
            };
            this.editModal = true; @endif
        }
    }">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8 md:mb-10">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                    إدارة الأقسام
                    <span
                        class="bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 text-sm py-1 px-3 rounded-xl font-bold">
                        {{ $categories->count() }} أقسام
                    </span>
                </h2>
                <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
                <p class="text-slate-500 mt-2 text-sm">تصنيفات الخدمات والمشاريع المتاحة في المنصة.</p>
            </div>
        </div>

        <div
            class="bg-white dark:bg-dark-800 p-6 md:p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10 w-full">
            <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                <div
                    class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-folder-plus"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 dark:text-white">إضافة قسم جديد</h3>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" @submit="loading = true">
                @csrf
                <div class="flex flex-col md:flex-row gap-4 items-start">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase tracking-wider">اسم
                            القسم</label>
                        <input type="text" name="name" value="{{ !session('edit_id') ? old('name') : '' }}"
                            placeholder="مثال: الخدمات الطبية، كفالة الأيتام..."
                            class="w-full px-5 py-4 rounded-xl border @if ($errors->has('name') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">
                        @if ($errors->has('name') && !session('edit_id'))
                            <span
                                class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                    <div class="w-full md:w-auto md:pt-6">
                        <button type="submit" :disabled="loading"
                            class="w-full md:w-auto bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                            <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                            <template x-if="!loading"><i class="fa-solid fa-plus"></i></template>
                            <span x-text="loading ? 'جاري الإضافة...' : 'حفظ وإضافة القسم'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div
            class="bg-white dark:bg-dark-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">

            <div
                class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white text-right">قائمة الأقسام الحالية</h3>
                <div class="w-full md:w-80 relative">
                    <input type="text" x-model="search" placeholder="ابحث عن قسم..."
                        class="w-full px-5 py-3.5 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all shadow-sm font-medium text-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                        <tr>
                            <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">اسم القسم</th>
                            <th
                                class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-40">
                                عدد الخدمات</th>
                            <th
                                class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-40">
                                الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @forelse($categories as $category)
                            <tr x-show="search === '' || '{{ $category->name }}'.includes(search)"
                                class="hover:bg-slate-50/50 dark:hover:bg-dark-900/20 transition-colors group">
                                <td
                                    class="px-6 py-5 text-base font-bold text-slate-800 dark:text-white transition-colors group-hover:text-brand-600">
                                    {{ $category->name }}</td>

                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="bg-slate-100 dark:bg-dark-900 text-slate-500 dark:text-slate-400 text-xs font-bold px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                                        {{ $category->services->count() ?? 0 }} خدمة
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="item = @js($category); editModal = true"
                                            class="w-11 h-11 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-blue-500 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 transition-all border border-slate-200 dark:border-slate-700/50"
                                            title="تعديل">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button @click="item = @js($category); deleteModal = true"
                                            class="w-11 h-11 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white hover:shadow-lg hover:shadow-rose-500/20 transition-all border border-slate-200 dark:border-slate-700/50"
                                            title="حذف">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 bg-slate-50 dark:bg-dark-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fa-solid fa-folder-open text-slate-300 dark:text-slate-600 text-3xl"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium text-lg">لم يتم إضافة أي أقسام حتى الآن.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div x-show="search !== '' && !Array.from($el.previousElementSibling.querySelectorAll('tbody tr')).some(el => el.style.display !== 'none')"
                    x-cloak class="px-6 py-16 text-center border-t border-slate-100 dark:border-slate-700/50">
                    <p class="text-slate-500 font-medium">لم يتم العثور على نتائج مطابقة لبحثك.</p>
                </div>
            </div>
        </div>

        <template x-teleport="body">
            <div>
                <div x-show="editModal" x-cloak
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
                    <div @click.away="!loading && (editModal = false)"
                        class="bg-white dark:bg-dark-800 p-8 rounded-[2rem] w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all text-right">

                        <div
                            class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل بيانات القسم</h3>
                            <button type="button" @click="editModal = false"
                                class="text-slate-400 hover:text-rose-500 transition-colors">
                                <i class="fa-solid fa-xmark text-2xl"></i>
                            </button>
                        </div>

                        <form :action="'{{ url('admin/categories') }}/' + item.id" method="POST" @submit="loading = true">
                            @csrf @method('PUT')

                            <div class="mb-8">
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase tracking-wider">اسم
                                    القسم</label>
                                <input type="text" name="name" x-model="item.name"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('name') ? 'true' : 'false' }} &&
                                            '{{ session('edit_id') }}' == item.id,
                                        'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !
                                            ({{ $errors->has('name') ? 'true' : 'false' }} &&
                                                '{{ session('edit_id') }}' == item.id)
                                    }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">

                                <template
                                    x-if="{{ $errors->has('name') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id">
                                    <span
                                        class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('name') }}</span>
                                </template>
                            </div>

                            <div class="flex gap-3">
                                <button type="submit" :disabled="loading"
                                    class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all">
                                    <template x-if="loading"><i
                                            class="fa-solid fa-circle-notch animate-spin"></i></template>
                                    <span x-text="loading ? 'جاري التحديث...' : 'حفظ التعديلات'"></span>
                                </button>
                                <button type="button" @click="editModal = false"
                                    class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 transition-colors text-sm">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="deleteModal" x-cloak
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] flex items-center justify-center p-4">
                    <div @click.away="!loading && (deleteModal = false)"
                        class="bg-white dark:bg-dark-800 p-8 rounded-[2rem] w-full max-w-sm text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                        <div
                            class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                            <i class="fa-solid fa-user-slash"></i>
                        </div>
                        <h4 class="text-xl font-black mb-2 text-slate-800 dark:text-white">تأكيد حذف القسم</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 leading-relaxed px-4">هل أنت متأكد من حذف
                            قسم <span class="font-bold text-slate-800 dark:text-white" x-text="item.name"></span>؟</p>
                        <p
                            class="text-[11px] bg-rose-50 dark:bg-rose-900/10 text-rose-600 dark:text-rose-400 p-3 rounded-xl font-bold mb-8 mx-2 border border-rose-100 dark:border-rose-900/20">
                            تحذير: هذا الإجراء سيقوم بحذف جميع الخدمات التابعة لهذا القسم نهائياً.</p>

                        <form :action="'{{ url('admin/categories') }}/' + item.id" method="POST"
                            @submit="loading = true">
                            @csrf @method('DELETE')
                            <div class="flex gap-3">
                                <button type="submit" :disabled="loading"
                                    class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-rose-700 shadow-lg shadow-rose-600/20 transition-all">
                                    <template x-if="loading"><i
                                            class="fa-solid fa-circle-notch animate-spin"></i></template>
                                    <span>نعم، احذف</span>
                                </button>
                                <button type="button" @click="deleteModal = false" :disabled="loading"
                                    class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 py-3.5 rounded-xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection
