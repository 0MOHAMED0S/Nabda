@extends('admin.layouts.master')
@section('title', 'الرؤية والمهمة')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    search: '',

    // Image Handling
    addImageUrl: null,
    editImageUrl: null,

    itemToEdit: { id: '', title: '', description: '', image: '' },
    itemToDelete: '',

    // Live Preview State (for clicking table rows)
    previewItem: null,

    fileChosenAdd(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => { this.addImageUrl = e.target.result; };
        reader.readAsDataURL(file);
    },

    fileChosenEdit(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => { this.editImageUrl = e.target.result; };
        reader.readAsDataURL(file);
    },

    init() {
        // Set first item as default preview if available
        @if(isset($visions) && $visions->count() > 0)
            this.previewItem = @js($visions->first());
        @endif

        // Modal Error Handling
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = {
                id: '{{ session('edit_id') }}',
                title: '{{ old('title') }}',
                description: '{{ old('description') }}'
            };

            @php $failedItem = $visions->firstWhere('id', session('edit_id')); @endphp
            @if($failedItem)
                this.editImageUrl = '{{ asset('storage/' . $failedItem->image) }}';
            @endif

            this.editModal = true;
        @endif
    }
}">

    <div class="flex flex-col justify-between items-start gap-4 mb-8 md:mb-10">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                المستقبل الذي نصنعه
                <span class="bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 text-sm py-1 px-3 rounded-xl font-bold">
                    {{ $visions->count() }} عناصر
                </span>
            </h2>
            <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            <p class="text-slate-500 mt-2 text-sm">إدارة الرؤية، المهمة، والقيم الأساسية للمنصة.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 md:p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10">
        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">إضافة عنصر جديد</h3>
        </div>

        <form action="{{ route('admin.about_visions.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true" class="grid grid-cols-1 md:grid-cols-12 gap-8 text-right">
            @csrf

            <div class="md:col-span-4 flex flex-col items-center justify-start pt-2">
                <label class="block text-xs font-bold text-slate-400 mb-3 uppercase tracking-wider">الأيقونة / الصورة</label>
                <label class="relative group cursor-pointer w-full max-w-[200px] aspect-square">
                    <div class="w-full h-full rounded-2xl border-2 border-dashed @if($errors->has('image') && !session('edit_id')) border-rose-500 bg-rose-50 dark:bg-rose-900/10 @else border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-dark-900 hover:border-brand-500 @endif overflow-hidden flex items-center justify-center transition-all">
                        <template x-if="addImageUrl">
                            <img :src="addImageUrl" class="w-full h-full object-contain p-4 bg-white dark:bg-dark-800 shadow-sm rounded-xl">
                        </template>
                        <template x-if="!addImageUrl">
                            <div class="text-center transition-transform group-hover:scale-110">
                                <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-solid fa-cloud-arrow-up text-slate-500 dark:text-slate-400 text-xl"></i>
                                </div>
                                <span class="block text-xs text-slate-500 font-bold">تصفح لرفع صورة</span>
                            </div>
                        </template>
                    </div>
                    <input type="file" name="image" class="hidden" @change="fileChosenAdd">
                </label>
                @if($errors->has('image') && !session('edit_id'))
                    <span class="text-[11px] text-rose-500 font-bold mt-2 text-center block">{{ $errors->first('image') }}</span>
                @endif
            </div>

            <div class="md:col-span-8 space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">العنوان (مثل: رؤيتنا)</label>
                    <input type="text" name="title" value="{{ !session('edit_id') ? old('title') : '' }}"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('title') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-bold">
                    @if($errors->has('title') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('title') }}</span>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الوصف</label>
                    <textarea name="description" rows="3"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('description') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-medium leading-relaxed">{{ !session('edit_id') ? old('description') : '' }}</textarea>
                    @if($errors->has('description') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('description') }}</span>
                    @endif
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
                    <button type="submit" :disabled="loading" class="w-full md:w-auto bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <template x-if="!loading"><i class="fa-solid fa-plus"></i></template>
                        <span x-text="loading ? 'جاري الإضافة...' : 'حفظ وإضافة للنظام'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-10 w-full">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة التصميم (اضغط على عنصر من الجدول لعرضه)
            </h3>
        </div>

        <div x-show="previewItem" x-transition class="bg-white dark:bg-dark-800 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-700/50 p-6 md:p-12 mb-10 flex flex-col md:flex-row items-center justify-center gap-8 relative overflow-hidden min-h-[250px]">
            <div class="absolute top-0 right-0 w-40 h-40 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10 pointer-events-none"></div>

            <div class="w-28 h-28 md:w-36 md:h-36 shrink-0 bg-slate-50 dark:bg-dark-900 rounded-2xl p-5 flex items-center justify-center border border-slate-100 dark:border-slate-700 shadow-sm transition-transform duration-500" :class="previewItem ? 'scale-100' : 'scale-90'">
                <img :src="previewItem ? '{{ asset('storage') }}/' + previewItem.image : ''" class="w-full h-full object-contain">
            </div>

            <div class="text-center md:text-right flex-1 z-10">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-brand-500 mb-2 block">معاينة حية للمحتوى</span>
                <h3 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mb-4 leading-tight" x-text="previewItem ? previewItem.title : ''"></h3>
                <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 leading-relaxed font-medium max-w-2xl" x-text="previewItem ? previewItem.description : ''"></p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden w-full">

        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">قائمة العناصر الحالية</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث عن عنوان أو وصف..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-2 focus:ring-brand-500 outline-none transition-all shadow-sm font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-24 text-center">الأيقونة</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-1/4">العنوان</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase">الوصف</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($visions as $item)
                    <tr @click="previewItem = @js($item)"
                        x-show="search === '' || '{{ $item->title }}'.includes(search) || '{{ $item->description }}'.includes(search)"
                        class="transition-all duration-300 cursor-pointer border-r-4 group"
                        :class="previewItem && previewItem.id === {{ $item->id }}
                            ? 'bg-brand-50/80 dark:bg-brand-900/30 border-brand-500'
                            : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                        <td class="px-6 py-4">
                            <div class="w-14 h-14 bg-white dark:bg-dark-800 rounded-xl p-2 border border-slate-200 dark:border-slate-700 shadow-sm mx-auto transition-transform duration-300"
                                 :class="previewItem && previewItem.id === {{ $item->id }} ? 'scale-110 shadow-md shadow-brand-500/20' : 'group-hover:scale-105'">
                                <img src="{{ asset('storage/' . $item->image) }}" class="w-full h-full object-contain">
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
                                <button @click.stop="itemToEdit = @js($item); editImageUrl = '{{ asset('storage/' . $item->image) }}'; editModal = true"
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
                                <i class="fa-solid fa-layer-group text-slate-300 dark:text-slate-600 text-3xl"></i>
                            </div>
                            <p class="text-slate-500 font-medium text-lg">لم يتم إضافة أي عناصر حتى الآن.</p>
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
            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-lg rounded-3xl shadow-2xl p-8 border border-slate-100 dark:border-slate-700 transform transition-all text-right max-h-[90vh] overflow-y-auto">

                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل البيانات</h3>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <form :action="'{{ url('admin/about-visions') }}/' + itemToEdit.id" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">

                        <div class="mb-6 flex flex-col items-center justify-center bg-slate-50 dark:bg-dark-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-700">
                            <label class="relative group cursor-pointer">
                                <div class="w-24 h-24 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 overflow-hidden bg-white dark:bg-dark-800 flex items-center justify-center transition-all group-hover:border-brand-500 shadow-sm"
                                     :class="{ 'border-rose-500 bg-rose-50 dark:bg-rose-900/10': {{ $errors->has('image') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id }">

                                    <template x-if="editImageUrl">
                                        <img :src="editImageUrl" class="w-full h-full object-contain p-2">
                                    </template>

                                    <div class="absolute inset-0 bg-brand-600/80 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                        <i class="fa-solid fa-pen text-white text-xl"></i>
                                    </div>
                                </div>
                                <input type="file" name="image" class="hidden" @change="fileChosenEdit">
                            </label>
                            <p class="text-[11px] text-slate-400 mt-3 font-bold uppercase tracking-wide">انقر لتغيير الصورة</p>

                            <template x-if="{{ $errors->has('image') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id">
                                <span class="text-[11px] text-rose-500 font-bold mt-2 block">{{ $errors->first('image') }}</span>
                            </template>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">العنوان</label>
                                <input type="text" name="title" x-model="itemToEdit.title"
                                    :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id, 'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !({{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id) }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm">

                                <template x-if="{{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id">
                                    <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('title') }}</span>
                                </template>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الوصف</label>
                                <textarea name="description" rows="4" x-model="itemToEdit.description"
                                    :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id, 'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !({{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id) }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm leading-relaxed"></textarea>

                                <template x-if="{{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == itemToEdit.id">
                                    <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('description') }}</span>
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-6 mt-6 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التعديلات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-3xl p-8 text-center shadow-2xl border border-slate-100 dark:border-slate-700/50 transform transition-all">
                    <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">تأكيد الحذف</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من رغبتك في حذف هذا العنصر نهائياً؟ لا يمكن التراجع.</p>

                    <form :action="'{{ url('admin/about-visions') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-3">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 shadow-lg shadow-rose-600/20 hover:bg-rose-700 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span>نعم، احذف</span>
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
