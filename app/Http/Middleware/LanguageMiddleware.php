<?php
namespace Pterodactyl\Http\Middleware;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
class LanguageMiddleware
{
    public function __construct(private Application $app)
    {
    }
    public function handle(Request $request, \Closure $next): mixed
    {
        $locale = $this->getDefaultLocale();
        if ($request->user() && $request->user()->language) {
            $locale = $request->user()->language;
        } elseif ($request->session()->get('locale')) {
            $locale = $request->session()->get('locale');
        }
        $this->app->setLocale($locale);
        return $next($request);
    }
    private function getDefaultLocale(): string
    {
        try {
            $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
            $addonConfig = json_decode($settingsRepository->get('settings::app:addons:arelix', '{}'), true);
            return $addonConfig['addons']['LanguageTranslations']['defaultLanguage'] ?? 'en';
        } catch (\Throwable $e) {
            return 'en';
        }
    }
}
