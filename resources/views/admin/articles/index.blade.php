@extends('admin.layouts.master')
@section('title', 'إدارة المركز الإعلامي')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
        loading: false,
        editModal: false,
        deleteModal: false,
        search: '',

        // معاينة الصورة في نموذج الإضافة
        imagePreview: null,

        // بيانات العنصر المراد تعديله أو حذفه
        itemToEdit: { id: '', type: 'article', main_title: '', second_title: '', description: '', published_date: '', image_url: '' },
        itemToDelete: '',

        init() {
            @if ($errors->any() && session('edit_id'))
                @php $current = $articles->firstWhere('id', session('edit_id')); @endphp
                @if ($current)
                    this.openEditModal(@js($current));
                @endif
            @endif
        },

        // معالجة اختيار صورة جديدة في نموذج الإضافة
        handleImageSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            } else {
                this.imagePreview = null;
            }
        },

        // معالجة تغيير الصورة في نموذج التعديل لتحديث المعاينة فوراً
        handleEditImageSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.itemToEdit.image_url = URL.createObjectURL(file);
            }
        },

        openEditModal(item) {
            this.itemToEdit = { ...item };

            // تنسيق التاريخ ليتوافق مع حقل input type date
            if (item.published_date) {
                this.itemToEdit.published_date = String(item.published_date).split('T')[0];
            }

            this.itemToEdit.image_url = '/storage/' + item.image;
            this.editModal = true;
        }
    }">

        <div class="mb-10">
            <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                المركز الإعلامي
                <span class="bg-brand-500/10 text-brand-600 dark:text-brand-400 text-sm py-1.5 px-4 rounded-full font-bold">
                    {{ $articles->total() }} موضوع
                </span>
            </h2>
            <div class="h-1.5 w-20 bg-brand-600 rounded-full mt-3"></div>
        </div>

        <div class="bg-white dark:bg-dark-800 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-700/50 mb-12 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 via-brand-600 to-brand-400"></div>

            <div class="flex items-center gap-4 mb-10 text-right">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white">إضافة محتوى جديد</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">أنشئ مقالاً تعليمياً أو خبراً للمنصة</p>
                </div>
            </div>

            <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 text-right">

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">نوع المحتوى <span class="text-rose-500">*</span></label>
                        <select name="type" class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('type') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm h-[60px]">
                            <option value="article" {{ old('type') == 'article' ? 'selected' : '' }}>مقال</option>
                            <option value="news" {{ old('type') == 'news' ? 'selected' : '' }}>خبر</option>
                        </select>
                        @if($errors->has('type') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('type') }}</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">تاريخ النشر <span class="text-rose-500">*</span></label>
                        <input type="date" name="published_date" value="{{ old('published_date', date('Y-m-d')) }}"
                            class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('published_date') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none font-bold text-sm transition-all h-[60px]">
                        @if($errors->has('published_date') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('published_date') }}</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">صورة الغلاف <span class="text-rose-500">*</span></label>
                        <div class="relative w-full h-[60px] rounded-2xl border-2 border-dashed @if($errors->has('image') && !session('edit_id')) border-rose-500 bg-rose-50 @else border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-dark-900 hover:border-brand-500 @endif transition-all flex items-center justify-center overflow-hidden cursor-pointer group"
                            @click="$refs.createFileInput.click()">
                            <input type="file" name="image" x-ref="createFileInput" @change="handleImageSelected" accept="image/*" class="hidden">

                            <div x-show="!imagePreview" class="flex items-center gap-2 text-slate-400 group-hover:text-brand-500 transition-colors pointer-events-none">
                                <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                                <span class="text-sm font-bold">انقر لاختيار صورة</span>
                            </div>

                            <div x-show="imagePreview" class="absolute inset-0 w-full h-full" x-cloak>
                                <img :src="imagePreview" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-white text-xs font-bold"><i class="fa-solid fa-pen mr-1"></i>تغيير</span>
                                </div>
                            </div>
                        </div>
                        @if($errors->has('image') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('image') }}</span>
                        @endif
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">العنوان الرئيسي <span class="text-rose-500">*</span></label>
                        <input type="text" name="main_title" value="{{ old('main_title') }}" placeholder="أدخل العنوان الجذاب هنا..."
                            class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('main_title') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none font-bold text-sm transition-all h-[60px]">
                        @if($errors->has('main_title') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('main_title') }}</span>
                        @endif
                    </div>

                    <div class="md:col-span-1 space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">عنوان فرعي (اختياري)</label>
                        <input type="text" name="second_title" value="{{ old('second_title') }}" placeholder="تفاصيل إضافية قصيرة..."
                            class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('second_title') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none font-bold text-sm transition-all h-[60px]">
                        @if($errors->has('second_title') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('second_title') }}</span>
                        @endif
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">المحتوى بالتفصيل <span class="text-rose-500">*</span></label>
                        <textarea name="description" rows="5" placeholder="اكتب تفاصيل المقال أو الخبر هنا..."
                            class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('description') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none font-medium text-sm leading-loose transition-all">{{ old('description') }}</textarea>
                        @if($errors->has('description') && !session('edit_id'))
                            <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('description') }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 dark:border-slate-700/50 pt-6">
                    <button type="submit" :disabled="loading"
                        class="bg-brand-600 text-white px-12 py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="loading ? 'جاري النشر...' : 'نشر المحتوى الآن'"></span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
            <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/30 dark:bg-dark-900/20">
                <h3 class="text-xl font-black text-slate-800 dark:text-white">سجل المواضيع</h3>

                <div class="relative w-full md:w-96">
                    <input type="text" x-model="search" placeholder="ابحث في العناوين..."
                        class="w-full pl-10 pr-4 py-3.5 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 focus:border-brand-500 outline-none transition-all text-sm font-bold shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto min-h-[450px]">
                <table class="w-full text-right border-collapse text-nowrap">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                            <th class="px-8 py-5 text-center w-24">الغلاف</th>
                            <th class="px-8 py-5">الموضوع</th>
                            <th class="px-8 py-5 text-center">النوع</th>
                            <th class="px-8 py-5 text-center">التاريخ</th>
                            <th class="px-8 py-5 text-center w-40">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        @forelse($articles as $item)
                            <tr x-show="search === '' || '{{ strtolower($item->main_title) }}'.includes(search.toLowerCase())"
                                class="hover:bg-slate-50/50 dark:hover:bg-dark-900/40 transition-all group">

                                <td class="px-8 py-5 text-center">
                                    <div class="w-16 h-12 rounded-xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700">
                                        <img src="{{ asset('storage/' . $item->image) }}" class="w-full h-full object-cover">
                                    </div>
                                </td>

                                <td class="px-8 py-5">
                                    <div class="flex flex-col max-w-xs md:max-w-md overflow-hidden whitespace-normal">
                                        <span class="text-sm font-black text-slate-700 dark:text-slate-200 group-hover:text-brand-600 transition-colors line-clamp-1">{{ $item->main_title }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 mt-1 line-clamp-1">{{ $item->second_title }}</span>
                                    </div>
                                </td>

                                <td class="px-8 py-5 text-center">
                                    @if ($item->type == 'article')
                                        <span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 text-[10px] font-black uppercase">مقال</span>
                                    @else
                                        <span class="px-3 py-1 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400 text-[10px] font-black uppercase">خبر</span>
                                    @endif
                                </td>

                                <td class="px-8 py-5 text-center">
                                    <span class="text-xs font-bold text-slate-500">{{ $item->published_date->format('Y/m/d') }}</span>
                                </td>

                                <td class="px-8 py-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal(@js($item))" :disabled="loading" title="تعديل"
                                            class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-blue-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <button @click="itemToDelete = '{{ $item->id }}'; deleteModal = true" :disabled="loading" title="حذف"
                                            class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-rose-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic">لا توجد بيانات متاحة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div x-show="search !== '' && !Array.from($el.previousElementSibling.querySelectorAll('tbody tr')).some(el => el.style.display !== 'none')"
                     x-cloak class="px-6 py-16 text-center border-t border-slate-100 dark:border-slate-700/50">
                    <p class="text-slate-500 font-medium">لا توجد نتائج مطابقة لبحثك.</p>
                </div>
            </div>

            @if ($articles->hasPages())
                <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>

        <template x-teleport="body">
            <div>
                <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                    <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-4xl rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right overflow-y-auto max-h-[90vh]">
                        <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white">تعديل محتوى الموضوع</h3>
                            <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                        </div>

                        <form :action="'{{ url('admin/articles') }}/' + itemToEdit.id" method="POST" enctype="multipart/form-data" @submit="loading = true">
                            @csrf @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">نوع المحتوى</label>
                                    <select name="type" x-model="itemToEdit.type"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('type')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('type')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                        <option value="article">مقال</option>
                                        <option value="news">خبر</option>
                                    </select>
                                    <template x-if="@json($errors->has('type')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('type') }}</span>
                                    </template>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">تاريخ النشر</label>
                                    <input type="date" name="published_date" x-model="itemToEdit.published_date"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('published_date')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('published_date')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                    <template x-if="@json($errors->has('published_date')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('published_date') }}</span>
                                    </template>
                                </div>

                                <div class="md:col-span-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">العنوان الرئيسي</label>
                                    <input type="text" name="main_title" x-model="itemToEdit.main_title"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('main_title')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('main_title')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                    <template x-if="@json($errors->has('main_title')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('main_title') }}</span>
                                    </template>
                                </div>

                                <div class="md:col-span-1 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">عنوان فرعي (اختياري)</label>
                                    <input type="text" name="second_title" x-model="itemToEdit.second_title"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('second_title')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('second_title')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                    <template x-if="@json($errors->has('second_title')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('second_title') }}</span>
                                    </template>
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">المحتوى الكامل</label>
                                    <textarea name="description" rows="5" x-model="itemToEdit.description"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('description')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('description')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-medium leading-loose transition-all"></textarea>
                                    <template x-if="@json($errors->has('description')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('description') }}</span>
                                    </template>
                                </div>

                                <div class="md:col-span-2 p-6 bg-slate-50 dark:bg-dark-900/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700"
                                     :class="{ 'border-rose-500 bg-rose-50 dark:bg-rose-900/10': @json($errors->has('image')) && @json(session('edit_id')) == itemToEdit.id }">
                                    <label class="block text-xs font-black text-slate-400 uppercase mb-4">تغيير الصورة الحالية (اختياري)</label>
                                    <div class="flex items-center gap-6">
                                        <div class="w-32 h-24 rounded-xl overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 bg-white relative group cursor-pointer"
                                            @click="$refs.editFileInput.click()">
                                            <img :src="itemToEdit.image_url" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <i class="fa-solid fa-camera text-white text-xl"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" name="image" x-ref="editFileInput" @change="handleEditImageSelected" class="hidden" accept="image/*">
                                            <button type="button" @click="$refs.editFileInput.click()"
                                                class="px-6 py-3 bg-white dark:bg-dark-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-brand-50 dark:hover:bg-brand-900/20 hover:text-brand-600 hover:border-brand-200 transition-all shadow-sm">
                                                <i class="fa-solid fa-image ml-2"></i> اختر صورة جديدة
                                            </button>
                                            <p class="text-[10px] text-slate-400 mt-2 font-bold">يفضل استخدام أبعاد عرضية بجودة عالية</p>

                                            <template x-if="@json($errors->has('image')) && @json(session('edit_id')) == itemToEdit.id">
                                                <span class="text-[10px] text-rose-500 font-bold mt-2 block">{{ $errors->first('image') }}</span>
                                            </template>
                                        </div>
                                    </div>
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
                    <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                        <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                            <i class="fa-solid fa-trash-can"></i>
                        </div>
                        <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف المحتوى</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-2">هل أنت متأكد من حذف هذا الموضوع نهائياً؟ لا يمكن التراجع عن هذا الإجراء.</p>
                        <form :action="'{{ url('admin/articles') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
