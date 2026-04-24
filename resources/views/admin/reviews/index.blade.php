@extends('admin.layouts.master')
@section('title', 'إدارة آراء المبدعين')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    deleteModal: false,
    search: '',
    showReview: true,
    itemToDelete: '',
    previewItem: null,

    init() {
        @if($reviews->count() > 0)
            this.previewItem = @js($reviews->first());
        @endif
    }
}">

    <div class="mb-10">
        <div class="flex flex-col justify-between items-start gap-4 mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                    آراء المبدعين (Reviews)
                </h2>
                <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 text-right">
            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">إجمالي الآراء</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['total'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">المعتمدة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['approved'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-all hover:shadow-md group">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="flex-1">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">قيد المراجعة</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">{{ $stats['pending'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-10 w-full" x-show="previewItem" x-transition>
        <div class="mb-4 flex items-center justify-between px-1">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة التصميم النهائي للموقع
            </h3>
        </div>

        <div class="bg-slate-50 dark:bg-dark-900 rounded-[2.5rem] p-6 md:p-12 border border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center min-h-[300px] overflow-hidden">
            <div class="w-full max-w-2xl bg-white dark:bg-dark-800 rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none relative transition-all duration-500">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <template x-for="i in 5">
                                <i class="fa-solid fa-star text-lg" :class="i <= (previewItem ? previewItem.rating : 0) ? 'text-amber-400' : 'text-slate-200 dark:text-slate-700'"></i>
                            </template>
                        </div>
                        <p class="text-slate-800 dark:text-slate-200 font-bold leading-relaxed text-lg" x-text="previewItem ? previewItem.message : 'نص المراجعة سيظهر هنا...'"></p>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <div class="text-brand-600 opacity-80">
                            <i class="fa-solid fa-quote-right text-7xl"></i>
                        </div>
                        <div class="flex items-center gap-4 text-right">
                            <div class="flex flex-col">
                                <span class="text-lg font-black text-slate-800 dark:text-white" x-text="previewItem ? previewItem.name : 'اسم المبدع'"></span>
                                <span class="text-brand-600 font-bold text-sm">مبدع مشارك</span>
                            </div>
                            <div class="relative">
                                <div class="w-16 h-16 rounded-full overflow-hidden border-4 border-white dark:border-dark-800 shadow-xl z-10 relative bg-slate-100">
                                    <img :src="'https://ui-avatars.com/api/?name=' + (previewItem ? previewItem.name : 'M') + '&background=6366f1&color=fff'" class="w-full h-full object-cover">
                                </div>
                                <div class="absolute inset-0 bg-brand-500 rounded-full blur-lg opacity-40 scale-110"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white text-right">سجل آراء المبدعين</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث باسم المبدع..."
                    class="w-full px-5 py-3 pl-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all shadow-sm font-medium text-sm text-right">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead class="bg-slate-50 dark:bg-dark-900/50 border-b border-slate-100 dark:border-slate-700/50">
                    <tr>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-16 text-center">#</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-right">المبدع</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-24 text-center">التقييم</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase w-24 text-center">الحالة</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase text-center w-32">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($reviews as $index => $item)
                    <tr @click="previewItem = @js($item); showReview = true"
                        x-show="search === '' || '{{ strtolower($item->name) }}'.includes(search.toLowerCase())"
                        class="transition-all duration-300 cursor-pointer border-r-4 group"
                        :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-50/80 dark:bg-brand-900/30 border-brand-500' : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                        <td class="px-6 py-5 text-center">
                            <div class="w-8 h-8 mx-auto flex items-center justify-center rounded-lg text-xs font-bold transition-all duration-300 shadow-sm"
                                 :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-600 text-white' : 'bg-slate-100 dark:bg-dark-900 text-slate-400'">
                                {{ $index + 1 }}
                            </div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex flex-col">
                                <span class="text-sm md:text-base font-bold transition-colors"
                                    :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-700 dark:text-brand-400' : 'text-slate-800 dark:text-white'">
                                    {{ $item->name }}
                                </span>
                                <span class="text-[10px] font-medium text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="inline-flex items-center gap-1 bg-amber-50 dark:bg-amber-900/20 px-2 py-1 rounded-lg">
                                <i class="fa-solid fa-star text-[10px] text-amber-400"></i>
                                <span class="text-xs font-black text-amber-600 dark:text-amber-500">{{ $item->rating }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($item->is_approved)
                                <span class="text-emerald-500 text-xs font-bold flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> معتمد
                                </span>
                            @else
                                <span class="text-slate-400 text-xs font-bold flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-minus"></i> مراجعة
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <form action="{{ route('admin.reviews.toggle', $item->id) }}" method="POST" @submit="loading = true">
                                    @csrf
                                    <button type="submit"
                                        :disabled="loading"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center transition-all border border-slate-200 dark:border-slate-700/50 shadow-sm {{ $item->is_approved ? 'bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white' }}"
                                        :class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                                        <i class="fa-solid text-xs" :class="loading ? 'fa-circle-notch animate-spin' : '{{ $item->is_approved ? 'fa-eye-slash' : 'fa-check' }}'"></i>
                                    </button>
                                </form>
                                <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true"
                                    :disabled="loading"
                                    class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all border border-slate-200 dark:border-slate-700/50">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-20 text-center text-slate-400">لا توجد مراجعات حالياً.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
        <div class="p-6 border-t border-slate-100 dark:border-slate-700/50">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid" :class="loading ? 'fa-circle-notch animate-spin' : 'fa-trash-can'"></i>
                </div>
                <h4 class="text-xl font-bold mb-2 text-slate-800 dark:text-white">حذف التقييم</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد من حذف هذا الرأي نهائياً؟</p>
                <form :action="'{{ url('admin/reviews') }}/' + itemToDelete" method="POST" @submit="loading = true">
                    @csrf @method('DELETE')
                    <div class="flex gap-3">
                        <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-3.5 rounded-xl font-bold hover:bg-rose-700 shadow-lg transition-all flex items-center justify-center gap-2" :class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                            <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                            <span x-text="loading ? 'جاري الحذف...' : 'نعم، احذف'"></span>
                        </button>
                        <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 py-3.5 rounded-xl font-bold hover:bg-slate-200">تراجع</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
