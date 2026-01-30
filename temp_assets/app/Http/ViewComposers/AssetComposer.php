<?php
namespace Pterodactyl\Http\ViewComposers;
use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;
class AssetComposer
{
    use \Pterodactyl\Traits\Helpers\ThemeLanguages;
    public function __construct(private AssetHashService $assetHashService)
    {
    }
    public function compose(View $view): void
    {
        $locale = 'en';
        try {
            $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
            $addonConfig = json_decode($settingsRepository->get('settings::app:addons:hyperv1', '{}'), true);
            $locale = $addonConfig['addons']['LanguageTranslations']['defaultLanguage'] ?? 'en';
        } catch (\Throwable $e) {
        }
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => $locale,
            'theme' => config('app.theme') ?? 'default',
            'hyperv1LicenseKey' => config('app.hyperv1_license'),
            'languages' => $this->getLanguagesSafe(),
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
        ]);
    }
    private function getLanguagesSafe(): array
    {
        try {
            $langs = $this->getThemeLanguages(true);
            return is_array($langs) && !empty($langs) ? $langs : ['en' => 'English'];
        } catch (\Throwable) {
            return ['en' => 'English'];
        }
    }
}
