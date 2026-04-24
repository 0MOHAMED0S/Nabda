@extends('admin.layouts.master')
@section('title', 'تعديل قسم الهيرو')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    form: {
        title: @js($hero->title),
        description: @js($hero->description)
    },
    // تغيير المتغير إلى videoPreview وقراءة حقل video
    videoPreview: '{{ $hero->video ? asset('storage/' . $hero->video) : '' }}',

    fileChosen(event) {
        const file = event.target.files[0];
        if (!file) return;

        // استخدام URL.createObjectURL أفضل وأسرع بكثير للفيديوهات من الـ FileReader
        this.videoPreview = URL.createObjectURL(file);
    }
}">
    <div class="mb-8 md:mb-10">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
            تخصيص الواجهة الرئيسية (الهيرو)
        </h2>
        <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
        <p class="text-slate-500 mt-2 text-sm">قم بتحديث النصوص وفيديو الخلفية الرئيسي للمنصة من هنا.</p>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10">
        <form action="{{ route('admin.hero.update') }}" method="POST" enctype="multipart/form-data" @submit="loading = true">
            @csrf

            <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
                <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-video"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل البيانات</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 text-right">

                <div class="md:col-span-4 flex flex-col items-center justify-start">
                    <label class="block text-xs font-bold text-slate-400 mb-3 uppercase tracking-wider w-full text-right">فيديو الخلفية الرئيسية</label>
                    <label class="relative group cursor-pointer block w-full aspect-video md:aspect-[4/3]">
                        <div class="w-full h-full rounded-2xl border-2 border-dashed @error('video') border-rose-500 bg-rose-50 dark:bg-rose-900/10 @else border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-dark-900 hover:border-brand-500 @enderror overflow-hidden flex items-center justify-center transition-all">

                            <template x-if="videoPreview">
                                <video :src="videoPreview" autoplay loop muted playsinline class="w-full h-full object-cover opacity-60 group-hover:opacity-30 transition-opacity"></video>
                            </template>

                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500" :class="videoPreview ? 'opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-md' : ''">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-dark-800 shadow-sm flex items-center justify-center mb-2">
                                    <i class="fa-solid fa-cloud-arrow-up text-brand-500 text-xl"></i>
                                </div>
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-800 dark:text-white bg-white/80 dark:bg-dark-900/80 px-2 py-1 rounded backdrop-blur-sm">تغيير الفيديو</span>
                            </div>
                        </div>

                        <input type="file" name="video" class="hidden" accept="video/mp4, video/quicktime, video/ogg" @change="fileChosen">
                    </label>

                    @error('video') <span class="text-[11px] text-rose-500 font-bold mt-2 text-center block">{{ $message }}</span> @enderror
                    <p class="text-[11px] text-slate-400 mt-3 font-medium text-center"><i class="fa-solid fa-circle-info ml-1"></i> الحجم الأقصى: 20 ميجابايت.</p>
                </div>

                <div class="md:col-span-8 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">العنوان الرئيسي</label>
                        <input type="text" name="title" x-model="form.title"
                            class="w-full px-5 py-4 rounded-xl border @error('title') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-base md:text-lg text-slate-800 dark:text-white">
                        @error('title') <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">وصف القسم (النص الفرعي)</label>
                        <textarea name="description" rows="4" x-model="form.description"
                            class="w-full px-5 py-4 rounded-xl border @error('description') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-medium leading-relaxed text-slate-700 dark:text-slate-300"></textarea>
                        @error('description') <span class="text-[11px] text-rose-500 font-bold mt-2 mr-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700/50 flex justify-end">
                <button type="submit" :disabled="loading" class="w-full md:w-auto bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <template x-if="!loading"><i class="fa-solid fa-floppy-disk"></i></template>
                    <span x-text="loading ? 'جاري الحفظ والرفع...' : 'حفظ ونشر التغييرات'"></span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
