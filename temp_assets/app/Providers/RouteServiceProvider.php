<?php
namespace Pterodactyl\Providers;
use Illuminate\Http\Request;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Pterodactyl\Http\Middleware\TrimStrings;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
class RouteServiceProvider extends ServiceProvider
{
    protected const FILE_PATH_REGEX = '/^\/api\/client\/servers\/([a-z0-9-]{36})\/files(\/?$|\/(.)*$)/i';
    public function boot(): void
    {
        $this->configureRateLimiting();
        TrimStrings::skipWhen(function (Request $request) {
            return preg_match(self::FILE_PATH_REGEX, $request->getPathInfo()) === 1;
        });
        Route::model('database', Database::class);
        $this->routes(function () {
            Route::middleware('web')->group(function () {
                Route::middleware(['auth.session', RequireTwoFactorAuthentication::class])
                    ->group(base_path('routes/base.php'));
                Route::middleware(['auth.session', RequireTwoFactorAuthentication::class, AdminAuthenticate::class])
                    ->prefix('/admin')
                    ->group(base_path('routes/admin.php'));
                Route::middleware('guest')->prefix('/auth')->group(base_path('routes/auth.php'));
                Route::get('/api/client/addons', function () {
                    $defaultsService = app(\Pterodactyl\Services\HyperV1AddonDefaultsService::class);
                    $cacheKey = $defaultsService->getAddonsCacheKey();
                    $cachedJson = Cache::remember($cacheKey, 86400, function () use ($defaultsService) {
                        $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
                        $raw = $settingsRepository->get('settings::app:addons:hyperv1', '{}');
                        $decoded = [];
                        try {
                            $decoded = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
                        } catch (\Throwable) {
                            $decoded = [];
                        }
                        $defaultAddons = $defaultsService->getDefaultAddons();
                        $data = array_merge([
                            'addons' => $defaultAddons,
                            'updated_at' => null,
                            'app_url' => config('app.url'),
                        ], is_array($decoded) ? $decoded : []);
                        foreach ($data['addons'] as $addonKey => &$addonConfig) {
                            if (isset($defaultAddons[$addonKey]['enabled']) && !$defaultAddons[$addonKey]['enabled']) {
                                $addonConfig['enabled'] = false;
                            }
                        }
                        $data['addons'] = array_filter($data['addons'], function($addonConfig, $addonKey) use ($defaultAddons) {
                            return isset($defaultAddons[$addonKey]['enabled']) && $defaultAddons[$addonKey]['enabled'];
                        }, ARRAY_FILTER_USE_BOTH);
                        foreach ($data['addons'] as $addonKey => &$addonConfig) {
                            if (!isset($addonConfig['enabled']) || $addonConfig['enabled'] === false) {
                                $configKeysToRemove = [];
                                switch ($addonKey) {
                                    case 'Notifications':
                                        $configKeysToRemove = ['notifications', 'broadcast'];
                                        break;
                                }
                                foreach ($configKeysToRemove as $configKey) {
                                    if (isset($addonConfig[$configKey])) {
                                        unset($addonConfig[$configKey]);
                                    }
                                }
                            }
                            $allowedFields = $defaultsService->getAllowedFields()[$addonKey] ?? ['enabled', 'name', 'description', 'category'];
                            $filteredConfig = [];
                            foreach ($allowedFields as $field) {
                                if (array_key_exists($field, $addonConfig)) {
                                    $filteredConfig[$field] = $addonConfig[$field];
                                }
                            }
                            $addonConfig = $filteredConfig;
                        }
                        $sensitiveFields = ['secret_key', 'cloudflare_api_token', 'cloudflare_email'];
                        if (isset($data['addons']) && is_array($data['addons'])) {
                            foreach ($data['addons'] as $addonKey => &$addonConfig) {
                                if (is_array($addonConfig)) {
                                    foreach ($sensitiveFields as $field) {
                                        unset($addonConfig[$field]);
                                    }
                                }
                            }
                        }
                        return json_encode($data);
                    });
                    return response($cachedJson)->header('Content-Type', 'application/json');
                });
            });
            Route::post('/api/public/license/verify', [\Pterodactyl\Http\Controllers\Api\Application\LicenseController::class, 'verifyPublic'])
                ->middleware([\Pterodactyl\Http\Middleware\EnsureStatefulRequests::class, \Pterodactyl\Http\Middleware\Api\IsValidJson::class, 'throttle:60,1'])
                ->name('api.public.license.verify');
            Route::get('/api/public/license/status', [\Pterodactyl\Http\Controllers\Api\Application\LicenseController::class, 'statusPublic'])
                ->middleware([\Pterodactyl\Http\Middleware\EnsureStatefulRequests::class])
                ->name('api.public.license.status');
            Route::get('/api/public/license/clear-cache', [\Pterodactyl\Http\Controllers\Api\Application\LicenseController::class, 'clearCache'])
                ->middleware([\Pterodactyl\Http\Middleware\EnsureStatefulRequests::class])
                ->name('api.public.license.clear-cache');
            Route::get('/api/public/license/clear-addons-cache', [\Pterodactyl\Http\Controllers\Api\Application\LicenseController::class, 'clearAddonsCache'])
                ->middleware([\Pterodactyl\Http\Middleware\EnsureStatefulRequests::class])
                ->name('api.public.license.clear-addons-cache');
            Route::get('/api/public/license/clear-all-cache', [\Pterodactyl\Http\Controllers\Api\Application\LicenseController::class, 'clearAllCache'])
                ->middleware([\Pterodactyl\Http\Middleware\EnsureStatefulRequests::class])
                ->name('api.public.license.clear-all-cache');
            Route::get('/api/public/pwa/manifest.json', [\Pterodactyl\Http\Controllers\Api\Client\Theme\PwaController::class, 'manifest'])
                ->name('api.public.pwa.manifest');
            Route::get('/api/public/pwa/sw-config.js', [\Pterodactyl\Http\Controllers\Api\Client\Theme\PwaController::class, 'serviceWorkerConfig'])
                ->name('api.public.pwa.sw-config');
            Route::get('/api/public/node-status', [\Pterodactyl\Http\Controllers\Base\PublicNodeStatusController::class, 'index'])
                ->name('api.public.node-status');
                Route::middleware(['api', RequireTwoFactorAuthentication::class])->group(function () {
                    Route::middleware(['application-api', 'throttle:api.application'])
                        ->prefix('/api/application')
                        ->scopeBindings()
                        ->group(base_path('routes/api-application.php'));
                    Route::middleware(['client-api', 'throttle:api.client'])
                        ->prefix('/api/client')
                        ->scopeBindings()
                        ->group(base_path('routes/api-client.php'));
                });            Route::middleware('daemon')
                ->prefix('/api/remote')
                ->scopeBindings()
                ->group(base_path('routes/api-remote.php'));
        });
    }
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('authentication', function (Request $request) {
            if ($request->route()->named('auth.post.forgot-password')) {
                return Limit::perMinute(2)->by($request->ip());
            }
            return Limit::perMinute(10);
        });
        RateLimiter::for('api.client', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();
            return Limit::perMinutes(
                config('http.rate_limit.client_period'),
                config('http.rate_limit.client')
            )->by($key);
        });
        RateLimiter::for('api.application', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();
            return Limit::perMinutes(
                config('http.rate_limit.application_period'),
                config('http.rate_limit.application')
            )->by($key);
        });
    }
}
