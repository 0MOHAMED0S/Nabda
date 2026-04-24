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
use App\Http\Controllers\Api\Foundation\FoundationAuthController;
use App\Http\Controllers\Api\Foundation\FoundationBranchController;
use App\Http\Controllers\Api\Foundation\FoundationCaseController;
use App\Http\Controllers\Api\Foundation\FoundationController;
use App\Http\Controllers\Api\Foundation\FoundationFaqController;
use App\Http\Controllers\Api\Foundation\FoundationGoalController;
use App\Http\Controllers\Api\Foundation\FoundationProfileController;
use App\Http\Controllers\Api\Public\DonationController;
use App\Http\Controllers\Api\Services\ServiceController;
use App\Http\Controllers\Api\Zakat\ZakatController;

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
    });
});



use App\Http\Controllers\Api\User\UserAuthController;

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
Route::prefix('volunteer')->group(function () {
    Route::post('/register', [VolunteerAuthController::class, 'register']);
    Route::post('/login', [VolunteerAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'volunteer'])->group(function () {
        Route::post('/logout', [VolunteerAuthController::class, 'logout']);
    });
});
