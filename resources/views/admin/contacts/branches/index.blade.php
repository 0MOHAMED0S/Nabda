@extends('admin.layouts.master')
@section('title', 'إدارة الفروع')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    editModal: false,
    deleteModal: false,
    search: '',

    form: {
        name: @js(old('name', '')),
        address: @js(old('address', '')),
        phone: @js(old('phone', '')),
        lat: @js(old('lat', '')),
        lng: @js(old('lng', ''))
    },

    itemToEdit: { id: '', name: '', address: '', phone: '', lat: '', lng: '' },
    itemToDelete: '',

    previewItem: null,
    previewIndex: 1,

    init() {
        @if($branches->count() > 0)
            this.previewItem = @js($branches->first());
            this.previewIndex = 1;
        @endif

        // إعادة فتح نافذة التعديل في حال وجود أخطاء Validation
        @if($errors->any() && session('edit_id'))
            this.itemToEdit = {
                id: '{{ session('edit_id') }}',
                name: @js(old('name')),
                address: @js(old('address')),
                phone: @js(old('phone')),
                lat: @js(old('lat', '')),
                lng: @js(old('lng', ''))
            };
            this.editModal = true;
        @endif
    }
}">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-10">
        <div>
            <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                فروعنا الرسمية
                <span class="bg-brand-500/10 text-brand-600 dark:text-brand-400 text-sm py-1.5 px-4 rounded-full font-bold">
                    {{ $branches->count() }}
                </span>
            </h2>
            <div class="h-1.5 w-20 bg-brand-600 rounded-full mt-3"></div>
            <p class="text-slate-500 mt-2 font-medium">إدارة مواقع الفروع وبيانات التواصل الجغرافية</p>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10 w-full relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 via-brand-600 to-brand-400"></div>

        <div class="flex items-center gap-4 mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-6">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
                <h3 class="text-xl font-black text-slate-800 dark:text-white">إضافة فرع جديد</h3>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">أدخل بيانات الفرع الجغرافية ومعلومات التواصل</p>
            </div>
        </div>

        <form action="{{ route('admin.branches.store') }}" method="POST" @submit="loading = true">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 text-right">

                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-400 mr-1 uppercase">اسم الفرع <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" placeholder="مثال: فرع القاهرة"
                        class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('name') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm h-[60px]">
                    @if($errors->has('name') && !session('edit_id'))
                        <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-400 mr-1 uppercase">رقم الهاتف <span class="text-rose-500">*</span></label>
                    <input type="text" name="phone" x-model="form.phone" placeholder="+20 1xx xxx xxxx"
                        class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('phone') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm h-[60px]" dir="ltr">
                    @if($errors->has('phone') && !session('edit_id'))
                        <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('phone') }}</span>
                    @endif
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-400 mr-1 uppercase">العنوان <span class="text-rose-500">*</span></label>
                    <input type="text" name="address" x-model="form.address" placeholder="المنطقة - الشارع"
                        class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('address') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-sm h-[60px]">
                    @if($errors->has('address') && !session('edit_id'))
                        <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('address') }}</span>
                    @endif
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-400 mr-1 uppercase">خط العرض (Lat)</label>
                    <input type="text" name="lat" x-model="form.lat" placeholder="30.059488"
                        class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('lat') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none text-sm font-medium h-[60px]">
                    @if($errors->has('lat') && !session('edit_id'))
                        <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('lat') }}</span>
                    @endif
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-400 mr-1 uppercase">خط الطول (Lng)</label>
                    <input type="text" name="lng" x-model="form.lng" placeholder="31.348315"
                        class="w-full px-6 py-4 rounded-2xl border-2 @if($errors->has('lng') && !session('edit_id')) border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @endif bg-slate-50 dark:bg-dark-900 outline-none text-sm font-medium h-[60px]">
                    @if($errors->has('lng') && !session('edit_id'))
                        <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('lng') }}</span>
                    @endif
                </div>

                <div class="flex items-end">
                    <button type="submit" :disabled="loading" class="w-full bg-brand-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-1 transition-all flex items-center justify-center gap-3 h-[60px] disabled:opacity-70 disabled:cursor-not-allowed">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="loading ? 'جاري الحفظ...' : 'حفظ الفرع الجديد'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-12 w-full">
        <div class="mb-6 flex items-center justify-between px-2">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة التصميم الفعلي للموقع
            </h3>
        </div>

        <div class="bg-slate-100 dark:bg-dark-900/50 rounded-[3rem] p-10 md:p-16 border border-slate-200/50 dark:border-slate-700/30 flex flex-col items-center justify-center min-h-[300px] overflow-hidden relative">
            <h4 class="text-3xl font-black text-brand-600 mb-8">فروعنا</h4>

            <div class="relative bg-white dark:bg-dark-800 border border-slate-100 dark:border-slate-700/80 rounded-3xl p-8 md:p-10 w-full max-w-lg text-right shadow-xl shadow-slate-200/50 dark:shadow-none transition-all duration-500 overflow-hidden">
                <span class="absolute top-1/2 left-6 -translate-y-1/2 text-[10rem] md:text-[14rem] font-black text-slate-50 dark:text-white/5 select-none z-0 pointer-events-none"
                      x-text="previewIndex"></span>

                <div class="relative z-10 space-y-4">
                    <h4 class="text-2xl md:text-3xl font-black text-brand-600 leading-tight"
                        x-text="previewItem ? previewItem.name : (form.name || 'اسم الفرع')"></h4>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-end gap-2 text-slate-700 dark:text-slate-200 font-bold">
                            <span x-text="previewItem ? previewItem.address : (form.address || 'العنوان...')"></span>
                            <span class="text-brand-500">:العنوان</span>
                        </div>
                        <div class="flex items-center justify-end gap-2 text-slate-700 dark:text-slate-200 font-bold">
                            <span dir="ltr" x-text="previewItem ? previewItem.phone : (form.phone || '+20...')"></span>
                            <span class="text-brand-500">:الهاتف</span>
                        </div>
                    </div>

                    <template x-if="(previewItem && (previewItem.lat || previewItem.lng)) || form.lat || form.lng">
                        <div class="mt-6 pt-4 border-t border-slate-50 dark:border-slate-700 flex gap-4 justify-center text-[10px] font-black uppercase text-brand-500/60 tracking-tighter">
                            <span>Lat: <span x-text="previewItem ? previewItem.lat : form.lat"></span></span>
                            <div class="w-px h-3 bg-slate-200 dark:bg-slate-700"></div>
                            <span>Lng: <span x-text="previewItem ? previewItem.lng : form.lng"></span></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full">
        <div class="p-8 border-b border-slate-50 dark:border-slate-700/50 bg-slate-50/30 dark:bg-dark-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <h3 class="text-xl font-black text-slate-800 dark:text-white">قائمة الفروع</h3>
            <div class="w-full md:w-80 relative">
                <input type="text" x-model="search" placeholder="ابحث عن فرع..."
                    class="w-full px-6 py-3.5 pr-14 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-900 focus:border-brand-500 outline-none transition-all shadow-sm font-bold text-sm">
                <i class="fa-solid fa-magnifying-glass absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead class="bg-slate-50/50 dark:bg-dark-900/50 border-b">
                    <tr>
                        <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase w-20 text-center">#</th>
                        <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase">اسم الفرع</th>
                        <th class="px-8 py-6 text-xs font-black text-slate-400 uppercase text-center w-40">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($branches as $index => $item)
                    <tr @click="previewItem = @js($item); previewIndex = {{ $index + 1 }}"
                        x-show="search === '' || '{{ $item->name }}'.includes(search)"
                        class="transition-all duration-300 cursor-pointer border-r-4 group"
                        :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-50/50 dark:bg-brand-900/20 border-brand-500' : 'border-transparent hover:bg-slate-50 dark:hover:bg-dark-900/40'">

                        <td class="px-8 py-6 text-center">
                            <div class="w-10 h-10 mx-auto flex items-center justify-center rounded-xl text-sm font-black transition-all duration-300"
                                 :class="previewItem && previewItem.id === {{ $item->id }} ? 'bg-brand-600 text-white scale-110 shadow-lg' : 'bg-slate-100 dark:bg-dark-900 text-slate-400'">
                                {{ $index + 1 }}
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <h5 class="text-base font-black transition-colors" :class="previewItem && previewItem.id === {{ $item->id }} ? 'text-brand-700 dark:text-brand-400' : 'text-slate-800 dark:text-white'">{{ $item->name }}</h5>
                            <p class="text-xs text-slate-400 font-bold mt-1">{{ $item->address }}</p>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center justify-center gap-3">
                                <button @click.stop="itemToEdit = @js($item); editModal = true" class="w-10 h-10 rounded-xl bg-white dark:bg-dark-700 text-blue-500 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all shadow-sm border border-slate-100 dark:border-slate-600"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button @click.stop="itemToDelete = '{{ $item->id }}'; deleteModal = true" class="w-10 h-10 rounded-xl bg-white dark:bg-dark-700 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-sm border border-slate-100 dark:border-slate-600"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-8 py-20 text-center text-slate-400 font-bold">لا يوجد فروع مسجلة.</td></tr>
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
            <div x-show="editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (editModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-2xl rounded-[3rem] shadow-2xl p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right">
                    <div class="flex justify-between items-center mb-10 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white">تعديل بيانات الفرع</h3>
                        <button @click="editModal = false" :disabled="loading" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fa-solid fa-xmark text-2xl"></i></button>
                    </div>

                    <form :action="'{{ url('admin/branches') }}/' + itemToEdit.id" method="POST" @submit="loading = true">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                            <div class="md:col-span-2 space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">اسم الفرع <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" x-model="itemToEdit.name"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('name')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('name')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                <template x-if="@json($errors->has('name')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('name') }}</span>
                                </template>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                                <input type="text" name="phone" x-model="itemToEdit.phone" dir="ltr"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('phone')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('phone')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                <template x-if="@json($errors->has('phone')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('phone') }}</span>
                                </template>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">العنوان <span class="text-rose-500">*</span></label>
                                <input type="text" name="address" x-model="itemToEdit.address"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('address')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('address')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all text-sm h-[60px]">
                                <template x-if="@json($errors->has('address')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('address') }}</span>
                                </template>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">خط العرض (Lat)</label>
                                <input type="text" name="lat" x-model="itemToEdit.lat"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('lat')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('lat')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-medium transition-all text-sm h-[60px]">
                                <template x-if="@json($errors->has('lat')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('lat') }}</span>
                                </template>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mr-1">خط الطول (Lng)</label>
                                <input type="text" name="lng" x-model="itemToEdit.lng"
                                    :class="{
                                        'border-rose-500 ring-4 ring-rose-500/10': @json($errors->has('lng')) && @json(session('edit_id')) == itemToEdit.id,
                                        'border-slate-100 dark:border-slate-700 focus:border-brand-500': !(@json($errors->has('lng')) && @json(session('edit_id')) == itemToEdit.id)
                                    }"
                                    class="w-full px-6 py-4 rounded-2xl border-2 bg-slate-50 dark:bg-dark-900 outline-none font-medium transition-all text-sm h-[60px]">
                                <template x-if="@json($errors->has('lng')) && @json(session('edit_id')) == itemToEdit.id">
                                    <span class="text-rose-500 text-[11px] font-bold mr-1 block">{{ $errors->first('lng') }}</span>
                                </template>
                            </div>

                        </div>

                        <div class="flex gap-4 mt-12 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                            <button type="submit" :disabled="loading" class="flex-1 bg-brand-600 text-white py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري التحديث...' : 'تحديث البيانات'"></span>
                            </button>
                            <button type="button" @click="editModal = false" :disabled="loading" class="px-10 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-black hover:bg-slate-200 transition-all disabled:opacity-70">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div @click.away="!loading && (deleteModal = false)" class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                    <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف الفرع</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد من حذف هذا الفرع نهائياً؟ لا يمكن التراجع عن هذا الإجراء.</p>

                    <form :action="'{{ url('admin/branches') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
