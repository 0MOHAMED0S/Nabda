@extends('admin.layouts.master')
@section('title', 'إدارة فريق العمل')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    addModal: false,
    editModal: false,
    deleteModal: false,
    search: '',
    imageUrl: null,

    itemToEdit: { id: '', name: '', job_title: '', image: '' },
    itemToDelete: '',

    fileChosen(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => { this.imageUrl = e.target.result; };
    },

    init() {
        @if($errors->any())
            this.itemToEdit = {
                id: '{{ session('edit_id', '') }}',
                name: '{{ old('name') }}',
                job_title: '{{ old('job_title') }}'
            };
            @if(session('edit_id'))
                this.editModal = true;
                @php $failedMember = $members->firstWhere('id', session('edit_id')); @endphp
                @if($failedMember) this.imageUrl = '{{ asset('storage/' . $failedMember->image) }}'; @endif
            @else
                this.addModal = true;
            @endif
        @endif
    }
}">

    <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-12">
        <div class="text-center md:text-right">
            <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white flex items-center justify-center md:justify-start gap-3">
                فريق العمل
                <span class="bg-brand-500/10 text-brand-600 dark:text-brand-400 text-sm py-1 px-4 rounded-full font-bold">
                    {{ $members->count() }}
                </span>
            </h2>
            <p class="text-slate-500 mt-2 font-medium">الأشخاص المبدعون خلف نجاح المنصة</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
            <div class="relative group">
                <input type="text" x-model="search" placeholder="بحث عن عضو..."
                    class="w-full sm:w-72 px-6 py-4 pr-12 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-white dark:bg-dark-800 focus:border-brand-500 focus:ring-0 outline-none transition-all shadow-sm text-sm font-bold">
                <i class="fa-solid fa-magnifying-glass absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors"></i>
            </div>

            <button @click="itemToEdit = { id: '', name: '', job_title: '', image: '' }; imageUrl = null; addModal = true"
                class="bg-brand-600 hover:bg-brand-700 text-white px-8 py-4 rounded-2xl font-bold transition shadow-xl shadow-brand-500/20 flex items-center justify-center gap-2 active:scale-95 group">
                <i class="fa-solid fa-plus group-hover:rotate-90 transition-transform"></i>
                <span>إضافة مبدع جديد</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach ($members as $member)
            <div x-show="search === '' || '{{ strtolower($member->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($member->job_title) }}'.includes(search.toLowerCase())"
                class="group bg-white dark:bg-dark-800 rounded-[2.5rem] p-4 border border-slate-100 dark:border-slate-700/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative">

                <div class="absolute top-6 left-6 z-10 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <button @click="itemToEdit = @js($member); imageUrl = '{{ asset('storage/' . $member->image) }}'; editModal = true"
                        class="w-10 h-10 bg-white dark:bg-slate-700 text-blue-500 rounded-full shadow-lg flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                    </button>
                    <button @click="itemToDelete = '{{ $member->id }}'; deleteModal = true"
                        class="w-10 h-10 bg-white dark:bg-slate-700 text-rose-500 rounded-full shadow-lg flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </div>

                <div class="relative w-full aspect-square rounded-[2rem] overflow-hidden mb-6 bg-slate-50 dark:bg-dark-900 border-4 border-slate-50 dark:border-dark-700 shadow-inner">
                    <img src="{{ asset('storage/' . $member->image) }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>

                <div class="px-2 pb-4 text-center">
                    <h3 class="text-xl font-black text-slate-800 dark:text-white mb-1 group-hover:text-brand-600 transition-colors">{{ $member->name }}</h3>
                    <p class="text-slate-400 dark:text-slate-500 font-bold text-xs uppercase tracking-widest">{{ $member->job_title }}</p>
                </div>

                <div class="absolute bottom-4 right-1/2 translate-x-1/2 w-12 h-1 bg-slate-100 dark:bg-dark-700 rounded-full group-hover:w-24 group-hover:bg-brand-500 transition-all duration-500"></div>
            </div>
        @endforeach
    </div>

    <div x-show="search !== '' && !Array.from($el.querySelectorAll('.grid > div')).some(el => el.style.display !== 'none')"
         x-cloak class="py-24 text-center">
        <div class="w-24 h-24 bg-slate-100 dark:bg-dark-800 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-magnifying-glass text-slate-300 text-4xl"></i>
        </div>
        <h4 class="text-xl font-bold text-slate-800 dark:text-white">لا يوجد نتائج لبحثك</h4>
        <p class="text-slate-400 mt-1 font-medium">جرب البحث بكلمات أخرى أو أضف عضواً جديداً</p>
    </div>

    <template x-teleport="body">
        <div x-show="addModal || editModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="!loading && (addModal = false, editModal = false)"
                class="bg-white dark:bg-dark-800 w-full max-w-lg rounded-[3rem] shadow-2xl p-8 md:p-10 border border-slate-100 dark:border-slate-700 transform transition-all text-right">

                <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700 pb-4">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white" x-text="addModal ? 'إضافة عضو جديد' : 'تعديل البيانات'"></h3>
                    <button @click="addModal = false; editModal = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-50 dark:bg-dark-900 text-slate-400 hover:text-rose-500 transition-all">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <form :action="addModal ? '{{ route('admin.team.store') }}' : '{{ url('admin/team') }}/' + itemToEdit.id"
                    method="POST" enctype="multipart/form-data" @submit="loading = true">
                    @csrf
                    <template x-if="editModal"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="space-y-8">
                        <div class="flex flex-col items-center">
                            <label class="relative group cursor-pointer">
                                <div class="w-36 h-36 rounded-[2.5rem] border-4 border-slate-50 dark:border-dark-700 overflow-hidden shadow-2xl bg-slate-100 dark:bg-dark-900 flex items-center justify-center transition-all group-hover:border-brand-500"
                                    :class="{ 'border-rose-500 ring-4 ring-rose-500/10': {{ $errors->has('image') ? 'true' : 'false' }} }">
                                    <template x-if="imageUrl">
                                        <img :src="imageUrl" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!imageUrl">
                                        <div class="text-center">
                                            <i class="fa-solid fa-camera text-slate-300 text-4xl mb-2"></i>
                                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">ارفع صورة</span>
                                        </div>
                                    </template>
                                    <div class="absolute inset-0 bg-brand-600/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                        <i class="fa-solid fa-cloud-arrow-up text-white text-3xl"></i>
                                    </div>
                                </div>
                                <input type="file" name="image" class="hidden" @change="fileChosen">
                            </label>
                            @error('image') <span class="text-[11px] text-rose-500 font-bold mt-3 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-black text-slate-400 mb-2 mr-1 uppercase tracking-widest">الاسم الكامل</label>
                                <input type="text" name="name" x-model="itemToEdit.name"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:border-brand-500 outline-none transition-all font-bold text-sm">
                                @error('name') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-400 mb-2 mr-1 uppercase tracking-widest">المسمى الوظيفي</label>
                                <input type="text" name="job_title" x-model="itemToEdit.job_title"
                                    class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:border-brand-500 outline-none transition-all font-bold text-sm">
                                @error('job_title') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex gap-4 pt-4 border-t border-slate-100 dark:border-slate-700 pb-2">
                            <button type="submit" :disabled="loading"
                                class="flex-1 bg-brand-600 text-white py-4 rounded-2xl font-black flex items-center justify-center gap-3 disabled:opacity-70 shadow-lg shadow-brand-500/30 hover:bg-brand-700 hover:-translate-y-1 transition-all">
                                <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                                <span x-text="loading ? 'جاري الحفظ...' : (addModal ? 'تأكيد الإضافة' : 'حفظ التعديلات')"></span>
                            </button>
                            <button type="button" @click="addModal = false; editModal = false" :disabled="loading"
                                class="px-8 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[2.5rem] p-8 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <h4 class="text-xl font-black mb-2 text-slate-800 dark:text-white">حذف العضو</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">هل أنت متأكد؟ هذا الإجراء سيقوم بإزالة هذا العضو من الفريق بشكل نهائي.</p>
                <form :action="'{{ url('admin/team') }}/' + itemToDelete" method="POST" @submit="loading = true">
                    @csrf @method('DELETE')
                    <div class="flex gap-3">
                        <button type="submit" :disabled="loading" class="flex-1 bg-rose-600 text-white py-4 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-rose-700 transition-all">
                            <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                            <span>نعم، حذف</span>
                        </button>
                        <button type="button" @click="deleteModal = false" :disabled="loading" class="flex-1 bg-slate-100 dark:bg-dark-700 text-slate-500 py-4 rounded-2xl font-bold hover:bg-slate-200 transition-colors">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection
