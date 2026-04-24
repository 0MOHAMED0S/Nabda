<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationTeamController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية لجميع الحالات
     */
    private $validationMessages = [
        'name.required'     => 'حقل الاسم مطلوب ولا يمكن تركه فارغاً.',
        'name.string'       => 'يجب أن يكون الاسم نصاً.',
        'name.max'          => 'يجب ألا يتجاوز طول الاسم 255 حرفاً.',

        'position.required' => 'حقل المنصب مطلوب.',
        'position.string'   => 'يجب أن يكون المنصب نصاً.',
        'position.max'      => 'يجب ألا يتجاوز المنصب 255 حرفاً.',

        'phone.required'    => 'رقم الهاتف مطلوب للتواصل.',
        'phone.string'      => 'رقم الهاتف يجب أن يكون نصاً.',
        'phone.max'         => 'رقم الهاتف أطول من المسموح به.',

        'status.required'   => 'يرجى تحديد حالة العضو (نشط أم مؤرشف).',
        'status.in'         => 'حالة العضو المدخلة غير صحيحة (يجب أن تكون: active أو archived).',

        'image.required'    => 'الصورة الشخصية للعضو مطلوبة.',
        'image.image'       => 'الملف المرفق يجب أن يكون صورة صحيحة.',
        'image.mimes'       => 'صيغ الصور المدعومة هي: jpeg, png, jpg, webp.',
        'image.max'         => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
    ];

    /**
     * API: جلب جميع أعضاء الفريق للمؤسسة الحالية
     */
    public function index(Request $request)
    {
        try {
            $team = $request->user()->teamMembers()->orderBy('created_at', 'desc')->get();

            if ($team->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا يوجد أعضاء في فريق العمل حالياً. يمكنك إضافة أعضاء جدد.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب أعضاء فريق العمل بنجاح.',
                'data'    => $team
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Team Index Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب البيانات.'
            ], 500);
        }
    }

    /**
     * API: إضافة عضو جديد للفريق
     */
    public function store(Request $request)
    {
        // جميع البيانات هنا مطلوبة إجبارياً (Required)
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'phone'    => 'required|string|max:50',
            'status'   => 'required|in:active,archived',
            'image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى إكمال جميع الحقول المطلوبة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only(['name', 'position', 'phone', 'status']);

            // رفع الصورة الإجبارية
            $data['image'] = $request->file('image')->store('foundations/team', 'public');

            $member = $request->user()->teamMembers()->create($data);

            return response()->json([
                'status'  => true,
                'message' => 'تمت إضافة العضو إلى فريق العمل بنجاح.',
                'data'    => $member
            ], 201);

        } catch (Exception $e) {
            Log::error("API Foundation Team Store Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع أثناء حفظ بيانات العضو.'
            ], 500);
        }
    }

    /**
     * API: عرض تفاصيل عضو محدد
     */
    public function show(Request $request, $id)
    {
        try {
            $member = $request->user()->teamMembers()->find($id);

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لم يتم العثور على العضو المطلوب، أو أنه لا يتبع لمؤسستك.'
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل العضو بنجاح.',
                'data'    => $member
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Team Show Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات العضو.'
            ], 500);
        }
    }

    /**
     * API: تعديل بيانات العضو (يدعم التحديث الجزئي باستخدام sometimes)
     */
    public function update(Request $request, $id)
    {
        // استخدام sometimes يجعل الحقل مطلوباً فقط إذا تم تمريره في الطلب
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|string|max:255',
            'phone'    => 'sometimes|required|string|max:50',
            'status'   => 'sometimes|required|in:active,archived',
            'image'    => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات التحديث غير صالحة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // التأكد من أن المستخدم أرسل بيانات لتحديثها فعلياً (تجنب استعلامات داتابيز فارغة)
        if (empty($request->all()) && !$request->hasFile('image')) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم إرسال أي بيانات جديدة ليتم تحديثها.'
            ], 400);
        }

        try {
            $member = $request->user()->teamMembers()->find($id);

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'العضو المراد تحديث بياناته غير موجود.'
                ], 404);
            }

            $data = $request->only(['name', 'position', 'phone', 'status']);

            // إذا أرسل صورة جديدة، نمسح القديمة ونرفع الجديدة
            if ($request->hasFile('image')) {
                if ($member->image && Storage::disk('public')->exists($member->image)) {
                    Storage::disk('public')->delete($member->image);
                }
                $data['image'] = $request->file('image')->store('foundations/team', 'public');
            }

            // تحديث الحقول التي تم إرسالها فقط
            if (!empty($data)) {
                $member->update($data);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات العضو بنجاح.',
                'data'    => $member
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Team Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء محاولة التحديث.'
            ], 500);
        }
    }

    /**
     * API: حذف عضو من الفريق
     */
    public function destroy(Request $request, $id)
    {
        try {
            $member = $request->user()->teamMembers()->find($id);

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'العضو المراد حذفه غير موجود بالفعل.'
                ], 404);
            }

            // حذف الصورة من السيرفر قبل الحذف من قاعدة البيانات للحفاظ على المساحة
            if ($member->image && Storage::disk('public')->exists($member->image)) {
                Storage::disk('public')->delete($member->image);
            }

            $member->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف العضو من فريق العمل بنجاح.'
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Team Delete Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة الحذف.'
            ], 500);
        }
    }
}
