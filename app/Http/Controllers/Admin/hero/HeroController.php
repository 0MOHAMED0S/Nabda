<?php

namespace App\Http\Controllers\Admin\Hero;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class HeroController extends Controller
{
    public function edit()
    {
        try {
            $hero = HeroSection::firstOrCreate(
                ['id' => 1],
                [
                    'title'       => 'عنوان الواجهة الرئيسية',
                    'description' => 'وصف تفصيلي يظهر تحت العنوان الرئيسي.',
                ]
            );

            return view('admin.welcomeHero.hero.index', compact('hero'));
        } catch (Exception $e) {
            Log::error("Hero Edit Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات القسم.');
        }
    }

    public function update(Request $request)
    {
        // 1. التحقق من صحة البيانات (تم تحديث شروط الفيديو)
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'video'       => 'nullable|mimes:mp4,mov,ogg,qt|max:20480', // حد أقصى 20 ميجابايت
        ], [
            'title.required'       => 'يرجى إدخال العنوان الرئيسي.',
            'description.required' => 'يرجى إدخال الوصف.',
            'video.mimes'          => 'صيغة الفيديو غير مدعومة. الصيغ المقبولة: mp4, mov, ogg, qt.',
            'video.max'            => 'حجم الفيديو كبير جداً. الحد الأقصى هو 20 ميجابايت.',
        ]);

        try {
            $hero = HeroSection::findOrFail(1);
            $data = $request->only(['title', 'description']);

            // 2. معالجة الفيديو المرفوع
            if ($request->hasFile('video')) {

                // حذف الفيديو القديم إذا وجد
                if (!empty($hero->video) && Storage::disk('public')->exists($hero->video)) {
                    Storage::disk('public')->delete($hero->video);
                }

                // حفظ الفيديو الجديد في مجلد hero/videos
                $data['video'] = $request->file('video')->store('hero/videos', 'public');
            }

            $hero->update($data);

            return back()->with('success', 'تم تحديث بيانات الواجهة (الفيديو) بنجاح.');

        } catch (Exception $e) {
            Log::error("Hero Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حفظ التعديلات.');
        }
    }
}
