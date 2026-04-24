<?php

namespace App\Http\Controllers\Admin\Hero;

use App\Http\Controllers\Controller;
use App\Models\Ticker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class TickerController extends Controller
{
    /**
     * عرض قائمة النصوص في الشريط المتحرك
     */
    public function index()
    {
        try {
            // جلب النصوص وترتيبها
            $tickers = Ticker::orderBy('order', 'asc')->orderBy('created_at', 'desc')->get();

            return view('admin.welcomeHero.tickers.index', compact('tickers'));
        } catch (Exception $e) {
            Log::error('Ticker Index Error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات الشريط المتحرك. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة نص جديد للشريط المتحرك
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع إضافة التحقق من حالة التفعيل
        $request->validate([
            'content'   => 'required|string|max:255',
            'is_active' => 'nullable|boolean'
        ], [
            'content.required'  => 'يرجى إدخال نص الخبر أو التنبيه.',
            'content.string'    => 'النص المدخل غير صالح.',
            'content.max'       => 'يجب ألا يتجاوز النص 255 حرفاً.',
            'is_active.boolean' => 'حالة التفعيل غير صالحة.',
        ]);

        try {
            // 2. تجهيز البيانات
            $data = [
                'content'   => $request->content,
                'is_active' => $request->boolean('is_active'), // قراءة حالة التفعيل من الفورم
                // 'order'  => 0, // يمكنك إضافة ترتيب مخصص هنا مستقبلاً
            ];

            // 3. الحفظ في قاعدة البيانات
            Ticker::create($data);

            return back()->with('success', 'تم إضافة النص بنجاح إلى الشريط المتحرك.');
        } catch (Exception $e) {
            Log::error('Ticker Store Error: ' . $e->getMessage());
            return back()->with('error', 'عذراً، فشل في إضافة النص. يرجى المحاولة مرة أخرى لاحقاً.');
        }
    }

    /**
     * تحديث نص موجود وحالته
     */
    public function update(Request $request, Ticker $ticker)
    {
        // 1. التحقق من البيانات مع السماح بحقل is_active
        $request->validate([
            'content'   => 'required|string|max:255',
            'is_active' => 'nullable|boolean'
        ], [
            'content.required'  => 'يرجى إدخال نص الخبر أو التنبيه للتحديث.',
            'content.string'    => 'النص المدخل غير صالح.',
            'content.max'       => 'يجب ألا يتجاوز النص 255 حرفاً.',
            'is_active.boolean' => 'قيمة التفعيل غير صالحة.',
        ]);

        try {
            // 2. جلب المحتوى النصي فقط
            $data = $request->only(['content']);

            // 3. السطر السحري: التعامل مع التفعيل والإيقاف
            $data['is_active'] = $request->boolean('is_active');

            // 4. التحديث في قاعدة البيانات
            $ticker->update($data);

            return back()->with('success', 'تم تحديث النص وحالته بنجاح.');

        } catch (Exception $e) {
            Log::error('Ticker Update Error (ID ' . $ticker->id . '): ' . $e->getMessage());
            return back()->with('error', 'عذراً، فشل في تحديث النص. يرجى التأكد من صحة البيانات والمحاولة مرة أخرى.');
        }
    }

    /**
     * حذف نص من الشريط المتحرك
     */
    public function destroy(Ticker $ticker)
    {
        try {
            $ticker->delete();
            return back()->with('success', 'تم حذف النص بنجاح من الشريط المتحرك.');
        } catch (Exception $e) {
            Log::error('Ticker Destroy Error (ID ' . $ticker->id . '): ' . $e->getMessage());
            return back()->with('error', 'عذراً، لا يمكن حذف هذا النص في الوقت الحالي. يرجى المحاولة لاحقاً.');
        }
    }
}
