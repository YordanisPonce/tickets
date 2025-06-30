<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Workdo\InstagramChat\Http\Controllers\Company\SettingsController;
use Workdo\InstagramChat\Http\Controllers\InstagramWebhookController;

Route::group(['middleware' => ['web', 'auth', 'verified','ModuleCheckEnable:InstagramChat']], function () {
    Route::post('instagram/setting',[SettingsController::class,'storeInsta'])->name('instagram.setting.store');
});

// Route::any('/instagram/webhook', [InstagramWebhookController::class, 'handleWebhook'])->name('instagram.webhook')->withoutMiddleware([VerifyCsrfToken::class]);
