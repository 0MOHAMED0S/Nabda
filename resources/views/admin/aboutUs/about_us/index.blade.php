@extends('admin.layouts.master')
@section('title', 'إعدادات من نحن')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="aboutUsForm()">

    <div class="mb-8 md:mb-10">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
            التعريف بالمنصة (من نحن)
        </h2>
        <div class="h-1.5 w-16 md:w-20 bg-brand-600 rounded-full mt-3"></div>
        <p class="text-slate-500 mt-3 text-sm">قم بتحديث النصوص ورابط الفيديو الخاص بقسم التعريف بالمنصة.</p>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-slate-700/50 mb-10 w-full">
        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 dark:border-slate-700/50 pb-6">
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-white">تعديل البيانات</h3>
        </div>

        <form action="{{ route('admin.about_us.update') }}" method="POST" @submit="loading = true">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-right mb-8">

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">العنوان الرئيسي</label>
                    <input type="text" name="title" x-model="form.title"
                        class="w-full px-5 py-4 rounded-xl border @error('title') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all font-bold text-base md:text-lg text-brand-600">
                    @error('title') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">رابط فيديو (YouTube)</label>
                    <div class="relative">
                        <input type="url" name="video_url" x-model="form.video_url" dir="ltr" placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full px-5 py-4 pl-12 rounded-xl border @error('video_url') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm text-left">
                        <i class="fa-brands fa-youtube absolute left-4 top-1/2 -translate-y-1/2 text-rose-500 text-xl"></i>
                    </div>
                    @error('video_url') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block text-right">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الفقرة الأولى (أساسية)</label>
                    <textarea name="description1" rows="5" x-model="form.description1"
                        class="w-full px-5 py-4 rounded-xl border @error('description1') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-medium leading-relaxed"></textarea>
                    @error('description1') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 mr-1">الفقرة الثانية (اختيارية)</label>
                    <textarea name="description2" rows="5" x-model="form.description2"
                        class="w-full px-5 py-4 rounded-xl border @error('description2') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-200 dark:border-slate-700 focus:ring-4 focus:ring-brand-500/10 @enderror bg-slate-50 dark:bg-dark-900 outline-none transition-all text-sm font-medium leading-relaxed"></textarea>
                    @error('description2') <span class="text-[11px] text-rose-500 font-bold mt-1 mr-1 block">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-700/50">
                <button type="submit" :disabled="loading" class="w-full md:w-auto bg-brand-600 text-white px-10 py-4 rounded-xl font-bold flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-0.5 transition-all text-base">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <template x-if="!loading"><i class="fa-solid fa-floppy-disk"></i></template>
                    <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التحديثات'"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="w-full">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs md:text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fa-regular fa-eye"></i> معاينة حية للتصميم
            </h3>
        </div>

        <div class="bg-slate-50 dark:bg-dark-900 rounded-[3rem] p-8 md:p-12 border border-slate-200/50 dark:border-slate-700/50 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-64 h-64 bg-brand-500 rounded-[4rem] rotate-12 -translate-x-10 -translate-y-10 opacity-10 dark:opacity-5 pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row gap-10 relative z-10 items-center">

                <div class="text-right order-2 lg:order-1 w-full lg:w-1/2">
                    <h2 class="text-3xl md:text-4xl font-black text-brand-600 mb-6 leading-tight" x-text="form.title || 'من نحن ؟'"></h2>
                    <p class="text-slate-700 dark:text-slate-300 font-medium text-sm md:text-base leading-loose mb-4" x-text="form.description1 || 'اكتب الفقرة الأولى...'"></p>
                    <p class="text-slate-600 dark:text-slate-400 font-medium text-sm md:text-base leading-loose" x-show="form.description2" x-text="form.description2"></p>
                </div>

                <div class="order-1 lg:order-2 w-full lg:w-1/2">
                    <div class="w-full aspect-video bg-dark-900 rounded-2xl overflow-hidden shadow-2xl relative border-4 border-white dark:border-dark-800 transition-all">
                        <template x-if="embedUrl">
                            <iframe :src="embedUrl" class="w-full h-full absolute inset-0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </template>
                        <template x-if="!embedUrl">
                            <div class="absolute inset-0 flex flex-col items-center justify-center bg-slate-800 text-slate-400 p-6 text-center">
                                <i class="fa-brands fa-youtube text-5xl mb-3 opacity-50"></i>
                                <span class="text-sm font-bold">رابط الفيديو غير صالح أو غير موجود</span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
        <p class="text-center text-xs text-slate-400 mt-4 font-medium"><i class="fa-solid fa-mobile-screen mr-1"></i> المعاينة تحاكي شكل واجهة (من نحن) على الموقع الفعلي.</p>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('aboutUsForm', () => ({
            loading: false,
            form: {
                title: @json(old('title', $about->title ?? '')),
                description1: @json(old('description1', $about->description1 ?? '')),
                description2: @json(old('description2', $about->description2 ?? '')),
                video_url: @json(old('video_url', $about->video_url ?? ''))
            },

            // Super robust YouTube Regex to catch standard, shortened, and shorts links
            get embedUrl() {
                if (!this.form.video_url) return null;
                const url = this.form.video_url.trim();
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=|\/shorts\/)([^#&?]*).*/;
                const match = url.match(regExp);

                if (match && match[2].length === 11) {
                    // استخدام youtube-nocookie لتقليل مشاكل الحظر والحقوق في لوحة التحكم
                    return 'https://www.youtube-nocookie.com/embed/' + match[2] + '?rel=0';
                }
                return null;
            }
        }))
    })
</script>
@endsection
