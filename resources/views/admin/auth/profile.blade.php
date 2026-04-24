@extends('admin.layouts.master')
@section('title', 'الملف الشخصي')

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    <div class="mb-10">
        <h2 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white flex items-center gap-3">
            إعدادات الحساب
        </h2>
        <div class="h-1.5 w-20 bg-brand-600 rounded-full mt-3"></div>
        <p class="text-slate-500 mt-2 font-medium">إدارة بياناتك الشخصية وتأمين حسابك</p>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-700/50 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-brand-400 via-brand-600 to-brand-400"></div>

        <div class="p-8 md:p-10">
            <div class="flex items-center gap-4 mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-6">
                <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 text-brand-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white">البيانات الشخصية</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">تحديث الاسم والبريد الإلكتروني</p>
                </div>
            </div>

            <form action="{{ route('admin.profile.update') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="space-y-2 text-right">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">الاسم الكامل <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $admin->name) }}"
                            class="w-full px-6 py-4 rounded-2xl border-2 @error('name') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @enderror bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all h-[60px]">
                        @error('name')
                            <span class="text-rose-500 text-[11px] font-bold mr-1 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-2 text-right">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" dir="ltr"
                            class="w-full px-6 py-4 rounded-2xl border-2 @error('email') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-brand-500 @enderror bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all h-[60px] text-right focus:text-left">
                        @error('email')
                            <span class="text-rose-500 text-[11px] font-bold mr-1 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-slate-50 dark:border-slate-700/50">
                    <button type="submit" :disabled="loading" class="bg-brand-600 text-white px-10 py-4 rounded-2xl font-black shadow-xl shadow-brand-500/20 hover:bg-brand-700 hover:-translate-y-1 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="loading ? 'جاري الحفظ...' : 'حفظ التغييرات'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-700/50 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-400 via-rose-600 to-rose-400"></div>

        <div class="p-8 md:p-10">
            <div class="flex items-center gap-4 mb-10 border-b border-slate-50 dark:border-slate-700/50 pb-6">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-shield-lock"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white">تغيير كلمة المرور</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">تأمين حسابك بكلمة مرور قوية</p>
                </div>
            </div>

            <form action="{{ route('admin.profile.password') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 gap-8 mb-10">
                    <div class="space-y-2 text-right max-w-xl">
                        <label class="block text-xs font-black text-slate-400 mr-1 uppercase">كلمة المرور الحالية <span class="text-rose-500">*</span></label>
                        <input type="password" name="current_password"
                            class="w-full px-6 py-4 rounded-2xl border-2 @error('current_password') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-rose-500 @enderror bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all h-[60px]" dir="ltr">
                        @error('current_password')
                            <span class="text-rose-500 text-[11px] font-bold mr-1 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2 text-right">
                            <label class="block text-xs font-black text-slate-400 mr-1 uppercase">كلمة المرور الجديدة <span class="text-rose-500">*</span></label>
                            <input type="password" name="password"
                                class="w-full px-6 py-4 rounded-2xl border-2 @error('password') border-rose-500 ring-4 ring-rose-500/10 @else border-slate-100 dark:border-slate-700 focus:border-rose-500 @enderror bg-slate-50 dark:bg-dark-900 outline-none font-bold transition-all h-[60px]" dir="ltr">
                            @error('password')
                                <span class="text-rose-500 text-[11px] font-bold mr-1 mt-2 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-2 text-right">
                            <label class="block text-xs font-black text-slate-400 mr-1 uppercase">تأكيد كلمة المرور الجديدة <span class="text-rose-500">*</span></label>
                            <input type="password" name="password_confirmation"
                                class="w-full px-6 py-4 rounded-2xl border-2 border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:border-rose-500 outline-none font-bold transition-all h-[60px]" dir="ltr">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-slate-50 dark:border-slate-700/50">
                    <button type="submit" :disabled="loading" class="bg-rose-600 text-white px-10 py-4 rounded-2xl font-black shadow-xl shadow-rose-500/20 hover:bg-rose-700 hover:-translate-y-1 transition-all flex items-center justify-center gap-3 disabled:opacity-70 disabled:cursor-not-allowed">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="loading ? 'جاري التحديث...' : 'تحديث كلمة المرور'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
