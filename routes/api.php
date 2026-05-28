<?php

use App\Http\Controllers\Api\Foundation\FoundationPasswordController;
use App\Http\Controllers\Api\Foundation\FoundationTeamController;
use App\Http\Controllers\Api\Hero\HeroController;
use App\Http\Controllers\Api\Hero\TickerController;
use App\Http\Controllers\Api\Rate\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Contacts\ContactController;
use App\Http\Controllers\Api\AboutUs\AboutUsController;
use App\Http\Controllers\Api\Article\ArticleController;
use App\Http\Controllers\Api\Faq\FaqController;
use App\Http\Controllers\Api\Foundation\CaseUpdateController;
use App\Http\Controllers\Api\Foundation\FoundationAuthController;
use App\Http\Controllers\Api\Foundation\FoundationBranchController;
use App\Http\Controllers\Api\Foundation\FoundationCaseController;
use App\Http\Controllers\Api\Foundation\FoundationController;
use App\Http\Controllers\Api\Foundation\FoundationDonationController;
use App\Http\Controllers\Api\Foundation\FoundationFaqController;
use App\Http\Controllers\Api\Foundation\FoundationGoalController;
use App\Http\Controllers\Api\Foundation\FoundationProfileController;
use App\Http\Controllers\Api\Foundation\FoundationVolunteerController;
use App\Http\Controllers\Api\Foundation\VolunteerOpportunityController;
use App\Http\Controllers\Api\Public\DonationController;
use App\Http\Controllers\Api\Services\ServiceController;
use App\Http\Controllers\Api\Zakat\ZakatController;
use App\Http\Controllers\Api\Foundation\OpportunityApplicationController;
use App\Http\Controllers\Api\Foundation\OpportunityReportEvaluationController;
use App\Http\Controllers\Api\Foundation\FoundationDashboardController;
use App\Http\Controllers\Api\Public\FoundationRatingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//reviews
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);
//hero section
Route::get('/hero', [HeroController::class, 'index']);
//tricker
Route::get('/tickers', [TickerController::class, 'index']);

// 🎯 إضافة تقييم لمؤسسة (متاح للجميع: زوار ومسجلين)
Route::post('/foundations/{foundationId}/rate', [FoundationRatingController::class, 'store']);

Route::prefix('about-us')->group(function () {
    Route::get('/info', [AboutUsController::class, 'getAboutInfo']);
    Route::get('/visions', [AboutUsController::class, 'getVisions']);
    Route::get('/goals-1', [AboutUsController::class, 'getGoalsPart1']);
    Route::get('/goals-2', [AboutUsController::class, 'getGoalsPart2']);
    Route::get('/histories', [AboutUsController::class, 'getHistories']);
    Route::get('/team', [AboutUsController::class, 'getTeam']);
});
Route::prefix('services')->group(function () {
    Route::get('/categories', [ServiceController::class, 'index']);
    Route::get('/all', [ServiceController::class, 'allServices']);
    Route::get('/{serviceId}/cases', [ServiceController::class, 'getServiceCases']);
    Route::get('/{serviceId}', [ServiceController::class, 'show']);
});
Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/{id}', [ArticleController::class, 'show']);
});
Route::prefix('contact')->group(function () {
    Route::get('/info', [ContactController::class, 'getContactInfo']);
    Route::get('/branches', [ContactController::class, 'getBranches']);
});
Route::prefix('zakat')->group(function () {
    Route::get('/', [ZakatController::class, 'getConditions']);
    Route::get('/gold-prices', [ZakatController::class, 'getGoldPrices']);
});
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('foundation/', [FoundationController::class, 'index']);
Route::get('foundation/{id}/show', [FoundationController::class, 'show']);
Route::get('/foundation/{id}/cases', [FoundationController::class, 'getFoundationCases']);
Route::get('/cases/{caseId}', [FoundationController::class, 'getCaseDetails']);

Route::middleware('auth:sanctum')->group(function () {
    // 🎯 جلب سجل تبرعات المستخدم الحالي وإحصائياته (للوحة التحكم)
    Route::get('/user/donations', [DonationController::class, 'index']);
});
// 🎯 جلب خدمات/حملات المؤسسة (التبويب الثاني في بروفايل المؤسسة)
Route::get('/foundations/{id}/services', [FoundationController::class, 'getFoundationServices']);
// 🎯 جلب معلومات الاتصال والفروع الخاصة بمؤسسة (التبويب الأخير)
Route::get('/foundations/{id}/contact', [FoundationController::class, 'getContactDetails']);
Route::prefix('foundation')->group(function () {
    Route::post('/register', [FoundationAuthController::class, 'register']);
    Route::post('/login', [FoundationAuthController::class, 'login']);

    // مسار الدفع (يمكن أن يكون داخل auth:sanctum أو خارجه، لأن دالة store تعالج الحالتين)
    Route::post('/donate', [DonationController::class, 'store']);
    // مسار استقبال الرد من Paymob (يجب أن يكون خااارج الـ auth:sanctum ومفتوح للجميع)
    Route::post('/paymob/callback', [DonationController::class, 'paymobCallback']);

    Route::middleware(['auth:sanctum', 'foundation'])->group(function () {
        Route::post('/logout', [FoundationAuthController::class, 'logout']);
        Route::apiResource('faqs', FoundationFaqController::class);
        Route::apiResource('team', FoundationTeamController::class);
        Route::apiResource('goals', FoundationGoalController::class);
        Route::apiResource('branches', FoundationBranchController::class);
        Route::post('cases/{id}/delete-file', [FoundationCaseController::class, 'deleteFile']);
        Route::apiResource('cases', FoundationCaseController::class);
        Route::patch('cases/{id}/status', [FoundationCaseController::class, 'updateStatus']);
        Route::get('/profile', [FoundationProfileController::class, 'show']);
        Route::post('/profile', [FoundationProfileController::class, 'update']);
        Route::post('/change-password', [FoundationPasswordController::class, 'update']);

        Route::get('/opportunities', [VolunteerOpportunityController::class, 'index']);
        Route::get('/opportunities/{id}', [VolunteerOpportunityController::class, 'show']);
        Route::post('/opportunities', [VolunteerOpportunityController::class, 'store']);
        Route::put('/opportunities/{id}', [VolunteerOpportunityController::class, 'update']);
        Route::delete('/opportunities/{id}', [VolunteerOpportunityController::class, 'destroy']);

        Route::get('/cases/{caseId}/updates', [CaseUpdateController::class, 'index']); // 👈 جلب كل التحديثات لحالة محددة
        Route::post('/cases/{caseId}/updates', [CaseUpdateController::class, 'store']); // إضافة تحديث
        Route::put('/cases/updates/{updateId}', [CaseUpdateController::class, 'update']); // تعديل تحديث
        Route::delete('/cases/updates/{updateId}', [CaseUpdateController::class, 'destroy']); // حذف تحديث


        Route::get('/donations', [FoundationDonationController::class, 'index']); // القائمة والفلترة
        Route::get('/donations/report', [FoundationDonationController::class, 'report']); // التقرير الرقابي
        Route::get('/donations/{id}', [FoundationDonationController::class, 'show']); // تفاصيل تبرع
        Route::put('/donations/{id}/status', [FoundationDonationController::class, 'updateStatus']); // تغيير الحالة
        Route::delete('/donations/{id}', [FoundationDonationController::class, 'destroy']); // حذف

        Route::get('/volunteers', [FoundationVolunteerController::class, 'index']); // جلب الجدول والإحصائيات
        Route::patch('/volunteers/{id}/toggle-status', [FoundationVolunteerController::class, 'toggleStatus']); // تفعيل وإيقاف المتطوع
        // 🎯 عرض الملف الشخصي لمتطوع محدد (تفاصيل المتطوع)
        Route::get('/volunteers/{id}', [FoundationVolunteerController::class, 'show']);
        // 🎯 عرض تفاصيل نشاط تطوعي محدد (تقرير منتهي)
        Route::get('/activities/{reportId}/details', [FoundationVolunteerController::class, 'showActivityDetails']);
        // ... داخل مجموعة مسارات المؤسسة (Foundation) ...

        // 🎯 مسارات إدارة طلبات الفرص التطوعية
        Route::get('/opportunities/{id}/applications', [OpportunityApplicationController::class, 'index']); // جلب الطلبات والمسجلين
        Route::post('/opportunities/{opportunityId}/applications/{volunteerId}/accept', [OpportunityApplicationController::class, 'accept']); // قبول
        Route::post('/opportunities/{opportunityId}/applications/{volunteerId}/reject', [OpportunityApplicationController::class, 'reject']); // رفض


        // 🎯 مسارات تقييم تقارير المتطوعين
        Route::get('/opportunities/{opportunityId}/reports', [OpportunityReportEvaluationController::class, 'index']); // جلب كل تقارير الفرصة
        Route::post('/reports/{reportId}/evaluate', [OpportunityReportEvaluationController::class, 'evaluate']); // تقييم التقرير (موافقة/رفض، ساعات، نجوم)


        // 🎯 لوحة التحكم الرئيسية للمؤسسة
        Route::get('/dashboard', [FoundationDashboardController::class, 'index']);
    });
});



use App\Http\Controllers\Api\User\UserAuthController;
use App\Http\Controllers\Api\Volunteer\ExploreOpportunityController;

Route::prefix('user')->group(function () {
    Route::post('/register', [UserAuthController::class, 'register']);
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'is_user'])->group(function () {
        Route::post('/profile/update', [UserAuthController::class, 'updateProfile']);
        Route::post('/change-password', [UserAuthController::class, 'updatePassword']);
        Route::get('/profile', [UserAuthController::class, 'profile']);
        Route::post('/logout', [UserAuthController::class, 'logout']);
    });
});


use App\Http\Controllers\Api\Volunteer\VolunteerAuthController;
use App\Http\Controllers\Api\Volunteer\VolunteerPasswordController;
use App\Http\Controllers\Api\Volunteer\VolunteerProfileController;
use App\Http\Controllers\Api\Volunteer\CompletedActivityController;
use App\Http\Controllers\Api\Volunteer\VolunteerHoursController;
use App\Http\Controllers\Api\Volunteer\VolunteerRatingController;
use App\Http\Controllers\Api\Volunteer\VolunteerDashboardController;

Route::prefix('volunteer')->group(function () {
    Route::post('/register', [VolunteerAuthController::class, 'register']);
    Route::post('/login', [VolunteerAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'volunteer'])->group(function () {
        Route::post('/logout', [VolunteerAuthController::class, 'logout']);
        Route::post('/change-password', [VolunteerPasswordController::class, 'update']);

        Route::get('/profile', [VolunteerProfileController::class, 'show']);
        Route::post('/profile/update', [VolunteerProfileController::class, 'update']);

        Route::get('/explore-opportunities', [ExploreOpportunityController::class, 'index']);
        Route::get('/explore-opportunities/{id}', [ExploreOpportunityController::class, 'show']);
        Route::post('/explore-opportunities/{id}/apply', [ExploreOpportunityController::class, 'apply']);
        Route::post('/explore-opportunities/{id}/cancel', [ExploreOpportunityController::class, 'cancel']);
        // 🎯 قبول ورفض الدعوات
        Route::post('/explore-opportunities/{id}/accept', [\App\Http\Controllers\Api\Volunteer\VolunteerOpportunityController::class, 'acceptInvitation']);
        Route::post('/explore-opportunities/{id}/reject', [\App\Http\Controllers\Api\Volunteer\VolunteerOpportunityController::class, 'rejectInvitation']);
        Route::post('/explore-opportunities/{id}/report', [ExploreOpportunityController::class, 'submitReport']);
        Route::get('/my-tasks', [ExploreOpportunityController::class, 'myTasks']);

        // 🎯 الأنشطة المنتهية للمتطوع (سجل الإنجازات والتقييمات)
        Route::get('/completed-activities', [CompletedActivityController::class, 'index']);

        // 🎯 سجل الساعات التطوعية والتأثير المجتمعي
        Route::get('/hours-record', [VolunteerHoursController::class, 'index']);

        // 🎯 تقييم التطوع وآراء المؤسسات
        Route::get('/ratings', [VolunteerRatingController::class, 'index']);

        // 🎯 لوحة التحكم الرئيسية للمتطوع
        Route::get('/dashboard', [VolunteerDashboardController::class, 'index']);
    });
});
