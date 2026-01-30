<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Base;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();
Route::get('/account', [Base\IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/locales/locale.json', Base\LocaleController::class)
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->where('namespace', '.*');

Route::get('/theme/hyperv1', [Base\HyperV1ThemePublicController::class, 'show'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class]);

Route::get('/language/available', [Base\LanguageController::class, 'available'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class]);
Route::patch('/language', [Base\LanguageController::class, 'set'])
    ->name('language.set');

Route::get('/referral/{code}', [Pterodactyl\Http\Controllers\Auth\ReferralController::class, 'index'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class]);

Route::get('/status', [Base\PublicStatusPageController::class, 'index'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->name('public.status');

Route::get('/public/stats', [Base\PublicStatsController::class, 'index'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->name('public.stats');

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
