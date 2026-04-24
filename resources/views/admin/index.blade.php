@extends('admin.layouts.master')

@section('title', 'الرئيسية')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">مرحباً بك مجدداً 👋</h2>
        <p class="text-gray-500">هذا ملخص سريع لما يحدث في المنصة اليوم.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 mb-4">
                <i class="fa-solid fa-coins text-xl"></i>
            </div>
            <p class="text-gray-400 text-sm font-medium">إجمالي التبرعات</p>
            <h4 class="text-2xl font-bold text-gray-800">45,200 ج.م</h4>
        </div>

    </div>
@endsection
