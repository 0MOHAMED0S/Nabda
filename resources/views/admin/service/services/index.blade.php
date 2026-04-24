@extends('admin.layouts.master')
@section('title', 'إدارة الخدمات')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    imgPreview: null,
    editImgPreview: null,
    search: '',
    item: {id: '', category_id: '', title: '', description: '', image: ''},

    fileChosen(e, type) {
        const file = e.target.files[0];
        if(!file) return;
        const reader = new FileReader();
        reader.onload = (e) => { type === 'add' ? this.imgPreview = e.target.result : this.editImgPreview = e.target.result; };
        reader.readAsDataURL(file);
    },

    init() {
        @if($errors->any() && session('edit_id'))
            this.item = {
                id: '{{ session('edit_id') }}',
                title: '{{ old('title') }}',
                category_id: '{{ old('category_id') }}',
                description: '{{ old('description') }}'
            };

            // استرجاع الصورة الأصلية للخدمة لكي لا يظهر المربع فارغاً عند الخطأ
            @php $failedItem = $services->firstWhere('id', session('edit_id')); @endphp
            @if($failedItem)
                this.editImgPreview = '{{ asset('storage/' . $failedItem->image) }}';
            @endif

            this.editModal = true;
        @endif
    }
}">

    <div class="mb-8 md:mb-10">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
            الخدمات والحملات
            <span class="bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 text-sm py-1 px-3 rounded-xl font-bold">
                {{ $services->count() }} خدمة
            </span>
        </h2>
        <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
        <p class="text-slate-500 mt-2 text-sm">إدارة جميع الخدمات والمشاريع المرتبطة بالأقسام.</p>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 md:p-8 mb-10 shadow-sm border border-slate-100 dark:border-slate-700/50">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">إضافة خدمة جديدة</h3>
        </div>

        <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8 text-right" @submit="loading = true">
            @csrf

            <div class="lg:col-span-3 flex flex-col items-center">
                <label class="block text-xs font-bold text-slate-400 mb-2 w-full text-right">صورة الخدمة</label>
                <label class="block relative w-full aspect-square border-2 border-dashed @if($errors->has('image') && !session('edit_id')) border-rose-500 bg-rose-50 dark:bg-rose-900/10 @else border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-dark-900 @endif rounded-2xl overflow-hidden cursor-pointer hover:border-brand-500 group transition-all">
                    <template x-if="imgPreview"><img :src="imgPreview" class="w-full h-full object-cover p-1 rounded-2xl"></template>
                    <template x-if="!imgPreview">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                            <i class="fa-solid fa-image text-4xl mb-3 group-hover:scale-110 group-hover:text-brand-500 transition-all"></i>
                            <span class="text-xs font-bold">اضغط لرفع صورة</span>
                        </div>
                    </template>
                    <input type="file" name="image" class="hidden" @change="fileChosen($event, 'add')">
                </label>
                @if($errors->has('image') && !session('edit_id'))
                    <span class="text-[11px] text-rose-500 font-bold mt-2 block">{{ $errors->first('image') }}</span>
                @endif
            </div>

            <div class="lg:col-span-9 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">اسم الخدمة / الحملة</label>
                        <input type="text" name="title" value="{{ !session('edit_id') ? old('title') : '' }}"
                            class="w-full px-5 py-4 rounded-xl border @if($errors->has('title') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm">
                        @if($errors->has('title') && !session('edit_id')) <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('title') }}</span> @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2">القسم</label>
                        <select name="category_id"
                            class="w-full px-5 py-4 rounded-xl border @if($errors->has('category_id') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm appearance-none">
                            <option value="">اختر القسم...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (!session('edit_id') && old('category_id') == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('category_id') && !session('edit_id')) <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('category_id') }}</span> @endif
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">الوصف</label>
                    <textarea name="description" rows="3"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('description') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm leading-relaxed">{{ !session('edit_id') ? old('description') : '' }}</textarea>
                    @if($errors->has('description') && !session('edit_id')) <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('description') }}</span> @endif
                </div>
                <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-700/50">
                    <button type="submit" :disabled="loading" class="bg-brand-600 text-white px-8 py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all w-full md:w-auto">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <template x-if="!loading"><i class="fa-solid fa-plus"></i></template>
                        <span x-text="loading ? 'جاري الحفظ...' : 'إضافة الخدمة'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">

        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">قائمة الخدمات</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث عن خدمة أو قسم..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-2 focus:ring-brand-500 outline-none transition-all shadow-sm font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-24 text-center">الصورة</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase">الخدمة والقسم</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($services as $service)
                    <tr x-show="search === '' || '{{ $service->title }}'.includes(search) || '{{ $service->category->name ?? '' }}'.includes(search)" class="hover:bg-slate-50/50 dark:hover:bg-dark-900/20 transition-colors group">
                        <td class="px-6 py-4">
                            <img src="{{ asset('storage/' . $service->image) }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm mx-auto group-hover:scale-105 transition-transform">
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-800 dark:text-white text-base mb-1">{{ $service->title }}</p>
                            <span class="text-[11px] bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 px-2.5 py-1 rounded-lg font-bold">
                                <i class="fa-solid fa-folder-open ml-1"></i> {{ $service->category->name ?? 'قسم محذوف' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="item = @js($service); editImgPreview = '{{ asset('storage/' . $service->image) }}'; editModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-blue-500 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 transition-all" title="تعديل">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="item = @js($service); deleteModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white hover:shadow-lg hover:shadow-rose-500/20 transition-all" title="حذف">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-dark-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-hand-holding-heart text-slate-300 dark:text-slate-600 text-2xl"></i>
                            </div>
                            <p class="text-slate-500 font-medium">لم يتم إضافة أي خدمات حتى الآن.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div x-show="search !== '' && !Array.from($el.previousElementSibling.querySelectorAll('tbody tr')).some(el => el.style.display !== 'none')"
                 x-cloak class="px-6 py-16 text-center border-t border-slate-100 dark:border-slate-700/50">
                <p class="text-slate-500 font-medium">لم يتم العثور على خدمة مطابقة للبحث.</p>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="editModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 p-8 rounded-3xl w-full max-w-lg shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all text-right max-h-[90vh] overflow-y-auto">

                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل الخدمة</h3>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <form :action="'{{ url('admin/services') }}/' + item.id" method="POST" enctype="multipart/form-data" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="mb-6 flex justify-center">
                            <label class="inline-block relative w-28 h-28 border-2 border-dashed @if($errors->has('image') && session('edit_id')) border-rose-500 @else border-slate-300 dark:border-slate-600 @endif rounded-2xl overflow-hidden cursor-pointer hover:border-brand-500 group">
                                <img :src="editImgPreview" class="w-full h-full object-cover p-1 rounded-2xl">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fa-solid fa-pen text-white text-xl"></i>
                                </div>
                                <input type="file" name="image" class="hidden" @change="fileChosen($event, 'edit')">
                            </label>
                        </div>
                        <div class="text-center mb-6">
                            <template x-if="{{ $errors->has('image') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id">
                                <span class="text-[11px] text-rose-500 font-bold">{{ $errors->first('image') }}</span>
                            </template>
                        </div>

                        <div class="mb-5">
                            <label class="text-xs font-bold text-slate-400 mb-2 block">العنوان</label>
                            <input type="text" name="title" x-model="item.title"
                                :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id, 'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !({{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id) }"
                                class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm">
                            <template x-if="{{ $errors->has('title') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id">
                                <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('title') }}</span>
                            </template>
                        </div>

                        <div class="mb-5">
                            <label class="text-xs font-bold text-slate-400 mb-2 block">القسم</label>
                            <select name="category_id" x-model="item.category_id"
                                :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('category_id') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id, 'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !({{ $errors->has('category_id') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id) }"
                                class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm appearance-none">
                                @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option> @endforeach
                            </select>
                            <template x-if="{{ $errors->has('category_id') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id">
                                <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('category_id') }}</span>
                            </template>
                        </div>

                        <div class="mb-6">
                            <label class="text-xs font-bold text-slate-400 mb-2 block">الوصف</label>
                            <textarea name="description" x-model="item.description" rows="3"
                                :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id, 'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !({{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id) }"
                                class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm leading-relaxed"></textarea>
                            <template x-if="{{ $errors->has('description') ? 'true' : 'false' }} && '{{ session('edit_id') }}' == item.id">
                                <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $errors->first('description') }}</span>
                            </template>
                        </div>

                        <div class="flex gap-3 pt-2 border-t border-slate-100 dark:border-slate-700/50 pt-6">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري التحديث...' : 'حفظ التعديلات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" class="px-6 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[110] flex items-center justify-center p-4">
                <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 p-8 rounded-3xl w-full max-w-sm text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">تأكيد الحذف</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">هل أنت متأكد من رغبتك في حذف خدمة <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.title"></span>؟</p>

                    <form :action="'{{ url('admin/services') }}/' + item.id" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-3">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-rose-600/20 hover:bg-rose-700 transition-all">
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
