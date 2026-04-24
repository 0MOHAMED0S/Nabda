<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الإدارة | نبضة خير</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #d7e1ec 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        /* أنيميشن شريط المؤقت */
        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }
        .timer-bar {
            animation: shrink 5s linear forwards;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="fixed top-5 left-4 right-4 sm:right-5 sm:left-auto z-[100] flex flex-col gap-4 w-auto sm:w-[400px]">

        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            class="relative overflow-hidden bg-white shadow-2xl shadow-emerald-500/20 border border-emerald-100 rounded-2xl p-5 flex items-start gap-4">

            <div class="bg-emerald-100 text-emerald-500 w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-check text-xl"></i>
            </div>
            <div class="flex-1 text-right pt-1">
                <h4 class="text-slate-800 font-black text-sm">عملية ناجحة</h4>
                <p class="text-slate-500 text-xs font-bold mt-1 leading-relaxed">{{ session('success') }}</p>
            </div>
            <button @click="show = false" class="text-slate-400 hover:text-rose-500 transition-colors shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-emerald-50">
                <div class="h-full bg-emerald-500 timer-bar"></div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            class="relative overflow-hidden bg-white shadow-2xl shadow-rose-500/20 border border-rose-100 rounded-2xl p-5 flex items-start gap-4">

            <div class="bg-rose-100 text-rose-500 w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
            <div class="flex-1 text-right pt-1">
                <h4 class="text-slate-800 font-black text-sm">عذراً، حدث خطأ</h4>
                <p class="text-slate-500 text-xs font-bold mt-1 leading-relaxed">{{ session('error') }}</p>
            </div>
            <button @click="show = false" class="text-slate-400 hover:text-rose-500 transition-colors shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-rose-50">
                <div class="h-full bg-rose-500 timer-bar"></div>
            </div>
        </div>
        @endif

        @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8"
            class="relative overflow-hidden bg-white shadow-2xl shadow-amber-500/20 border border-amber-100 rounded-2xl p-5 flex items-start gap-4">

            <div class="bg-amber-100 text-amber-500 w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-exclamation text-xl"></i>
            </div>
            <div class="flex-1 text-right pt-1">
                <h4 class="text-slate-800 font-black text-sm">تنبيه</h4>
                <p class="text-slate-500 text-xs font-bold mt-1 leading-relaxed">{{ $errors->first() }}</p>
            </div>
            <button @click="show = false" class="text-slate-400 hover:text-rose-500 transition-colors shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-amber-50">
                <div class="h-full bg-amber-500 timer-bar"></div>
            </div>
        </div>
        @endif
    </div>


    <div class="fixed top-[-10%] left-[-10%] w-96 h-96 bg-brand-300/30 rounded-full mix-blend-multiply filter blur-[80px] animate-pulse" style="background-color: #a78bfa;"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-96 h-96 bg-blue-300/30 rounded-full mix-blend-multiply filter blur-[80px] animate-pulse" style="animation-delay: 2s; background-color: #60a5fa;"></div>

    <div class="w-full max-w-[420px] glass-card rounded-[2.5rem] overflow-hidden relative z-10">
        <div class="p-8 sm:p-10">

            <div class="text-center mb-8">
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('logo/logo.webp') }}" alt="نبضة خير" class="h-20 w-auto object-contain drop-shadow-sm hover:scale-105 transition-transform duration-500">
                </div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">لوحة تحكم الإدارة</h2>
                <p class="text-slate-500 mt-2 text-sm font-medium">أدخل بيانات الاعتماد للوصول إلى النظام</p>
            </div>

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="space-y-1.5 text-right">
                    <label class="block text-slate-700 text-sm font-bold mr-1">البريد الإلكتروني</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}"  placeholder="admin@nabdatkhair.com"
                            class="w-full pl-4 pr-12 py-3.5 rounded-2xl bg-white/80 border-2 border-slate-100 focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all duration-300 placeholder:text-slate-400 text-sm font-bold text-slate-700">
                        <i class="fa-solid fa-envelope absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>

                <div class="space-y-1.5 text-right">
                    <label class="block text-slate-700 text-sm font-bold mr-1">كلمة المرور</label>
                    <div class="relative" x-data="{ showPass: false }">
                        <input :type="showPass ? 'text' : 'password'" name="password"  placeholder="••••••••"
                            class="w-full pl-12 pr-12 py-3.5 rounded-2xl bg-white/80 border-2 border-slate-100 focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 outline-none transition-all duration-300 placeholder:text-slate-400 text-sm font-bold text-slate-700">
                        <i class="fa-solid fa-lock absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                        <button type="button" @click="showPass = !showPass" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600 transition-colors">
                            <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full bg-[#7c3aed] hover:bg-[#6d28d9] text-white font-black py-4 rounded-2xl transition duration-300 shadow-xl shadow-purple-500/30 active:scale-[0.98] mt-4 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <template x-if="loading"><i class="fa-solid fa-circle-notch animate-spin"></i></template>
                    <span x-text="loading ? 'جاري الدخول...' : 'تسجيل الدخول'"></span>
                </button>
            </form>
        </div>

        <div class="bg-slate-50/80 py-5 text-center border-t border-slate-100/50 backdrop-blur-md">
            <p class="text-xs text-slate-400 tracking-widest font-bold">
                نبضة خير &copy; {{ date('Y') }} • منصة العطاء الرقمية
            </p>
        </div>
    </div>
</body>
</html>
