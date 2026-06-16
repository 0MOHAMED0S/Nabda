@extends('admin.layouts.master')
@section('title', 'إشعارات النظام')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    loading: false,
    deleteModal: false,
    itemToDelete: ''
}">

    <div class="mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                    إشعارات النظام
                    @if($unreadCount > 0)
                        <span class="text-white text-sm font-bold bg-rose-500 px-3 py-1 rounded-lg animate-pulse">{{ $unreadCount }} جديد</span>
                    @endif
                </h2>
                <div class="h-1.5 bg-brand-600 w-16 mt-3 rounded-full"></div>
            </div>

            @if($unreadCount > 0)
                <form action="{{ route('admin.notifications.markAllAsRead') }}" method="POST" @submit="loading = true">
                    @csrf
                    <button type="submit" :disabled="loading" class="bg-white dark:bg-dark-800 text-slate-600 dark:text-slate-300 border-2 border-slate-200 dark:border-slate-700 px-6 py-3 rounded-2xl font-black shadow-sm hover:border-brand-500 hover:text-brand-600 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-check-double"></i> تحديد الكل كمقروء
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="space-y-4 mb-10">
        @forelse($notifications as $notification)
            @php
                $isRead = !is_null($notification->read_at);
                $type = $notification->data['type'] ?? 'info';

                // تحديد الألوان والأيقونات بناءً على نوع الإشعار
                $icon = 'fa-bell';
                $colorClass = 'text-brand-500 bg-brand-50 dark:bg-brand-900/30';

                if ($type === 'success') {
                    $icon = 'fa-circle-check';
                    $colorClass = 'text-emerald-500 bg-emerald-50 dark:bg-emerald-900/30';
                } elseif ($type === 'warning') {
                    $icon = 'fa-triangle-exclamation';
                    $colorClass = 'text-amber-500 bg-amber-50 dark:bg-amber-900/30';
                } elseif ($type === 'error' || $type === 'danger') {
                    $icon = 'fa-circle-xmark';
                    $colorClass = 'text-rose-500 bg-rose-50 dark:bg-rose-900/30';
                }
            @endphp

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border transition-all flex flex-col md:flex-row gap-6 items-start md:items-center relative overflow-hidden {{ $isRead ? 'border-slate-100 dark:border-slate-700/50 opacity-70' : 'border-brand-300 dark:border-brand-700 shadow-md shadow-brand-500/5' }}">

                @if(!$isRead)
                    <div class="absolute right-0 top-0 bottom-0 w-1.5 bg-brand-500"></div>
                @endif

                <div class="w-16 h-16 shrink-0 rounded-2xl flex items-center justify-center text-2xl {{ $colorClass }}">
                    <i class="fa-solid {{ $icon }}"></i>
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h4 class="text-lg font-black text-slate-800 dark:text-white">{{ $notification->data['title'] ?? 'إشعار جديد' }}</h4>
                        <span class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-dark-900 px-2 py-1 rounded-md flex items-center gap-1" dir="ltr">
                            {{ $notification->created_at->locale('ar')->diffForHumans() }} <i class="fa-regular fa-clock"></i>
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                        {{ $notification->data['body'] ?? '' }}
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end md:self-auto mt-4 md:mt-0">
                    @if(!$isRead)
                        <form action="{{ route('admin.notifications.markAsRead', $notification->id) }}" method="POST" @submit="loading = true">
                            @csrf @method('PUT')
                            <button type="submit" :disabled="loading" title="تحديد كمقروء"
                                class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-emerald-600 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                                <i class="fa-solid fa-check text-xs"></i>
                            </button>
                        </form>
                    @endif

                    <button @click="itemToDelete = '{{ $notification->id }}'; deleteModal = true" :disabled="loading" title="حذف الإشعار"
                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-dark-900 text-rose-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm disabled:opacity-50">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 p-16 text-center shadow-sm">
                <div class="w-24 h-24 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                    <i class="fa-regular fa-bell-slash"></i>
                </div>
                <h4 class="text-xl font-black text-slate-500 mb-2">لا يوجد إشعارات حالياً</h4>
                <p class="text-sm text-slate-400">جميع الأمور على ما يرام، ليس لديك أي إشعارات جديدة.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mb-10">
            {{ $notifications->links() }}
        </div>
    @endif

    <template x-teleport="body">
        <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
            <div @click.away="!loading && (deleteModal = false)" x-transition class="bg-white dark:bg-dark-800 w-full max-w-sm rounded-[3rem] p-10 text-center shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                <div class="w-24 h-24 bg-rose-50 dark:bg-rose-900/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-8 text-4xl shadow-inner">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h4 class="text-2xl font-black mb-2 text-slate-800 dark:text-white">حذف الإشعار</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 leading-relaxed px-4">هل أنت متأكد من حذف هذا الإشعار؟ لا يمكن التراجع عن هذه الخطوة.</p>

                <form :action="'{{ url('admin/notifications') }}/' + itemToDelete" method="POST" @submit="loading = true">
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
    </template>
</div>
@endsection
