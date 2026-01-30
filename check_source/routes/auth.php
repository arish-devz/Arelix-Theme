<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

// These routes are defined so that we can continue to reference them programmatically.
// They all route to the same controller function which passes off to React.
Route::get('/login', [Auth\LoginController::class, 'index'])->name('auth.login');
Route::get('/register', [Auth\RegisterController::class, 'index'])->name('auth.register');
Route::get('/password', [Auth\LoginController::class, 'index'])->name('auth.forgot-password');
Route::get('/password/reset/{token}', [Auth\LoginController::class, 'index'])->name('auth.reset');

// Apply a throttle to authentication action endpoints, in addition to the
// recaptcha endpoints to slow down manual attack spammers even more. 🤷‍
//
// @see \Pterodactyl\Providers\RouteServiceProvider
Route::middleware(['throttle:authentication'])->group(function () {
    Route::post('/login', [Auth\LoginController::class, 'login'])->middleware('recaptcha');
    Route::post('/login/checkpoint', Auth\LoginCheckpointController::class)->name('auth.login-checkpoint');

    Route::post('/register', [Auth\RegisterController::class, 'register'])->middleware('recaptcha');

    Route::post('/password', [Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('auth.post.forgot-password')
        ->middleware('recaptcha');
});

Route::post('/password/reset', Auth\ResetPasswordController::class)->name('auth.reset-password');

Route::get('/login/sso/{provider}', [Auth\SSOLoginController::class, 'redirect'])
    ->withoutMiddleware('guest')
    ->name('auth.sso.redirect');
Route::get('/login/sso/{provider}/callback', [Auth\SSOLoginController::class, 'callback'])
    ->withoutMiddleware('guest')
    ->name('auth.sso.callback');
Route::delete('/account/sso/{provider}', [Auth\SSOLoginController::class, 'unlink'])
    ->withoutMiddleware('guest')
    ->name('auth.sso.unlink');


Route::get('/account/sso', [Auth\SSOLoginController::class, 'index'])
    ->withoutMiddleware('guest')
    ->name('auth.sso.index');

Route::get('/sso-wemx', [Auth\WemxSsoController::class, 'webhook'])
    ->withoutMiddleware('guest')
    ->name('sso-wemx.webhook');
Route::get('/sso-wemx/{token}', [Auth\WemxSsoController::class, 'handle'])
    ->withoutMiddleware('guest')
    ->name('sso-wemx.login');


Route::post('/logout', [Auth\LoginController::class, 'logout'])
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

Route::withoutMiddleware('guest')->middleware('auth')->group(function () {
    Route::get('/passkey', [Auth\PasskeyController::class, 'index'])->name('auth.passkey.index');
    Route::post('/passkey/register/options', [Auth\PasskeyController::class, 'registerOptions'])->name('auth.passkey.register.options');
    Route::post('/passkey/register', [Auth\PasskeyController::class, 'register'])->name('auth.passkey.register');
    Route::delete('/passkey/{id}', [Auth\PasskeyController::class, 'delete'])->name('auth.passkey.delete');
});

Route::middleware(['throttle:authentication'])->group(function () {
    Route::post('/passkey/login/options', [Auth\PasskeyController::class, 'authenticateOptions'])->name('auth.passkey.login.options');
    Route::post('/passkey/login', [Auth\PasskeyController::class, 'authenticate'])->name('auth.passkey.login');
});

Route::fallback([Auth\LoginController::class, 'index']);
