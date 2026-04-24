@extends('admin.layouts.master')
@section('title', 'إدارة معلومات التواصل')

@section('content')
<div class="max-w-4xl mx-auto px-4" x-data="{ loading: false }">
    <div class="mb-10">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white">معلومات التواصل</h2>
        <div class="h-1.5 w-20 bg-brand-600 rounded-full mt-2"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-700/50 shadow-sm">
            <form action="{{ route('admin.contact_info.update') }}" method="POST" @submit="loading = true">
                @csrf @method('PUT')

                <div class="space-y-6 text-right">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase">رقم الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $contact->phone) }}"
                            class="w-full px-5 py-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all font-bold">
                        @error('phone') <span class="text-rose-500 text-[11px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $contact->email) }}"
                            class="w-full px-5 py-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all font-bold">
                        @error('email') <span class="text-rose-500 text-[11px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-2 mr-1 uppercase">ساعات العمل</label>
                        <input type="text" name="working_hours" value="{{ old('working_hours', $contact->working_hours) }}"
                            class="w-full px-5 py-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-dark-900 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all font-bold">
                        @error('working_hours') <span class="text-rose-500 text-[11px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" :disabled="loading" class="w-full bg-brand-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-brand-500/20 hover:bg-brand-700 transition-all flex items-center justify-center gap-2">
                        <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                        <span x-text="loading ? 'جاري الحفظ...' : 'تحديث المعلومات'"></span>
                    </button>
                </div>
            </form>
        </div>

        <div class="flex flex-col justify-center">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 text-center md:text-right">المعاينة الحية</h3>
            <div class="bg-slate-100 dark:bg-dark-900 rounded-[2rem] p-8 border border-slate-200 dark:border-slate-700 space-y-4">
                <h4 class="text-2xl font-black text-brand-600 text-center mb-6">معلومات التواصل</h4>

                <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm text-right flex flex-col gap-1 border border-slate-50 dark:border-slate-700/50">
                    <div class="flex items-center justify-end gap-2 text-brand-500">
                        <span class="font-bold text-sm">: رقم الهاتف</span>
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <span class="font-black text-slate-800 dark:text-white" dir="ltr">{{ $contact->phone }}</span>
                </div>

                <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm text-right flex flex-col gap-1 border border-slate-50 dark:border-slate-700/50">
                    <div class="flex items-center justify-end gap-2 text-brand-500">
                        <span class="font-bold text-sm">: البريد الإلكتروني</span>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <span class="font-bold text-slate-800 dark:text-white">{{ $contact->email }}</span>
                </div>

                <div class="bg-white dark:bg-dark-800 p-4 rounded-2xl shadow-sm text-right flex flex-col gap-1 border border-slate-50 dark:border-slate-700/50">
                    <div class="flex items-center justify-end gap-2 text-brand-500">
                        <span class="font-bold text-sm">: ساعات العمل</span>
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <span class="font-bold text-slate-800 dark:text-white">{{ $contact->working_hours }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
