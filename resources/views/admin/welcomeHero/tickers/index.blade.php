@extends('admin.layouts.master')
@section('title', 'شريط الأخبار المتحرك')

@section('content')

<style>
    /* أنيميشن احترافي للشريط المتحرك باللغة العربية (RTL) */
    @keyframes marquee-rtl {
        0% { transform: translateX(0); }
        100% { transform: translateX(50%); }
    }
    .ticker-container {
        overflow: hidden;
        white-space: nowrap;
        position: relative;
        direction: rtl;
        display: flex;
    }
    .ticker-track {
        display: flex;
        width: max-content;
        animation: marquee-rtl 30s linear infinite;
    }
    .ticker-container:hover .ticker-track {
        animation-play-state: paused;
    }
</style>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    search: '',
    newItemContent: '{{ old('content') }}',
    newItemActive: true,
    itemToEdit: { id: '', content: '', is_active: true },
    itemToDelete: '',

    init() {
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = {
                id: '{{ session('edit_id') }}',
                content: @js(old('content')),
                is_active: Boolean({{ old('is_active', true) ? 'true' : 'false' }})
            };
            this.editModal = true;
        @endif
    }
}">

    <div class="flex flex-col justify-between items-start gap-4 mb-8">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                شريط النصوص المتحرك
            </h2>
            <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            <p class="text-slate-500 mt-2 text-sm">تحكم في الجمل الإعلانية التي تظهر في الشريط المتحرك أعلى الموقع.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-10">
        <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">إجمالي الجمل</p>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white">{{ $tickers->count() }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-solid fa-list-ul"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">الجمل المفعلة</p>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white">{{ $tickers->where('is_active', true)->count() }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-regular fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">الجمل الموقوفة</p>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white">{{ $tickers->where('is_active', false)->count() }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-500 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-regular fa-circle-xmark"></i>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-message"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">إضافة جملة جديدة</h3>
        </div>

        <form action="{{ route('admin.tickers.store') }}" method="POST" @submit="loading = true">
            @csrf
            <div class="flex flex-col md:flex-row gap-4 md:items-start">
                <div class="flex-1 w-full">
                    <input type="text" name="content" x-model="newItemContent" placeholder="مثال: معاً نصنع فرقاً ونمنح الأمل لمن يحتاجه..."
                        class="w-full px-5 py-4 rounded-xl border @if($errors->has('content') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-medium text-slate-800 dark:text-white">
                    @if($errors->has('content') && !session('edit_id'))
                        <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('content') }}</span>
                    @endif
                </div>

                <div class="shrink-0 flex items-center justify-center bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 px-4 py-4 rounded-xl">
                    <label class="flex items-center cursor-pointer gap-3">
                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300" x-text="newItemActive ? 'مفعل' : 'موقوف'"></span>
                        <div class="relative">
                            <input type="checkbox" name="is_active" class="sr-only" x-model="newItemActive" value="1">
                            <div class="block w-12 h-7 rounded-full transition-colors" :class="newItemActive ? 'bg-brand-500' : 'bg-slate-300 dark:bg-slate-600'"></div>
                            <div class="absolute right-1 top-1 bg-white w-5 h-5 rounded-full transition-transform" :class="newItemActive ? '-translate-x-5' : 'translate-x-0'"></div>
                        </div>
                    </label>
                </div>

                <button type="submit" :disabled="loading" class="w-full md:w-auto shrink-0 bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <template x-if="!loading"><i class="fa-solid fa-plus"></i></template>
                    <span x-text="loading ? 'جاري الإضافة...' : 'إضافة للشريط'"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة حية للشريط المتحرك
            </h3>
            <span class="text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300 px-2 py-1 rounded-md font-bold">يعرض المفعل فقط</span>
        </div>

        <div class="bg-[#2b3c5a] border-4 border-white dark:border-dark-800 shadow-xl rounded-2xl py-4 overflow-hidden relative" dir="rtl">
            <div class="ticker-container">
                <div class="ticker-track">
                    @for ($i = 0; $i < 2; $i++)
                    <div class="flex items-center shrink-0">
                        @foreach($tickers->where('is_active', true) as $ticker)
                            <span class="text-white font-bold text-sm md:text-base tracking-wide flex items-center shrink-0 px-4">
                                {{ $ticker->content }}
                                <i class="fa-solid fa-heart text-white/50 text-xs mx-4"></i>
                            </span>
                        @endforeach

                        <template x-if="newItemContent.trim() !== '' && newItemActive">
                            <span class="flex items-center gap-4 shrink-0 px-4 text-brand-300 drop-shadow-[0_0_8px_rgba(167,139,250,0.8)] font-bold text-sm md:text-base">
                                <span x-text="newItemContent"></span>
                                <i class="fa-solid fa-heart text-white/50 text-xs mx-2"></i>
                            </span>
                        </template>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">الجمل المضافة مسبقاً</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث عن جملة..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-2 focus:ring-brand-500 outline-none transition-all shadow-sm font-medium text-sm">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">الجملة</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-24">الحالة</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($tickers as $index => $ticker)
                    <tr x-show="search === '' || '{{ $ticker->content }}'.includes(search)" class="hover:bg-slate-50/50 dark:hover:bg-dark-900/20 transition-colors group">
                        <td class="px-6 py-5 text-sm font-bold text-brand-600 text-center">{{ $index + 1 }}</td>
                        <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-slate-200 leading-relaxed">
                            <i class="fa-solid fa-quote-right text-brand-400/50 ml-2 text-xs"></i>
                            {{ $ticker->content }}
                        </td>

                        <td class="px-6 py-5 text-center">
                            @if($ticker->is_active)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> مفعل
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200/50 dark:border-rose-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> موقوف
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="itemToEdit = { id: '{{ $ticker->id }}', content: @js($ticker->content), is_active: {{ $ticker->is_active ? 'true' : 'false' }} }; editModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-blue-500 hover:text-white hover:shadow-lg hover:shadow-blue-500/20 transition-all" title="تعديل">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button @click="itemToDelete = '{{ $ticker->id }}'; deleteModal = true"
                                    class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white hover:shadow-lg hover:shadow-rose-500/20 transition-all" title="حذف">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-dark-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-message text-slate-300 dark:text-slate-600 text-2xl"></i>
                            </div>
                            <p class="text-slate-500 font-medium">لم يتم إضافة أي جمل حتى الآن.</p>
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
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-lg rounded-3xl shadow-2xl p-8 border border-slate-100 dark:border-slate-700 transform transition-all text-right">

                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-700/50 pb-4">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل الجملة</h3>
                        <button type="button" @click="editModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <form :action="'{{ url('admin/tickers') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">نص الجملة</label>
                            <input type="text" name="content" x-model="itemToEdit.content"
                                class="w-full px-5 py-4 rounded-xl border bg-slate-50 dark:bg-dark-900 outline-none transition-all font-medium text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                                :class="{'border-rose-500': '{{ session('edit_id') }}' == itemToEdit.id && {{ $errors->has('content') ? 'true' : 'false' }}, 'border-slate-200 dark:border-slate-700': !('{{ session('edit_id') }}' == itemToEdit.id && {{ $errors->has('content') ? 'true' : 'false' }}) }">

                            @if($errors->has('content') && session('edit_id'))
                            <template x-if="'{{ session('edit_id') }}' == itemToEdit.id">
                                <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $errors->first('content') }}</span>
                            </template>
                            @endif
                        </div>

                        <div class="mb-8 flex items-center justify-between bg-slate-50 dark:bg-dark-900 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div>
                                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300">حالة التفعيل</span>
                                <span class="text-xs text-slate-400">تحكم في ظهور هذا الخبر للزوار</span>
                            </div>
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="is_active" class="sr-only" x-model="itemToEdit.is_active" value="1">
                                    <div class="block w-12 h-7 rounded-full transition-colors" :class="itemToEdit.is_active ? 'bg-brand-500' : 'bg-slate-300 dark:bg-slate-600'"></div>
                                    <div class="absolute right-1 top-1 bg-white w-5 h-5 rounded-full transition-transform" :class="itemToEdit.is_active ? '-translate-x-5' : 'translate-x-0'"></div>
                                </div>
                            </label>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري التحديث...' : 'حفظ التعديلات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading" class="px-6 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-xl font-bold hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors">إلغاء</button>
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
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من رغبتك في حذف هذه الجملة؟ لن يراها زوار الموقع مجدداً.</p>

                    <form :action="'{{ url('admin/tickers') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
