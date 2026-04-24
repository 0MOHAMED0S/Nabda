@extends('admin.layouts.master')
@section('title', 'شروط الزكاة')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{
    loading: false,
    addModal: false,
    editModal: false,
    deleteModal: false,
    itemToEdit: { id: '', title: '', description: '', icon: '' },
    itemToDelete: '',
    icons: [
        'fa-solid fa-scale-balanced', 'fa-solid fa-calendar-days', 'fa-solid fa-user-check',
        'fa-solid fa-chart-line', 'fa-solid fa-hand-holding-dollar', 'fa-solid fa-money-bill-trend-up',
        'fa-solid fa-vault', 'fa-solid fa-coins', 'fa-solid fa-sack-dollar', 'fa-solid fa-gem'
    ],
    init() {
        @if($errors->any() && !session('edit_id'))
            this.itemToEdit = { id: '', title: '{{ old('title') }}', description: '{{ old('description') }}', icon: '{{ old('icon') }}' };
            this.addModal = true;
        @endif
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = { id: '{{ session('edit_id') }}', title: '{{ old('title') }}', description: '{{ old('description') }}', icon: '{{ old('icon') }}' };
            this.editModal = true;
        @endif
    }
}">

    <div class="flex justify-between items-end mb-12">
        <div>
            <h2 class="text-3xl font-bold text-slate-800 dark:text-white">شروط وجوب الزكاة</h2>
            <div class="h-1.5 w-20 bg-brand-600 rounded-full mt-2"></div>
        </div>
        <button @click="itemToEdit = { id: '', title: '', description: '', icon: 'fa-solid fa-star' }; addModal = true" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-3.5 rounded-xl font-bold transition shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i>
            <span>إضافة بطاقة جديدة</span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach($conditions as $item)
        <div class="bg-white dark:bg-dark-800 border border-slate-100 dark:border-slate-700/50 rounded-2xl shadow-sm p-8 flex flex-col items-center text-center transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="w-24 h-24 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 rounded-full flex items-center justify-center mb-6 text-4xl shadow-inner">
                <i class="{{ $item->icon }}"></i>
            </div>
            <h3 class="text-2xl font-bold text-sky-700 dark:text-sky-400 mb-4">{{ $item->title }}</h3>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-sm mb-8 flex-grow">{{ $item->description }}</p>
            <div class="flex items-center gap-3 border-t border-slate-50 dark:border-slate-700/50 pt-6 w-full justify-center">
                <button @click="itemToEdit = @js($item); editModal = true" class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-blue-500 transition-colors px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                    <i class="fa-solid fa-pen"></i> تعديل
                </button>
                <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>
                <button @click="itemToDelete = '{{ $item->id }}'; deleteModal = true" class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-rose-500 transition-colors px-3 py-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20">
                    <i class="fa-solid fa-trash"></i> حذف
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="addModal || editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (addModal = false, editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-lg rounded-3xl shadow-2xl p-8 border border-slate-100 dark:border-slate-700 transform transition-all">
                    <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-white" x-text="addModal ? 'إضافة شرط جديد' : 'تعديل البيانات'"></h3>

                    <form :action="addModal ? '{{ route('admin.zakat.store') }}' : '{{ url('admin/zakat-conditions') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf
                        <template x-if="editModal"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="space-y-5 text-right">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">عنوان البطاقة</label>
                                <input type="text" name="title" x-model="itemToEdit.title"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('title')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id)),
                                        'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !@json($errors->has('title'))
                                    }"
                                    class="w-full px-4 py-3 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all">

                                <template x-if="@json($errors->has('title')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id))">
                                    <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('title') }}</span>
                                </template>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الأيقونة (اختر أو اكتب الكود)</label>
                                <div class="grid grid-cols-5 gap-2 bg-slate-50 dark:bg-dark-900 p-3 rounded-t-xl border border-b-0"
                                    :class="@json($errors->has('icon')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id)) ? 'border-rose-500' : 'border-slate-200 dark:border-slate-700'">
                                    <template x-for="iconClass in icons">
                                        <button type="button" @click="itemToEdit.icon = iconClass"
                                            :class="itemToEdit.icon === iconClass ? 'bg-brand-600 text-white shadow-md scale-110' : 'bg-white dark:bg-dark-800 text-slate-400 hover:bg-slate-200 dark:hover:bg-dark-700'"
                                            class="h-10 w-10 rounded-lg flex items-center justify-center transition-all border border-transparent">
                                            <i :class="iconClass"></i>
                                        </button>
                                    </template>
                                </div>
                                <input type="text" name="icon" x-model="itemToEdit.icon" placeholder="مثال: fa-solid fa-heart"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('icon')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id)),
                                        'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !@json($errors->has('icon'))
                                    }"
                                    class="w-full px-4 py-3 rounded-b-xl border bg-slate-50 dark:bg-dark-900 outline-none text-left dir-ltr transition-all">

                                <template x-if="@json($errors->has('icon')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id))">
                                    <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('icon') }}</span>
                                </template>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الوصف المختصر</label>
                                <textarea name="description" rows="3" x-model="itemToEdit.description"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('description')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id)),
                                        'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !@json($errors->has('description'))
                                    }"
                                    class="w-full px-4 py-3 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all"></textarea>

                                <template x-if="@json($errors->has('description')) && ((addModal && !@json(session('edit_id'))) || (editModal && @json(session('edit_id')) == itemToEdit.id))">
                                    <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('description') }}</span>
                                </template>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 hover:bg-brand-700 transition-colors shadow-lg shadow-brand-500/30">
                                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                    <span x-text="loading ? 'جاري الحفظ...' : 'حفظ البيانات'"></span>
                                </button>
                                <button type="button" @click="addModal = false; editModal = false" :disabled="loading" class="px-6 bg-slate-100 dark:bg-dark-700 text-slate-500 hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors rounded-xl font-bold">إلغاء</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-3xl p-8 text-center shadow-2xl border border-slate-100 dark:border-slate-700/50 transform transition-all">

                    <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl shadow-inner">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>

                    <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">تأكيد الحذف</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من رغبتك في حذف هذه البطاقة؟ لا يمكن التراجع عن هذا الإجراء.</p>

                    <form :action="'{{ url('admin/zakat-conditions') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf
                        @method('DELETE')
                        <div class="flex gap-3">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-rose-600/20 hover:bg-rose-700 hover:-translate-y-0.5 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحذف...' : 'نعم، احذف'"></span>
                            </button>
                            <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 py-3.5 rounded-xl font-bold hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </template>
</div>
@endsection
