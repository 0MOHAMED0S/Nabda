@extends('admin.layouts.master')
@section('title', 'أهدافنا - القسم الثاني')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    search: '',

    // Form State for Live Preview
    form: {
        title: '{{ old('title', '') }}',
        description: '{{ old('description', '') }}'
    },

    itemToEdit: { id: '', title: '', description: '' },
    itemToDelete: '',

    // State for Table Click Preview
    previewItem: null,

    init() {
        // Set first item as default preview if available
        @if(isset($goals) && $goals->count() > 0)
            this.previewItem = @js($goals->first());
        @endif

        // فتح نافذة التعديل تلقائياً إذا كان هناك خطأ في التحديث
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = {
                id: '{{ session('edit_id') }}',
                title: '{{ old('title') }}',
                description: '{{ old('description') }}'
            };
            this.editModal = true;
        @endif
    }
}">

    <div class="flex flex-col justify-between items-start gap-4 mb-8 md:mb-10">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                أهدافنا (القسم الثاني)
                <span class="bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 text-sm py-1 px-3 rounded-xl font-bold">
                    {{ $goals->count() }} أهداف
                </span>
            </h2>
            <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            <p class="text-slate-500 mt-2 text-sm">إدارة القائمة الجانبية للأهداف الموضحة في قسم (من نحن).</p>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 md:p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10 w-full">
        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-plus"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">إضافة هدف جديد</h3>
        </div>

        <form action="{{ route('admin.about_goals2.store') }}" method="POST" @submit="loading = true">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-right">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">عنوان الهدف</label>
                    <input type="text" name="title" x-model="form.title" placeholder="مثال: تعزيز الشفافية في العمل الخيري"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('title') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm text-slate-800 dark:text-white">
                    @if($errors->has('title') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('title') }}</span>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الوصف المختصر</label>
                    <input type="text" name="description" x-model="form.description" placeholder="مثال: عرض بيانات وتقارير واضحة..."
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('description') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm text-slate-600 dark:text-slate-300">
                    @if($errors->has('description') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('description') }}</span>
                    @endif
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
                <button type="submit" :disabled="loading" class="w-full md:w-auto bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <template x-if="!loading"><i class="fa-solid fa-floppy-disk"></i></template>
                    <span x-text="loading ? 'جاري الحفظ...' : 'حفظ الهدف الجديد'"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-10 w-full">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة حية (اضغط على صف لعرضه هنا)
            </h3>
        </div>

        <div class="bg-slate-50 dark:bg-dark-900 rounded-[2rem] p-8 md:p-12 border border-slate-200/50 dark:border-slate-700/50 flex flex-col items-center justify-center min-h-[250px] overflow-hidden">

            <div class="w-full max-w-2xl bg-white dark:bg-dark-800 rounded-2xl p-6 md:p-8 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col gap-2 transition-all duration-500"
                 :class="previewItem ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-4 opacity-50 scale-95'">
                <h4 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white text-right"
                    x-text="previewItem ? previewItem.title : (form.title || 'عنوان الهدف سيظهر هنا')"></h4>
                <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 text-right leading-relaxed"
                   x-text="previewItem ? previewItem.description : (form.description || 'الوصف التفصيلي سيظهر هنا بشكل منسق...')"></p>
            </div>

        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">

        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">قائمة الأهداف الحالية</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث عن هدف..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-2 focus:ring-brand-500 outline-none transition-all shadow-sm font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-20 text-center">#</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-1/3">عنوان الهدف</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase">الوصف</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($goals as $index => $item)
                    <tr @click="previewItem = @js($item)"
                        x-show="search === '' || '{{ $item->title }}'.includes(search) || '{{ $item->description }}'.includes(search)"
                        class="transition-all duration-300 cursor-pointer border-r-4 group"
                        :class="previewItem && previewItem.id === {{ $item->id }}
                            ? 'bg-brand-50/80 dark:bg-brand-900/30 border-brand-500'
                            : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                        <td class="px-6 py-5 text-center">
                            <div class="w-8 h-8 mx-auto flex items-center justify-center rounded-xl text-sm font-bold transition-all duration-300"
                                 :class="previewItem && previewItem.id === {{ $item->id }}
                                    ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/30 scale-110'
                                    : 'text-brand-600 bg-brand-100 dark:bg-brand-900/40 group-hover:scale-105'">
                                {{ $index + 1 }}
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm md:text-base font-bold transition-colors duration-300"
                            :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-700 dark:text-brand-400' : 'text-slate-800 dark:text-white'">
                            {{ $item->title }}
                        </td>
                        <td class="px-6 py-5 text-xs md:text-sm leading-relaxed truncate max-w-xs transition-colors duration-300"
                            :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-600/80 dark:text-brand-400/80' : 'text-slate-500 dark:text-slate-400'"
                            title="{{ $item->description }}">
                            {{ Str::limit($item->description, 80) }}
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <button @click.stop="itemToEdit = @js($item); editModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-800 text-slate-500 flex items-center justify-center hover:bg-blue-500 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 transition-all border border-slate-200 dark:border-slate-700/50" title="تعديل">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-800 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white hover:shadow-lg hover:shadow-rose-500/20 transition-all border border-slate-200 dark:border-slate-700/50" title="حذف">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-circle-info text-slate-300 dark:text-slate-600 text-3xl"></i>
                            </div>
                            <p class="text-slate-500 font-medium text-lg">لم يتم إضافة أي أهداف حتى الآن.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-lg rounded-3xl shadow-2xl p-8 border border-slate-100 dark:border-slate-700 transform transition-all text-right">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل بيانات الهدف</h3>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/about-goals2') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">عنوان الهدف</label>
                                <input type="text" name="title" x-model="itemToEdit.title"
                                    :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm">
                                <template x-if="{{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id">
                                    <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('title') }}</span>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الوصف</label>
                                <input type="text" name="description" x-model="itemToEdit.description"
                                    :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm">
                                <template x-if="{{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id">
                                    <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('description') }}</span>
                                </template>
                            </div>
                            <div class="flex gap-3 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all">
                                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                    <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التعديلات'"></span>
                                </button>
                                <button type="button" @click="editModal = false" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-3xl p-8 text-center shadow-2xl border border-slate-100 dark:border-slate-700/50 transform transition-all">
                    <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fa-solid fa-trash-can"></i></div>
                    <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">تأكيد الحذف</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من رغبتك في حذف هذا الهدف نهائياً؟</p>
                    <form :action="'{{ url('admin/about-goals2') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-3">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-rose-700 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span>نعم، احذف</span>
                            </button>
                            <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 py-3.5 rounded-xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
