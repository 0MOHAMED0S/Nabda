@extends('admin.layouts.master')
@section('title', 'صندوق الوارد (رسائل الزوار)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    showModal: false,
    selectedMessage: null,
    loading: false,
    replying: false,

    openModal(messageData) {
        this.selectedMessage = JSON.parse(JSON.stringify(messageData));
        this.showModal = true;
        this.replying = false;

        // إزالة علامة 'غير مقروءة' بصرياً من الجدول فور فتح الرسالة
        const row = document.getElementById('msg-row-' + this.selectedMessage.id);
        if(row) {
            row.classList.remove('bg-brand-50/30', 'dark:bg-brand-900/5');
            const indicator = document.getElementById('msg-indicator-' + this.selectedMessage.id);
            if(indicator) {
                indicator.className = 'w-3 h-3 rounded-full bg-slate-200 dark:bg-slate-700 mx-auto';
            }
        }
    },

    getAvatarText(name) {
        return name ? name.substring(0, 1).toUpperCase() : '?';
    },

    formatDateTime(dateString) {
        if(!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
}">

    <div class="mb-10">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 mb-2">
            صندوق الوارد ودعم الزوار
            <span class="text-white text-xs font-bold bg-brand-500 px-3 py-1 rounded-xl shadow-sm">{{ $stats['total'] ?? 0 }} تذكرة</span>
        </h2>
        <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">استقبال استفسارات الزوار، مراجعة الاقتراحات، وتقديم الدعم الفني المباشر عبر البريد الإلكتروني.</p>
        <div class="h-1.5 bg-brand-600 w-16 mt-4 rounded-full mb-8"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 p-6 rounded-[2rem] shadow-lg shadow-blue-500/20 text-white flex items-center gap-5 relative overflow-hidden transition-transform hover:-translate-y-1">
                <i class="fa-solid fa-inbox absolute -left-4 -bottom-4 text-7xl opacity-10"></i>
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl shrink-0"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div>
                    <span class="block text-blue-100 text-[10px] font-black uppercase tracking-widest mb-1">إجمالي الرسائل المستلمة</span>
                    <h4 class="text-3xl font-black">{{ number_format($stats['total'] ?? 0) }} <span class="text-lg font-bold text-blue-200">رسالة</span></h4>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-rose-100 dark:border-rose-900/30 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1 group">
                <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/30 text-rose-500 rounded-2xl flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform"><i class="fa-solid fa-headset animate-pulse"></i></div>
                <div>
                    <span class="block text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">تذاكر مفتوحة (بانتظار الرد)</span>
                    <h4 class="text-3xl font-black text-rose-600 dark:text-rose-500">{{ number_format($stats['unread'] ?? 0) }} <span class="text-lg font-bold text-slate-400">تذكرة</span></h4>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 rounded-2xl flex items-center justify-center text-3xl shrink-0"><i class="fa-solid fa-check-double"></i></div>
                <div>
                    <span class="block text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">مكتملة / تم الرد عليها</span>
                    <h4 class="text-3xl font-black text-slate-800 dark:text-white">{{ number_format($stats['read'] ?? 0) }} <span class="text-lg font-bold text-slate-400">مكتملة</span></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50 shadow-sm mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-brand-50 dark:bg-brand-900/10 rounded-bl-full -z-10"></div>

        <form action="{{ route('admin.messages.index') }}" method="GET" @submit="loading = true">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="relative lg:col-span-2">
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">البحث في التذاكر</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث باسم المرسل، البريد الإلكتروني، أو الموضوع..." class="w-full px-5 py-3 pr-10 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-10 text-slate-400"></i>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">حالة التذكرة</label>
                    <select name="status" class="w-full px-5 py-3 rounded-2xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all cursor-pointer shadow-sm">
                        <option value="all">الكل</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>تذاكر مفتوحة (تحتاج رد) 🔴</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>تذاكر مغلقة (تمت) 🟢</option>
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-black py-3 rounded-2xl transition-all shadow-lg shadow-brand-500/20 flex items-center justify-center gap-2">
                        <span x-show="!loading"><i class="fa-solid fa-filter"></i> تصفية</span>
                        <span x-show="loading" x-cloak><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                    @if(request()->hasAny(['search', 'status']) && request()->except('page'))
                        <a href="{{ route('admin.messages.index') }}" class="px-4 py-3 bg-slate-100 dark:bg-dark-900 text-slate-500 hover:text-rose-500 rounded-2xl transition-colors text-sm font-bold" title="إعادة ضبط">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700/50 shadow-sm overflow-hidden w-full mb-10">
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-right border-collapse text-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-dark-900/50 text-slate-400 text-[11px] font-black uppercase tracking-widest">
                        <th class="px-6 py-6 w-12 text-center">حالة</th>
                        <th class="px-6 py-6">المرسل / التذكرة</th>
                        <th class="px-6 py-6">موضوع الرسالة والمحتوى</th>
                        <th class="px-6 py-6 text-center">التاريخ</th>
                        <th class="px-6 py-6 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse($messages as $msg)
                        <tr id="msg-row-{{ $msg->id }}" class="transition-all duration-300 hover:bg-slate-50 dark:hover:bg-dark-900/40 {{ !$msg->is_read ? 'bg-brand-50/30 dark:bg-brand-900/5' : '' }}">

                            <td class="px-6 py-5 text-center">
                                <div id="msg-indicator-{{ $msg->id }}" class="{{ !$msg->is_read ? 'w-3 h-3 rounded-full bg-rose-500 mx-auto animate-pulse shadow-sm shadow-rose-500/50' : 'w-3 h-3 rounded-full bg-slate-200 dark:bg-slate-700 mx-auto' }}" title="{{ !$msg->is_read ? 'تحتاج رد' : 'مكتملة' }}"></div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full {{ $msg->user_id ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'bg-slate-100 text-slate-500 dark:bg-dark-900' }} flex items-center justify-center font-black shadow-inner shrink-0 text-lg">
                                        {{ mb_substr($msg->name, 0, 1, "UTF-8") }}
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black {{ !$msg->is_read ? 'text-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                            {{ \Illuminate\Support\Str::limit($msg->name, 20) }}
                                            @if($msg->user_id)
                                                <i class="fa-solid fa-circle-check text-blue-500 text-[10px] ml-1" title="عضو مسجل بالمنصة"></i>
                                            @endif
                                        </h5>
                                        <span class="text-[10px] font-bold text-slate-400 block mt-0.5" dir="ltr"><a href="mailto:{{ $msg->email }}" class="hover:text-brand-500">{{ $msg->email }}</a></span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 max-w-[300px]">
                                <span class="block text-sm font-black {{ !$msg->is_read ? 'text-slate-800 dark:text-white' : 'text-slate-700 dark:text-slate-300' }} truncate mb-1">
                                    {{ $msg->subject }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-500 truncate block">
                                    {{ \Illuminate\Support\Str::limit($msg->message, 70) }}
                                </span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <span class="block text-xs font-black text-slate-600 dark:text-slate-300" dir="ltr">{{ $msg->created_at->format('Y-m-d') }}</span>
                                <span class="text-[10px] font-bold text-slate-400">{{ $msg->created_at->diffForHumans() }}</span>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openModal(@js($msg))" class="w-9 h-9 rounded-xl {{ !$msg->is_read ? 'bg-brand-50 text-brand-600 hover:bg-brand-600 hover:text-white' : 'bg-slate-50 text-slate-500 hover:bg-blue-600 hover:text-white' }} dark:bg-dark-900 border border-slate-100 dark:border-slate-700 flex items-center justify-center transition-all shadow-sm" title="فتح وقراءة التذكرة">
                                        <i class="fa-solid fa-envelope-open-text"></i>
                                    </button>

                                    <form action="{{ route('admin.messages.destroy', $msg->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرسالة نهائياً؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-dark-900 text-slate-400 hover:text-white hover:bg-rose-500 border border-slate-100 dark:border-slate-700 flex items-center justify-center transition-all shadow-sm" title="حذف التذكرة">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 dark:bg-dark-900 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner border border-slate-100 dark:border-slate-700">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-500">صندوق الوارد فارغ</h4>
                                <p class="text-sm text-slate-400 mt-1">لا توجد رسائل تطابق بحثك، أو لم تصلك أي تذاكر دعم بعد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messages->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-700/50 bg-slate-50/20 dark:bg-dark-900/10">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/70 backdrop-blur-sm">
            <div @click.away="showModal = false"
                 x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="bg-slate-50 dark:bg-dark-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[95vh] border border-slate-200 dark:border-slate-700">

                <div class="px-8 py-6 bg-white dark:bg-dark-800 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 flex items-center justify-center text-2xl font-black shadow-sm border border-brand-100 dark:border-brand-800" x-text="getAvatarText(selectedMessage?.name)"></div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-xl font-black text-slate-800 dark:text-white" x-text="selectedMessage?.name"></h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded text-white shadow-sm" :class="selectedMessage?.user_id ? 'bg-blue-500' : 'bg-slate-400'" x-text="selectedMessage?.user_id ? 'عضو مسجل' : 'زائر'"></span>
                            </div>
                            <p class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-slate-400"></i> <a :href="'mailto:' + selectedMessage?.email" class="hover:text-brand-500 transition-colors" dir="ltr" x-text="selectedMessage?.email"></a>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-show="selectedMessage?.replied_at" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-xl text-xs font-black shadow-sm flex items-center gap-2 border border-emerald-100 dark:border-emerald-800">
                            <i class="fa-solid fa-check-double"></i> تم إرسال الرد
                        </span>
                        <button @click="showModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-500 hover:text-white hover:bg-rose-500 transition-all border border-slate-200 dark:border-slate-700 shadow-sm" title="إغلاق"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>

                <div class="p-8 overflow-y-auto flex-1 custom-scrollbar space-y-8">

                    <div>
                        <div class="flex justify-between items-end mb-3 px-2">
                            <h4 class="text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2"><i class="fa-solid fa-message text-brand-500"></i> رسالة الزائر</h4>
                            <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1" dir="ltr">
                                <i class="fa-regular fa-clock"></i> <span x-text="formatDateTime(selectedMessage?.created_at)"></span>
                            </span>
                        </div>
                        <div class="bg-white dark:bg-dark-800 p-6 rounded-3xl rounded-tr-sm border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <h5 class="text-lg font-black text-slate-800 dark:text-white mb-3 pb-3 border-b border-slate-50 dark:border-slate-700/50" x-text="'الموضوع: ' + selectedMessage?.subject"></h5>
                            <p class="text-sm font-bold text-slate-600 dark:text-slate-300 leading-loose whitespace-pre-line" x-text="selectedMessage?.message"></p>
                        </div>
                    </div>

                    <div>
                        <template x-if="selectedMessage?.replied_at">
                            <div class="pl-0 md:pl-12"> <div class="flex justify-between items-end mb-3 px-2">
                                    <h4 class="text-sm font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-widest flex items-center gap-2"><i class="fa-solid fa-headset"></i> رد الإدارة</h4>
                                    <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1" dir="ltr">
                                        <i class="fa-regular fa-clock"></i> <span x-text="formatDateTime(selectedMessage?.replied_at)"></span>
                                    </span>
                                </div>
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 p-6 rounded-3xl rounded-tl-sm border border-emerald-100 dark:border-emerald-800/30 shadow-sm">
                                    <h5 class="text-sm font-black text-emerald-800 dark:text-emerald-400 mb-3 pb-3 border-b border-emerald-100 dark:border-emerald-800/30" x-text="'رد على: ' + selectedMessage?.reply_subject"></h5>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300 leading-loose whitespace-pre-line" x-text="selectedMessage?.reply_body"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="!selectedMessage?.replied_at">
                            <div class="pl-0 md:pl-12">
                                <div class="bg-white dark:bg-dark-800 p-6 rounded-3xl rounded-tl-sm border border-brand-100 dark:border-brand-900/30 shadow-sm relative">

                                    <form :action="'{{ url('admin/messages') }}/' + selectedMessage?.id + '/reply'" method="POST" @submit="replying = true">
                                        @csrf
                                        <h4 class="text-sm font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2 pb-3 border-b border-slate-50 dark:border-slate-700/50">
                                            <i class="fa-solid fa-reply text-brand-500"></i> كتابة رد (سيتم الإرسال للبريد الإلكتروني مباشرة)
                                        </h4>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-[11px] font-black text-slate-500 uppercase mb-2">موضوع البريد <span class="text-rose-500">*</span></label>
                                                <input type="text" name="reply_subject" :value="'رد من الإدارة: ' + (selectedMessage?.subject || '')" required class="w-full px-5 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm">
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-black text-slate-500 uppercase mb-2">نص الرد <span class="text-rose-500">*</span></label>
                                                <textarea name="reply_body" rows="6" required placeholder="مرحباً بك، رداً على استفسارك..." class="w-full px-5 py-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-700 text-sm font-bold outline-none focus:border-brand-500 transition-all shadow-sm resize-y leading-loose"></textarea>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex justify-end">
                                            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-black px-8 py-3 rounded-xl transition-all shadow-lg shadow-brand-500/20 flex items-center justify-center gap-2 w-full sm:w-auto">
                                                <span x-show="!replying"><i class="fa-regular fa-paper-plane"></i> اعتماد وإرسال الرد</span>
                                                <span x-show="replying" x-cloak><i class="fa-solid fa-circle-notch fa-spin"></i> جاري الإرسال...</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>

<style>
/* شريط التمرير الأنيق للنافذة المنبثقة */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #334155; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
@endsection
