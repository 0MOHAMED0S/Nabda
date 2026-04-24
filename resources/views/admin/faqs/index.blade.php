@extends('admin.layouts.master')
@section('title', 'إدارة الأسئلة الشائعة')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    search: '',

    // Accordion State for Preview
    showAnswer: true, // تظهر الإجابة مفتوحة افتراضياً عند اختيار سؤال

    // Form State for Live Preview
    form: {
        question: @js(old('question', '')),
        answer: @js(old('answer', ''))
    },

    itemToEdit: { id: '', question: '', answer: '', order: 0 },
    itemToDelete: '',

    // Active Selection for Preview
    previewItem: null,

    init() {
        @if($faqs->count() > 0)
            this.previewItem = @js($faqs->first());
        @endif

        // إعادة فتح نافذة التعديل في حال وجود أخطاء في الـ Validation
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = {
                id: '{{ session('edit_id') }}',
                question: @js(old('question')),
                answer: @js(old('answer')),
                order: '{{ old('order', 0) }}'
            };
            this.editModal = true;
        @endif
    }
}">

    <div class="flex flex-col justify-between items-start gap-4 mb-8 md:mb-10">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                الأسئلة الشائعة (FAQs)
                <span class="bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 text-sm py-1 px-3 rounded-xl font-bold">
                    {{ $faqs->count() }} سؤالاً
                </span>
            </h2>
            <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 md:p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10 w-full">
        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">إضافة سؤال جديد</h3>
        </div>

        <form action="{{ route('admin.faqs.store') }}" method="POST" @submit="loading = true">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8 text-right">

                <div class="md:col-span-8">
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase tracking-wider">نص السؤال</label>
                    <input type="text" name="question" x-model="form.question" placeholder="ما هي شروط التبرع؟"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('question') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">
                    @if($errors->has('question') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('question') }}</span>
                    @endif
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase tracking-wider">الترتيب</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}"
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('order') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">
                    @if($errors->has('order') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('order') }}</span>
                    @endif
                </div>

                <div class="md:col-span-12">
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase tracking-wider">الإجابة</label>
                    <textarea name="answer" x-model="form.answer" rows="3" placeholder="اشرح الإجابة بشكل وافي..."
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('answer') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm leading-relaxed"></textarea>
                    @if($errors->has('answer') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('answer') }}</span>
                    @endif
                </div>

            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
                <button type="submit" :disabled="loading" class="bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all text-base disabled:opacity-70 disabled:cursor-not-allowed">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <span x-text="loading ? 'جاري الإضافة...' : 'حفظ وإضافة للسجل'"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-10 w-full">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة تفاعلية (اضغط على السهم في المعاينة لتجربة العرض)
            </h3>
        </div>

        <div class="bg-slate-50 dark:bg-dark-900 rounded-[2.5rem] p-8 md:p-12 border border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center min-h-[250px] overflow-hidden">
            <div class="w-full max-w-2xl flex flex-col gap-4">
                <div class="bg-white dark:bg-dark-800 rounded-3xl border-2 border-brand-500/20 shadow-xl shadow-brand-500/5 transition-all duration-500 overflow-hidden">
                    <div @click="showAnswer = !showAnswer" class="p-4 md:p-6 flex items-center gap-6 cursor-pointer select-none">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-brand-600 text-white flex items-center justify-center shrink-0 shadow-lg transition-transform duration-500"
                             :class="showAnswer ? 'rotate-90' : 'rotate-0'">
                            <i class="fa-solid fa-chevron-left text-lg"></i>
                        </div>

                        <div class="text-right flex-1">
                            <h4 class="text-base md:text-xl font-black text-slate-800 dark:text-white transition-colors"
                                x-text="previewItem ? previewItem.question : (form.question || 'عنوان السؤال سيظهر هنا')"></h4>
                        </div>
                    </div>

                    <div x-show="showAnswer"
                         x-collapse
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="px-8 md:px-24 pb-8 text-right border-t border-slate-50 dark:border-slate-700/50 pt-6">
                        <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 leading-loose font-medium whitespace-pre-line"
                           x-text="previewItem ? previewItem.answer : (form.answer || 'الإجابة الكاملة ستظهر هنا عند الضغط على السهم...')"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white text-right">سجل الأسئلة الشائعة</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث في الأسئلة..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all shadow-sm font-medium text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-16 text-center">#</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase">السؤال</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-20 text-center">الترتيب</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($faqs as $index => $item)
                    <tr @click="previewItem = @js($item); showAnswer = true"
                        x-show="search === '' || '{{ $item->question }}'.includes(search)"
                        class="transition-all duration-300 cursor-pointer border-r-4 group"
                        :class="previewItem && previewItem.id === {{ $item->id }}
                            ? 'bg-brand-50/80 dark:bg-brand-900/30 border-brand-500'
                            : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                        <td class="px-6 py-5 text-center">
                            <div class="w-8 h-8 mx-auto flex items-center justify-center rounded-lg text-xs font-bold transition-all duration-300 shadow-sm"
                                 :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-600 text-white' : 'bg-slate-100 dark:bg-dark-900 text-slate-400'">
                                {{ $index + 1 }}
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm md:text-base font-bold transition-colors"
                            :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-700 dark:text-brand-400' : 'text-slate-800 dark:text-white'">
                            {{ $item->question }}
                        </td>
                        <td class="px-6 py-5 text-center font-bold text-slate-400 italic">{{ $item->order }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <button @click.stop="itemToEdit = @js($item); editModal = true" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all border border-slate-200 dark:border-slate-700/50"><i class="fa-solid fa-pen"></i></button>
                                <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all border border-slate-200 dark:border-slate-700/50"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center text-slate-400">لا توجد بيانات.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div x-show="search !== '' && !Array.from($el.previousElementSibling.querySelectorAll('tbody tr')).some(el => el.style.display !== 'none')"
                 x-cloak class="px-6 py-16 text-center border-t border-slate-100 dark:border-slate-700/50">
                <p class="text-slate-500 font-medium">لا توجد نتائج مطابقة لبحثك.</p>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div>
            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-2xl rounded-[2.5rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right">
                    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل بيانات السؤال</h3>
                        <button type="button" @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/faqs') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">نص السؤال</label>
                                    <input type="text" name="question" x-model="itemToEdit.question"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('question')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !(@json($errors->has('question')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">
                                    <template x-if="@json($errors->has('question')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('question') }}</span>
                                    </template>
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الترتيب</label>
                                    <input type="number" name="order" x-model="itemToEdit.order"
                                        :class="{
                                            'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('order')) && @json(session('edit_id')) == itemToEdit.id,
                                            'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !(@json($errors->has('order')) && @json(session('edit_id')) == itemToEdit.id)
                                        }"
                                        class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm">
                                    <template x-if="@json($errors->has('order')) && @json(session('edit_id')) == itemToEdit.id">
                                        <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('order') }}</span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الإجابة</label>
                                <textarea name="answer" rows="5" x-model="itemToEdit.answer"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('answer')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10': !(@json($errors->has('answer')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm leading-relaxed"></textarea>
                                <template x-if="@json($errors->has('answer')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-[10px] text-rose-500 font-bold mt-1 mr-1 block">{{ $errors->first('answer') }}</span>
                                </template>
                            </div>

                            <div class="flex gap-3 pt-6 border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-brand-700 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                    <span x-text="loading ? 'جاري التحديث...' : 'حفظ التعديلات'"></span>
                                </button>
                                <button type="button" @click="editModal = false" :disabled="loading" class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[2rem] p-8 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl shadow-inner"><i class="fa-solid fa-trash-can"></i></div>
                    <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">حذف السؤال</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من حذف هذا السؤال؟</p>

                    <form :action="'{{ url('admin/faqs') }}/' + itemToDelete" method="POST" @submit="loading = true">
                        @csrf @method('DELETE')
                        <div class="flex gap-3">
                            <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-rose-700 shadow-lg transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحذف...' : 'نعم، احذف'"></span>
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
