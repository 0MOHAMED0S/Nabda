<?php

use App\Http\Controllers\Admin\aboutUs\AboutGoal2Controller;
use App\Http\Controllers\Admin\aboutUs\AboutGoalController;
use App\Http\Controllers\Admin\aboutUs\AboutHistoryController;
use App\Http\Controllers\Admin\aboutUs\AboutUsController;
use App\Http\Controllers\Admin\aboutUs\AboutVisionController;
use App\Http\Controllers\Admin\aboutUs\TeamController;
use App\Http\Controllers\Admin\Articles\ArticleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\Contacts\BranchController;

use App\Http\Controllers\Admin\Contacts\ContactInfoController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\Foundation\FoundationController;
use App\Http\Controllers\Admin\hero\HeroController;
use App\Http\Controllers\Admin\hero\TickerController;
use App\Http\Controllers\Admin\Rate\ReviewController;
use App\Http\Controllers\Admin\Service\CategoryController;
use App\Http\Controllers\Admin\Service\ServiceController;
use App\Http\Controllers\Admin\ZakatConditionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');



    // Protected routes
    Route::middleware(['admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::view('/dashboard', 'admin.index')->name('dashboard');

        Route::get('profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

        //hero
        Route::get('/hero', [HeroController::class, 'edit'])->name('hero.edit');
        Route::post('/hero/update', [HeroController::class, 'update'])->name('hero.update');

        //tricker
        Route::resource('tickers', TickerController::class)->only(['index', 'store', 'update', 'destroy']);

        //zakat
        Route::resource('zakat-conditions', ZakatConditionController::class)->names('zakat');

        //team
        Route::resource('team', TeamController::class)->names('team');

        //goals
        Route::resource('about-goals1', AboutGoalController::class)->names('about_goals1');
        Route::resource('about-goals2', AboutGoal2Controller::class)->names('about_goals2');

        Route::get('about-us', [AboutUsController::class, 'index'])->name('about_us.index');
        Route::put('about-us', [AboutUsController::class, 'update'])->name('about_us.update');

        Route::resource('about-histories', AboutHistoryController::class)->names('about_histories');

        Route::resource('about-visions', AboutVisionController::class)->names('about_visions');

        Route::resource('categories', CategoryController::class)->names('categories');
        Route::resource('services', ServiceController::class)->names('services');

        Route::resource('faqs', FaqController::class)->names('faqs');

        Route::get('contact-info', [ContactInfoController::class, 'edit'])->name('contact_info.edit');
        Route::put('contact-info', [ContactInfoController::class, 'update'])->name('contact_info.update');
        Route::resource('branches', BranchController::class)->names('branches');

        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
        Route::post('reviews/{review}/toggle', [ReviewController::class, 'toggleApprove'])->name('reviews.toggle');

        Route::resource('articles', ArticleController::class)
            ->names('articles')
            ->only(['index', 'store', 'update', 'destroy']);

        Route::prefix('foundations')->name('foundations.')->group(function () {
        Route::get('/approved', [FoundationController::class, 'approvedIndex'])->name('approved');
        Route::get('/', [FoundationController::class, 'index'])->name('index');
        Route::put('/{foundation}', [FoundationController::class, 'update'])->name('update');
        Route::put('/{foundation}/reject', [FoundationController::class, 'reject'])->name('reject'); // مسار الرفض
        Route::delete('/{foundation}', [FoundationController::class, 'destroy'])->name('destroy');
    });
    });
});
