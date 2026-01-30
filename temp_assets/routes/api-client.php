<?php

use Pterodactyl\Http\Controllers\Api\Client\Servers\Rolexdev\FiveMUtilsController;

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Activity\AccountSubject;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Controllers\Api\Client\Servers\Rolexdev\NodeStatusController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\Rolexdev\CustomMonitorController;
use Pterodactyl\Http\Controllers\Api\Client\Rolexdev\LoginActivityController;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', [Client\ClientController::class, 'index'])->name('api:client.index');
Route::get('/permissions', [Client\ClientController::class, 'permissions']);

Route::get('/public/node-status', [Pterodactyl\Http\Controllers\Base\PublicNodeStatusController::class, 'index'])
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class, 'client-api']);

Route::prefix('/account')->middleware(AccountSubject::class)->group(function () {
    Route::prefix('/')->withoutMiddleware(RequireTwoFactorAuthentication::class)->group(function () {
        Route::get('/', [Client\AccountController::class, 'index'])->name('api:client.account');
        Route::get('/two-factor', [Client\TwoFactorController::class, 'index']);
        Route::post('/two-factor', [Client\TwoFactorController::class, 'store']);
        Route::post('/two-factor/disable', [Client\TwoFactorController::class, 'delete']);
    });

    Route::put('/info', [Client\AccountController::class, 'updateAccountInfo'])->name('api:client.account.update-info');
    Route::put('/email', [Client\AccountController::class, 'updateEmail'])->name('api:client.account.update-email');
    Route::put('/password', [Client\AccountController::class, 'updatePassword'])->name('api:client.account.update-password');

    Route::get('/activity', Client\ActivityLogController::class)->name('api:client.account.activity');

    Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
    Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

    Route::prefix('/ssh-keys')->group(function () {
        Route::get('/', [Client\SSHKeyController::class, 'index']);
        Route::post('/', [Client\SSHKeyController::class, 'store']);
        Route::post('/remove', [Client\SSHKeyController::class, 'delete']);
    });

    Route::group(['prefix' => '/login-activity'], function () {
        Route::get('/', [Client\Rolexdev\LoginActivityController::class, 'index']);
        Route::post('/revoke/{sessionId}', [Client\Rolexdev\LoginActivityController::class, 'revoke']);
    });
});

Route::group(['prefix' => '/theme'], function () {
    Route::get('/hyperv1', [Client\Theme\HyperV1ThemeController::class, 'show']);
    Route::put('/hyperv1', [Client\Theme\HyperV1ThemeController::class, 'update']);
    Route::get('/hyperv1/version', [Client\Theme\HyperV1ThemeController::class, 'checkVersion']);
    Route::post('/hyperv1/update', [Client\Theme\HyperV1ThemeController::class, 'startUpdate']);
    Route::get('/hyperv1/update/status', [Client\Theme\HyperV1ThemeController::class, 'getUpdateStatus']);
    Route::get('/hyperv1/sidebar', [Client\Theme\HyperV1ThemeController::class, 'getAvailableSidebarItems']);
});

Route::group(['prefix' => '/addons', 'middleware' => [\Pterodactyl\Http\Middleware\CompressResponse::class]], function () {
    Route::get('/defaults', [Client\Theme\HyperV1AddonController::class, 'defaults']);
    Route::put('/', [Client\Theme\HyperV1AddonController::class, 'update']);
    Route::get('/check-server-availability', [Client\Theme\HyperV1AddonController::class, 'checkServerAvailability']);
    
    Route::post('/subdomain-manager/test-connection', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'testConnection']);
    Route::post('/subdomain-manager/fetch-domains', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'fetchDomains']);
    Route::get('/subdomain-manager/fetch-all-subdomains', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'fetchAllSubdomains']);
    Route::delete('/subdomain-manager/subdomains/{id}', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'deleteSubdomainAdmin']);

    Route::get('/template-installer/templates', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'index']);
    Route::post('/template-installer/templates', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'store']);
    Route::put('/template-installer/templates/{id}', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'update']);
    Route::delete('/template-installer/templates/{id}', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'destroy']);

    Route::group(['prefix' => '/discord-bot'], function () {
        Route::get('/stats', [Client\Admin\RolexDev\DiscordBotController::class, 'stats']);
        Route::post('/sync', [Client\Admin\RolexDev\DiscordBotController::class, 'triggerSync']);
        Route::get('/bot-status', [Client\Admin\RolexDev\DiscordBotController::class, 'botStatus']);
        Route::post('/restart', [Client\Admin\RolexDev\DiscordBotController::class, 'restartBot']);
    });

})->withoutMiddleware(['client-api']);

Route::get('admin/addons/server-type-changer/all-nests-eggs', [Pterodactyl\Http\Controllers\Api\Client\Servers\Rolexdev\ServerTypeChangerController::class, 'getAllNestsAndEggs'])->withoutMiddleware(['client-api']);

Route::group(['prefix' => '/addons/server-importer'], function () {
    Route::post('/test-connection', [Client\Servers\Rolexdev\ServerImporterController::class, 'testConnection']);
    Route::get('/imports', [Client\Servers\Rolexdev\ServerImporterController::class, 'userImports']);
});

Route::group(['prefix' => '/addons/upload-from-url'], function () {
    Route::post('/query', [Client\Servers\Rolexdev\UploadFromUrlController::class, 'query']);
});

Route::group(['prefix' => 'addons'], function () {
    Route::get('/node-status', [NodeStatusController::class, 'index']);
    Route::get('/node-status/monitors', [CustomMonitorController::class, 'index']);
    Route::post('/node-status/monitors', [CustomMonitorController::class, 'store']);
    Route::put('/node-status/monitors/{id}', [CustomMonitorController::class, 'update']);
    Route::delete('/node-status/monitors/{id}', [CustomMonitorController::class, 'destroy']);
    Route::get('/login-activity', [LoginActivityController::class, 'index']);
    Route::post('/login-activity/revoke', [LoginActivityController::class, 'revoke']);

    Route::post('/rolexdev/server-stats', [Pterodactyl\Http\Controllers\Api\Client\Servers\Rolexdev\ServerStatsController::class, 'batch']);

    Route::group(['prefix' => '/billing'], function () {
        Route::get('/balance', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getBalance']);
        Route::post('/top-up', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'initiateTopUp']);
        Route::post('/verify', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'verifyTransaction']);
        Route::get('/transactions', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getTransactions']);
        
        Route::get('/admin/stats', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getAdminStats']);
        Route::get('/admin/transactions', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getAllTransactions']);
        Route::get('/admin/users-with-credits', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getUsersWithCredits']);
        
        Route::get('/store/categories', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\StoreController::class, 'index']);
        Route::get('/store/nodes', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\StoreController::class, 'getNodes']);
        Route::get('/store/categories/{shortUrl}', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\StoreController::class, 'showCategory']);
        
        Route::post('/order/create', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\OrderController::class, 'createServer']);
        Route::post('/order/renew/{serverUuid}', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\OrderController::class, 'renewServer']);
        Route::get('/services', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\OrderController::class, 'getServices']);
        Route::post('/promocodes/validate', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\PromoCodeController::class, 'validateCode']);
        Route::get('/discount', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getDiscount']);

        Route::get('/referral', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'getReferralInfo']);
        Route::post('/referral/withdraw', [Pterodactyl\Http\Controllers\Api\Client\Rolexdev\Billing\BillingController::class, 'withdrawReferralBalance']);
    });
});



Route::group(['prefix' => '/addons/staff-request'], function () {
    Route::get('/requests', [Client\Servers\Rolexdev\StaffRequestController::class, 'index']);
    Route::get('/owner-requests', [Client\Servers\Rolexdev\StaffRequestController::class, 'ownerRequests']);
    Route::post('/requests', [Client\Servers\Rolexdev\StaffRequestController::class, 'store']);
    Route::post('/requests/{staffRequest}/accept', [Client\Servers\Rolexdev\StaffRequestController::class, 'accept']);
    Route::post('/requests/{staffRequest}/reject', [Client\Servers\Rolexdev\StaffRequestController::class, 'reject']);
    Route::delete('/requests/{staffRequest}', [Client\Servers\Rolexdev\StaffRequestController::class, 'destroy']);
    Route::post('/auto-reject', [Client\Servers\Rolexdev\StaffRequestController::class, 'autoReject']);
    Route::get('/servers', [Client\Servers\Rolexdev\StaffRequestController::class, 'searchServers']);
});

Route::get('/admin/users/search', [Client\AdminUserSearchController::class, 'search'])->withoutMiddleware(['client-api']);

Route::group(['prefix' => '/pwa'], function () {
    Route::get('/manifest.json', [Client\Theme\PwaController::class, 'manifest']);
    Route::get('/sw-config.js', [Client\Theme\PwaController::class, 'serviceWorkerConfig']);
})->withoutMiddleware(['client-api', RequireTwoFactorAuthentication::class]);

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function () {
    Route::get('/', [Client\Servers\ServerController::class, 'index'])->name('api:client:server.view');
    Route::get('/websocket', Client\Servers\WebsocketController::class)->name('api:client:server.ws');
    Route::get('/resources', Client\Servers\ResourceUtilizationController::class)->name('api:client:server.resources');
    Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');

    Route::get('/addons/template-installer/templates', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'listForServer']);
    Route::post('/addons/template-installer/install', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'install']);
    Route::get('/addons/template-installer/progress', [Client\Servers\Rolexdev\TemplateInstallerController::class, 'getProgress']);

    Route::get('/minecraft/player-count', [Client\Servers\Rolexdev\MinecraftController::class, 'getPlayerCount'])->name('api:client:server.minecraft.player-count');

    Route::group(['prefix' => '/minecraft'], function () {
        Route::get('/configuration', [Client\Servers\Rolexdev\MinecraftController::class, 'getConfiguration'])->name('api:client:server.minecraft.configuration');
        Route::get('/icon', [Client\Servers\Rolexdev\MinecraftController::class, 'getIcon'])->name('api:client:server.minecraft.icon.get');
        Route::post('/icon', [Client\Servers\Rolexdev\MinecraftController::class, 'uploadIcon'])->name('api:client:server.minecraft.icon.upload');
        Route::delete('/icon', [Client\Servers\Rolexdev\MinecraftController::class, 'deleteIcon'])->name('api:client:server.minecraft.icon.delete');
        Route::get('/motd', [Client\Servers\Rolexdev\MinecraftController::class, 'getMotd'])->name('api:client:server.minecraft.motd.get');
        Route::put('/motd', [Client\Servers\Rolexdev\MinecraftController::class, 'updateMotd'])->name('api:client:server.minecraft.motd.update');
        Route::get('/properties', [Client\Servers\Rolexdev\MinecraftController::class, 'getProperties'])->name('api:client:server.minecraft.properties.get');
        Route::put('/properties', [Client\Servers\Rolexdev\MinecraftController::class, 'updateProperties'])->name('api:client:server.minecraft.properties.update');
        Route::get('/config', [Client\Servers\Rolexdev\MinecraftController::class, 'getConfig'])->name('api:client:server.minecraft.config.get');
        Route::put('/config', [Client\Servers\Rolexdev\MinecraftController::class, 'updateConfig'])->name('api:client:server.minecraft.config.update');
        Route::get('/files', [Client\Servers\Rolexdev\MinecraftController::class, 'listYamlFiles'])->name('api:client:server.minecraft.files.get');
        Route::get('/yaml', [Client\Servers\Rolexdev\MinecraftController::class, 'getYamlFile'])->name('api:client:server.minecraft.yaml.get');
        Route::put('/yaml', [Client\Servers\Rolexdev\MinecraftController::class, 'updateYamlFile'])->name('api:client:server.minecraft.yaml.update');
        Route::get('/debug-scan', [Client\Servers\Rolexdev\MinecraftController::class, 'debugDirectoryScan'])->name('api:client:server.minecraft.debug-scan.get');
        
        Route::get('/version-changer/types', [Client\Servers\Rolexdev\MinecraftVersionController::class, 'getServerTypes'])->name('api:client:server.minecraft.version-changer.types');
        Route::get('/version-changer/versions/{type}', [Client\Servers\Rolexdev\MinecraftVersionController::class, 'getVersions'])->name('api:client:server.minecraft.version-changer.versions');
        Route::get('/version-changer/builds/{type}/{version}', [Client\Servers\Rolexdev\MinecraftVersionController::class, 'getBuilds'])->name('api:client:server.minecraft.version-changer.builds');
        Route::post('/version-changer/change', [Client\Servers\Rolexdev\MinecraftVersionController::class, 'changeVersion'])->name('api:client:server.minecraft.version-changer.change');
        Route::get('/version-changer/progress', [Client\Servers\Rolexdev\MinecraftVersionController::class, 'getProgress'])->name('api:client:server.minecraft.version-changer.progress');
        
        Route::get('/plugin-installer/installed', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'getInstalledPlugins'])->name('api:client:server.minecraft.plugin-installer.installed');
        Route::post('/plugin-installer/install', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'installPlugin'])->name('api:client:server.minecraft.plugin-installer.install');
        Route::delete('/plugin-installer/uninstall', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'uninstallPlugin'])->name('api:client:server.minecraft.plugin-installer.uninstall');
        Route::get('/plugin-installer/progress', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'getProgress'])->name('api:client:server.minecraft.plugin-installer.progress');
        Route::get('/plugin-installer/versions/{provider}/{pluginId}', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'getPluginVersions'])->name('api:client:server.minecraft.plugin-installer.versions');
        Route::get('/plugin-installer/details/{provider}/{pluginId}', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'getPluginDetails'])->name('api:client:server.minecraft.plugin-installer.details');
        Route::get('/plugin-installer/icon/{provider}/{iconPath}', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'getPluginIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.plugin-installer.icon');


        Route::get('/mod-installer/check-availability', [Client\Servers\Rolexdev\MinecraftModController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.mod-installer.check-availability');

        Route::get('/plugin-installer/cache', [Client\Servers\Rolexdev\MinecraftPluginCacheController::class, 'getCachedPlugins'])->name('api:client:server.minecraft.plugin-installer.cache.get');
        Route::post('/plugin-installer/cache', [Client\Servers\Rolexdev\MinecraftPluginCacheController::class, 'cachePluginData'])->name('api:client:server.minecraft.plugin-installer.cache.post');
        Route::delete('/plugin-installer/cache', [Client\Servers\Rolexdev\MinecraftPluginCacheController::class, 'clearCache'])->name('api:client:server.minecraft.plugin-installer.cache.clear');
        Route::get('/plugin-installer/cache/status', [Client\Servers\Rolexdev\MinecraftPluginCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.plugin-installer.cache.status');
        Route::get('/plugin-installer/game-versions', [Client\Servers\Rolexdev\MinecraftPluginCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.plugin-installer.game-versions');

        Route::get('/mod-installer/installed', [Client\Servers\Rolexdev\MinecraftModController::class, 'getInstalledMods'])->name('api:client:server.minecraft.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\Rolexdev\MinecraftModController::class, 'installMod'])->name('api:client:server.minecraft.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\Rolexdev\MinecraftModController::class, 'uninstallMod'])->name('api:client:server.minecraft.mod-installer.uninstall');
        Route::get('/mod-installer/progress', [Client\Servers\Rolexdev\MinecraftModController::class, 'getProgress'])->name('api:client:server.minecraft.mod-installer.progress');
        Route::get('/mod-installer/versions/{provider}/{modId}', [Client\Servers\Rolexdev\MinecraftModController::class, 'getModVersions'])->name('api:client:server.minecraft.mod-installer.versions');
        Route::get('/mod-installer/cache', [Client\Servers\Rolexdev\MinecraftModCacheController::class, 'getCachedMods'])->name('api:client:server.minecraft.mod-installer.cache.get');
        Route::post('/mod-installer/cache', [Client\Servers\Rolexdev\MinecraftModCacheController::class, 'cacheModData'])->name('api:client:server.minecraft.mod-installer.cache.post');
        Route::delete('/mod-installer/cache', [Client\Servers\Rolexdev\MinecraftModCacheController::class, 'clearCache'])->name('api:client:server.minecraft.mod-installer.cache.clear');
        Route::get('/mod-installer/cache/status', [Client\Servers\Rolexdev\MinecraftModCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.mod-installer.cache.status');
        Route::get('/mod-installer/game-versions', [Client\Servers\Rolexdev\MinecraftModCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.mod-installer.game-versions');

        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\Rolexdev\MinecraftModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.mod-installer.icon');

        Route::get('/modpack-installer/installed', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'getInstalledModpacks'])->name('api:client:server.minecraft.modpack-installer.installed');
        Route::post('/modpack-installer/install', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'installModpack'])->name('api:client:server.minecraft.modpack-installer.install');
        Route::delete('/modpack-installer/uninstall', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'uninstallModpack'])->name('api:client:server.minecraft.modpack-installer.uninstall');
        Route::get('/modpack-installer/progress', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'getProgress'])->name('api:client:server.minecraft.modpack-installer.progress');
        Route::get('/modpack-installer/versions/{provider}/{modpackId}', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'getModpackVersions'])->name('api:client:server.minecraft.modpack-installer.versions');
        Route::post('/modpack-installer/restore', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'restoreModpackServer'])->name('api:client:server.minecraft.modpack-installer.restore');
        Route::get('/modpack-installer/status', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'getModpackInstallStatus'])->name('api:client:server.minecraft.modpack-installer.status');
        Route::get('/modpack-installer/check-addon-availability', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.modpack-installer.check-addon-availability');
        Route::get('/modpack-installer/cache', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'getCachedModpacks'])->name('api:client:server.minecraft.modpack-installer.cache.get');
        Route::get('/modpack-installer/game-versions', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.modpack-installer.game-versions');
        Route::post('/modpack-installer/cache', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'cacheModpackData'])->name('api:client:server.minecraft.modpack-installer.cache.post');
        Route::get('/modpack-installer/modpack/{modpack}/versions', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'getModpackVersions'])->name('api:client:server.minecraft.modpack-installer.modpack.versions');
        Route::delete('/modpack-installer/cache', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'clearCache'])->name('api:client:server.minecraft.modpack-installer.cache.clear');
        Route::get('/modpack-installer/cache/status', [Client\Servers\Rolexdev\MinecraftModpackCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.modpack-installer.cache.status');

        Route::get('/modpack-installer/icon/{provider}/{iconPath}', [Client\Servers\Rolexdev\MinecraftModpackController::class, 'getModpackIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.modpack-installer.icon');

        Route::get('/world-manager/installed', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'getInstalledWorlds'])->name('api:client:server.minecraft.world-manager.installed');
        Route::post('/world-manager/install', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'installWorld'])->name('api:client:server.minecraft.world-manager.install');
        Route::delete('/world-manager/uninstall', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'uninstallWorld'])->name('api:client:server.minecraft.world-manager.uninstall');
        Route::get('/world-manager/progress', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'getProgress'])->name('api:client:server.minecraft.world-manager.progress');
        Route::get('/world-manager/inspect', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'inspectServer'])->name('api:client:server.minecraft.world-manager.inspect');

        Route::get('/world-manager/level-name', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'getLevelName'])->name('api:client:server.minecraft.world-manager.level-name.get');
        Route::post('/world-manager/level-name', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'updateLevelName'])->name('api:client:server.minecraft.world-manager.level-name.update');

        Route::get('/world-manager/cache', [Client\Servers\Rolexdev\MinecraftWorldCacheController::class, 'getCachedWorlds'])->name('api:client:server.minecraft.world-manager.cache.get');
        Route::post('/world-manager/cache', [Client\Servers\Rolexdev\MinecraftWorldCacheController::class, 'cacheWorldData'])->name('api:client:server.minecraft.world-manager.cache.post');
        Route::delete('/world-manager/cache', [Client\Servers\Rolexdev\MinecraftWorldCacheController::class, 'clearCache'])->name('api:client:server.minecraft.world-manager.cache.clear');
        Route::get('/world-manager/cache/status', [Client\Servers\Rolexdev\MinecraftWorldCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.world-manager.cache.status');
        Route::get('/world-manager/game-versions', [Client\Servers\Rolexdev\MinecraftWorldCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.world-manager.game-versions');
        
        Route::get('/world-manager/versions/{worldId}', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'getWorldVersions'])->name('api:client:server.minecraft.world-manager.versions');
        
        Route::get('/world-manager/icon/{avatarPath}', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'getWorldIcon'])
            ->where('avatarPath', '.*')
            ->name('api:client:server.minecraft.world-manager.icon');

        Route::get('/world-manager/check-addon-availability', [Client\Servers\Rolexdev\MinecraftWorldController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.world-manager.check-addon-availability');

        Route::get('/bedrock-addon-installer/installed', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'getInstalledAddons'])->name('api:client:server.minecraft.bedrock-addon-installer.installed');
        Route::post('/bedrock-addon-installer/install', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'installAddon'])->name('api:client:server.minecraft.bedrock-addon-installer.install');
        Route::delete('/bedrock-addon-installer/uninstall', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'uninstallAddon'])->name('api:client:server.minecraft.bedrock-addon-installer.uninstall');
        Route::get('/bedrock-addon-installer/progress', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'getProgress'])->name('api:client:server.minecraft.bedrock-addon-installer.progress');
        Route::get('/bedrock-addon-installer/versions/{addonId}', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'getAddonVersions'])->name('api:client:server.minecraft.bedrock-addon-installer.versions');
        Route::get('/bedrock-addon-installer/icon/{iconPath}', [Client\Servers\Rolexdev\MinecraftBedrockAddonController::class, 'getAddonIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.bedrock-addon-installer.icon');

        Route::get('/bedrock-addon-installer/cache', [Client\Servers\Rolexdev\MinecraftBedrockAddonCacheController::class, 'getCachedAddons'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.get');
        Route::post('/bedrock-addon-installer/cache', [Client\Servers\Rolexdev\MinecraftBedrockAddonCacheController::class, 'cacheAddonData'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.post');
        Route::delete('/bedrock-addon-installer/cache', [Client\Servers\Rolexdev\MinecraftBedrockAddonCacheController::class, 'clearCache'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.clear');
        Route::get('/bedrock-addon-installer/cache/status', [Client\Servers\Rolexdev\MinecraftBedrockAddonCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.status');

        Route::get('/bedrock-version-changer/versions', [Client\Servers\Rolexdev\MinecraftBedrockVersionController::class, 'getVersions'])->name('api:client:server.minecraft.bedrock-version-changer.versions');
        Route::get('/bedrock-version-changer/specific/{type}/{version}', [Client\Servers\Rolexdev\MinecraftBedrockVersionController::class, 'getSpecificVersions'])->name('api:client:server.minecraft.bedrock-version-changer.specific');
        Route::post('/bedrock-version-changer/change', [Client\Servers\Rolexdev\MinecraftBedrockVersionController::class, 'changeVersion'])->name('api:client:server.minecraft.bedrock-version-changer.change');
        Route::get('/bedrock-version-changer/progress', [Client\Servers\Rolexdev\MinecraftBedrockVersionController::class, 'getProgress'])->name('api:client:server.minecraft.bedrock-version-changer.progress');
        Route::group(['prefix' => '/player-manager'], function () {
            Route::get('/', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'index'])->name('api:client:server.minecraft.player-manager.index');
            Route::post('/fix-rcon', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'fixRcon'])->name('api:client:server.minecraft.player-manager.fix-rcon');
            Route::get('/details/{playerUuid}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'details'])->name('api:client:server.minecraft.player-manager.details');
            Route::post('/details/{playerUuid}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'saveDetails'])->name('api:client:server.minecraft.player-manager.save-details');
            Route::post('/icons', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'batchIcons'])->name('api.client.servers.rolexdev.minecraft.player-manager.icons-batch');
            Route::get('/icon/{item}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'icon'])->name('api.client.servers.rolexdev.minecraft.player-manager.icon');
            Route::get('/worlds', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'worlds'])->name('api:client:server.minecraft.player-manager.worlds');
            Route::post('/action', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'action'])->name('api:client:server.minecraft.player-manager.action');
            Route::post('/health/{player}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'setHealth'])->name('api:client:server.minecraft.player-manager.health');
            Route::post('/food/{player}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'setFood'])->name('api:client:server.minecraft.player-manager.food');
            Route::post('/experience/{player}', [Client\Servers\Rolexdev\MinecraftPlayerManagerController::class, 'setExperience'])->name('api:client:server.minecraft.player-manager.experience');
        });
    });

    Route::group(['prefix' => '/ark'], function () {
        Route::get('/mod-installer/server-info', [Client\Servers\Rolexdev\ArkModController::class, 'getServerInfo'])->name('api:client:server.ark.mod-installer.server-info');
        Route::get('/mod-installer/mod-ids', [Client\Servers\Rolexdev\ArkModController::class, 'getModIds'])->name('api:client:server.ark.mod-installer.mod-ids');
        Route::get('/mod-installer/installed', [Client\Servers\Rolexdev\ArkModController::class, 'getInstalledMods'])->name('api:client:server.ark.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\Rolexdev\ArkModController::class, 'installMod'])->name('api:client:server.ark.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\Rolexdev\ArkModController::class, 'uninstallMod'])->name('api:client:server.ark.mod-installer.uninstall');
        Route::get('/mod-installer/search', [Client\Servers\Rolexdev\ArkModController::class, 'searchMods'])->name('api:client:server.ark.mod-installer.search');
        Route::get('/mod-installer/versions/{modId}', [Client\Servers\Rolexdev\ArkModController::class, 'getModVersions'])->name('api:client:server.ark.mod-installer.versions');
        Route::get('/mod-installer/progress', [Client\Servers\Rolexdev\ArkModController::class, 'getProgress'])->name('api:client:server.ark.mod-installer.progress');
        Route::get('/mod-installer/check-availability', [Client\Servers\Rolexdev\ArkModController::class, 'checkAddonAvailability'])->name('api:client:server.ark.mod-installer.check-availability');

        Route::get('/mod-installer/cache', [Client\Servers\Rolexdev\ArkModCacheController::class, 'getCachedMods'])->name('api:client:server.ark.mod-installer.cache.get');
        Route::post('/mod-installer/cache', [Client\Servers\Rolexdev\ArkModCacheController::class, 'cacheModData'])->name('api:client:server.ark.mod-installer.cache.post');
        Route::delete('/mod-installer/cache', [Client\Servers\Rolexdev\ArkModCacheController::class, 'clearCache'])->name('api:client:server.ark.mod-installer.cache.clear');
        Route::get('/mod-installer/cache/status', [Client\Servers\Rolexdev\ArkModCacheController::class, 'getCacheStatus'])->name('api:client:server.ark.mod-installer.cache.status');

        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\Rolexdev\ArkModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.ark.mod-installer.icon');
    });

    Route::group(['prefix' => '/hytale'], function () {
        Route::get('/mod-installer/check-availability', [Client\Servers\Rolexdev\HytaleModController::class, 'checkAddonAvailability'])->name('api:client:server.hytale.mod-installer.check-availability');
        Route::get('/mod-installer/cache', [Client\Servers\Rolexdev\HytaleModController::class, 'getCachedMods'])->name('api:client:server.hytale.mod-installer.cache');
        Route::get('/mod-installer/installed', [Client\Servers\Rolexdev\HytaleModController::class, 'getInstalledMods'])->name('api:client:server.hytale.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\Rolexdev\HytaleModController::class, 'installMod'])->name('api:client:server.hytale.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\Rolexdev\HytaleModController::class, 'uninstallMod'])->name('api:client:server.hytale.mod-installer.uninstall');
        Route::get('/mod-installer/progress', [Client\Servers\Rolexdev\HytaleModController::class, 'getProgress'])->name('api:client:server.hytale.mod-installer.progress');
        Route::get('/mod-installer/versions/{provider}/{modId}', [Client\Servers\Rolexdev\HytaleModController::class, 'getModVersions'])->name('api:client:server.hytale.mod-installer.versions');
        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\Rolexdev\HytaleModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.hytale.mod-installer.icon');

        Route::get('/world-manager/installed', [Client\Servers\Rolexdev\HytaleWorldController::class, 'getInstalledWorlds'])->name('api:client:server.hytale.world-manager.installed');
        Route::post('/world-manager/install', [Client\Servers\Rolexdev\HytaleWorldController::class, 'installWorld'])->name('api:client:server.hytale.world-manager.install');
        Route::get('/world-manager/progress', [Client\Servers\Rolexdev\HytaleWorldController::class, 'getProgress'])->name('api:client:server.hytale.world-manager.progress');
        Route::get('/world-manager/versions/{worldId}', [Client\Servers\Rolexdev\HytaleWorldController::class, 'getWorldVersions'])->name('api:client:server.hytale.world-manager.versions');
        
        Route::get('/world-manager/icon/{avatarPath}', [Client\Servers\Rolexdev\HytaleWorldController::class, 'getWorldIcon'])
            ->where('avatarPath', '.*')
            ->name('api:client:server.hytale.world-manager.icon');
            
        Route::get('/world-manager/check-addon-availability', [Client\Servers\Rolexdev\HytaleWorldController::class, 'checkAddonAvailability'])->name('api:client:server.hytale.world-manager.check-availability');
        
        Route::get('/world-manager/inspect', [Client\Servers\Rolexdev\HytaleWorldController::class, 'inspectServer'])->name('api:client:server.hytale.world-manager.inspect');
        Route::delete('/world-manager/uninstall', [Client\Servers\Rolexdev\HytaleWorldController::class, 'uninstallWorld'])->name('api:client:server.hytale.world-manager.uninstall');

        Route::get('/world-manager/level-name', [Client\Servers\Rolexdev\HytaleWorldController::class, 'getLevelName'])->name('api:client:server.hytale.world-manager.level-name.get');
        Route::post('/world-manager/level-name', [Client\Servers\Rolexdev\HytaleWorldController::class, 'updateLevelName'])->name('api:client:server.hytale.world-manager.level-name.update');

        Route::get('/world-manager/cache', [Client\Servers\Rolexdev\HytaleWorldCacheController::class, 'getCachedWorlds'])->name('api:client:server.hytale.world-manager.cache.get');
        Route::post('/world-manager/cache', [Client\Servers\Rolexdev\HytaleWorldCacheController::class, 'cacheWorldData'])->name('api:client:server.hytale.world-manager.cache.post');
        Route::delete('/world-manager/cache', [Client\Servers\Rolexdev\HytaleWorldCacheController::class, 'clearCache'])->name('api:client:server.hytale.world-manager.cache.clear');
        Route::get('/world-manager/cache/status', [Client\Servers\Rolexdev\HytaleWorldCacheController::class, 'getCacheStatus'])->name('api:client:server.hytale.world-manager.cache.status');
    });

    Route::group(['prefix' => '/fivem-utils'], function () {
        Route::get('/components', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'getComponents']);
        Route::post('cache', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'clearCache']);
        Route::post('build', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'setGameBuild']);
        Route::post('txadmin', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'toggleTxAdmin']);
        Route::post('txadmin-port', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'setTxAdminPort']);
        Route::post('database', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'configureDatabase']);
        Route::post('artifact', [Client\Servers\Rolexdev\FiveMUtilsController::class, 'changeArtifact']);
    });

    Route::group(['prefix' => '/arma-reforger'], function () {
        Route::get('/mod-manager/test', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'test'])->name('api:client:server.arma-reforger.mod-manager.test');
        Route::get('/mod-manager/config', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getSelectedConfig'])->name('api:client:server.arma-reforger.mod-manager.config');
        Route::post('/mod-manager/dependencies', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'fetchDependencies'])->name('api:client:server.arma-reforger.mod-manager.dependencies');

        Route::get('/mod-manager/mods', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'fetchMods'])->name('api:client:server.arma-reforger.mod-manager.mods');
        Route::get('/mod-manager/mods/{modId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'fetchModById'])->name('api:client:server.arma-reforger.mod-manager.mod-details');
        Route::get('/mod-manager/mod/{modId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'fetchModById'])->name('api:client:server.arma-reforger.mod-manager.mod-details-alias');
        Route::get('/mod-manager/search', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'fetchMods'])->name('api:client:server.arma-reforger.mod-manager.search');

        Route::get('/mod-manager/installed', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getInstalledMods'])->name('api:client:server.arma-reforger.mod-manager.installed');
        Route::post('/mod-manager/install', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.install');
        Route::post('/mod-manager/uninstall', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.uninstall');

        Route::post('/mod-manager/batch-details', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'batchFetchModDetails'])->name('api:client:server.arma-reforger.mod-manager.batch-details');
        Route::post('/mod-manager/refresh-cache', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'refreshModCache'])->name('api:client:server.arma-reforger.mod-manager.refresh-cache');
        Route::post('/mod-manager/clear-dependency-cache', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'clearDependencyCache'])->name('api:client:server.arma-reforger.mod-manager.clear-dependency-cache');
        
        Route::get('/mod-manager/webhook-settings', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getWebhookSettings'])->name('api:client:server.arma-reforger.mod-manager.webhook-settings.get');
        Route::post('/mod-manager/webhook-settings', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'saveWebhookSettings'])->name('api:client:server.arma-reforger.mod-manager.webhook-settings.save');

        Route::get('/mod-manager/collections', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getCollections'])->name('api:client:server.arma-reforger.mod-manager.collections');
        Route::post('/mod-manager/collections', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'createCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.create');
        Route::put('/mod-manager/collections/{collectionId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'updateCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.update');
        Route::delete('/mod-manager/collections/{collectionId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'deleteCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.delete');
        Route::post('/mod-manager/collections/{collectionId}/apply', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'applyCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.apply');
        Route::post('/mod-manager/collections/{collectionId}/toggle-visibility', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'toggleCollectionVisibility'])->name('api:client:server.arma-reforger.mod-manager.collections.toggle-visibility');

        Route::get('/mod-manager/collections/{collectionId}/members', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getCollectionMembers'])->name('api:client:server.arma-reforger.mod-manager.collections.members.index');
        Route::post('/mod-manager/collections/{collectionId}/members', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'addCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.store');
        Route::delete('/mod-manager/collections/{collectionId}/members/{userId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'removeCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.destroy');
        Route::put('/mod-manager/collections/{collectionId}/members/{userId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'updateCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.update');

        Route::post('/mod-manager/update', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.update');
        Route::post('/mod-manager/bulk-update', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'bulkUpdateMods'])->name('api:client:server.arma-reforger.mod-manager.bulk-update');
        Route::post('/mod-manager/reorder', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'reorderMods'])->name('api:client:server.arma-reforger.mod-manager.reorder');
        Route::post('/mod-manager/send-to-discord', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'sendToDiscord'])->name('api:client:server.arma-reforger.mod-manager.send-to-discord');
        Route::get('/mod-manager/addons-size', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getAddonsSize'])->name('api:client:server.arma-reforger.mod-manager.addons-size');
        Route::get('/mod-manager/versions/{modId}', [Client\Servers\Rolexdev\ArmaReforgerModManagerController::class, 'getModVersions'])->name('api:client:server.arma-reforger.mod-manager.versions');

        Route::get('/config-editor', [Client\Servers\Rolexdev\ArmaReforgerConfigController::class, 'index'])->name('api:client:server.arma-reforger.config-editor.index');
        Route::post('/config-editor', [Client\Servers\Rolexdev\ArmaReforgerConfigController::class, 'save'])->name('api:client:server.arma-reforger.config-editor.save');
        Route::get('/config-editor/mods-scenarios', [Client\Servers\Rolexdev\ArmaReforgerConfigController::class, 'getModsWithScenarios'])->name('api:client:server.arma-reforger.config-editor.mods-scenarios');

        Route::group(['prefix' => '/admin-tools'], function () {
            Route::get('/test', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'test'])->name('api:client:server.arma-reforger.admin-tools.test');
            Route::get('/config', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'getToolsConfig'])->name('api:client:server.arma-reforger.admin-tools.config');
            Route::post('/config', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'updateConfig'])->name('api:client:server.arma-reforger.admin-tools.config.update');
            Route::post('/reset', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'resetConfig'])->name('api:client:server.arma-reforger.admin-tools.reset');
            Route::get('/priority-queue', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'getPriorityQueue'])->name('api:client:server.arma-reforger.admin-tools.priority-queue');
            Route::post('/priority-queue', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'updatePriorityQueue'])->name('api:client:server.arma-reforger.admin-tools.priority-queue.update');
            Route::get('/stats', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'getStats'])->name('api:client:server.arma-reforger.admin-tools.stats');
                Route::get('/player-count', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'getPlayerCount'])->name('api:client:server.arma-reforger.admin-tools.player-count');
            Route::get('/webhook-config', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'getWebhookConfig'])->name('api:client:server.arma-reforger.admin-tools.webhook-config');
            Route::post('/webhook-config', [Client\Servers\Rolexdev\ArmaReforgerAdminToolsController::class, 'updateWebhookConfig'])->name('api:client:server.arma-reforger.admin-tools.webhook-config.update');
        });
    });

    Route::get('/hyperv1-addon/check-server-availability', [Client\Servers\Rolexdev\MinecraftPluginController::class, 'checkAddonAvailability'])->name('api:client:server.hyperv1-addon.check-availability');

    Route::post('/command', [Client\Servers\CommandController::class, 'index']);
    Route::post('/power', [Client\Servers\PowerController::class, 'index']);

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', [Client\Servers\DatabaseController::class, 'index']);
        Route::post('/', [Client\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/rotate-password', [Client\Servers\DatabaseController::class, 'rotatePassword']);
        Route::delete('/{database}', [Client\Servers\DatabaseController::class, 'delete']);
        Route::delete('/{database}/clear', [Client\Servers\DatabaseController::class, 'clear']);
        Route::post('/{database}/import', [Client\Servers\DatabaseController::class, 'import']);
        Route::get('/{database}/export', [Client\Servers\DatabaseController::class, 'export']);
        Route::get('/{database}/download/{filename}', [Client\Servers\DatabaseController::class, 'download'])->name('api.client.servers.database.download');
        Route::post('/{database}/import-remote', [Client\Servers\DatabaseController::class, 'importFromRemote']);
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', [Client\Servers\FileController::class, 'directory']);
        Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
        Route::get('/download', [Client\Servers\FileController::class, 'download']);
        Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
        Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
        Route::post('/write', [Client\Servers\FileController::class, 'write']);
        Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
        Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
        Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
        Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
        Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
        Route::post('/pull', [Client\Servers\FileController::class, 'pull'])->middleware(['throttle:10,5']);
        Route::get('/upload', Client\Servers\FileUploadController::class);



        Route::group(['prefix' => '/recycle'], function () {
            Route::get('/', [Client\Servers\Rolexdev\RecycleBinController::class, 'index']);
            Route::get('/stats', [Client\Servers\Rolexdev\RecycleBinController::class, 'stats']);
            Route::post('/', [Client\Servers\Rolexdev\RecycleBinController::class, 'store']);
            Route::post('/restore', [Client\Servers\Rolexdev\RecycleBinController::class, 'restore']);
            Route::post('/restore/multiple', [Client\Servers\Rolexdev\RecycleBinController::class, 'restoreMultiple']);
            Route::delete('/permanent', [Client\Servers\Rolexdev\RecycleBinController::class, 'permanentDelete']);
            Route::delete('/empty', [Client\Servers\Rolexdev\RecycleBinController::class, 'empty']);
            Route::get('/{fileId}', [Client\Servers\Rolexdev\RecycleBinController::class, 'show']);
            Route::get('/{fileId}/preview', [Client\Servers\Rolexdev\RecycleBinController::class, 'preview']);
            Route::get('/{fileId}/download', [Client\Servers\Rolexdev\RecycleBinController::class, 'download']);
        });

        Route::group(['prefix' => '/subdomain-manager'], function () {
            Route::get('/', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'index']);
            Route::post('/', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'store']);
            Route::post('/check', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'checkAvailability']);
            Route::delete('/{id}', [Client\Servers\Rolexdev\SubdomainManagerController::class, 'destroy']);
        });

        Route::group(['prefix' => '/addons/auto-suspend'], function () {
            Route::get('/expiry', [Client\Servers\Rolexdev\AutoSuspendController::class, 'getExpiry']);
            Route::post('/expiry', [Client\Servers\Rolexdev\AutoSuspendController::class, 'setExpiry']);
            Route::delete('/expiry', [Client\Servers\Rolexdev\AutoSuspendController::class, 'removeExpiry']);
        });

        Route::group(['prefix' => '/quick-access'], function () {
            Route::get('/', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'index']);
            Route::post('/', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'store']);
            Route::post('/toggle', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'toggle']);
            Route::post('/check', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'check']);
            Route::post('/validate', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'validateItems']);
            Route::delete('/{id}', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'destroy']);
            Route::delete('/', [Client\Servers\Rolexdev\QuickFileAccessController::class, 'destroyByPath']);
        });
    });

    Route::group(['prefix' => '/addons/staff-request'], function () {
        Route::get('/requests', [Client\Servers\Rolexdev\StaffRequestController::class, 'serverRequests']);
        Route::post('/requests', [Client\Servers\Rolexdev\StaffRequestController::class, 'store']);
        Route::post('/requests/{staffRequest}/accept', [Client\Servers\Rolexdev\StaffRequestController::class, 'accept']);
        Route::post('/requests/{staffRequest}/reject', [Client\Servers\Rolexdev\StaffRequestController::class, 'reject']);
        Route::get('/search-servers', [Client\Servers\Rolexdev\StaffRequestController::class, 'searchServers']);
    });

    Route::group(['prefix' => '/addons/server-importer'], function () {
        Route::get('/imports', [Client\Servers\Rolexdev\ServerImporterController::class, 'index']);
        Route::post('/imports', [Client\Servers\Rolexdev\ServerImporterController::class, 'store']);
        Route::get('/imports/{import}', [Client\Servers\Rolexdev\ServerImporterController::class, 'show']);
        Route::patch('/imports/{import}', [Client\Servers\Rolexdev\ServerImporterController::class, 'update']);
        Route::delete('/imports/{import}', [Client\Servers\Rolexdev\ServerImporterController::class, 'destroy']);
        Route::post('/imports/{import}/import', [Client\Servers\Rolexdev\ServerImporterController::class, 'import']);
        Route::post('/restore', [Client\Servers\Rolexdev\ServerImporterController::class, 'restore']);
        Route::get('/status', [Client\Servers\Rolexdev\ServerImporterController::class, 'status']);
    });

    Route::group(['prefix' => '/addons/server-type-changer'], function () {
        Route::get('/nests', [Client\Servers\Rolexdev\ServerTypeChangerController::class, 'getNests']);
        Route::get('/current', [Client\Servers\Rolexdev\ServerTypeChangerController::class, 'getCurrentServerType']);
        Route::post('/change', [Client\Servers\Rolexdev\ServerTypeChangerController::class, 'changeServerType']);
        Route::get('/progress', [Client\Servers\Rolexdev\ServerTypeChangerController::class, 'getProgress']);
    });

    Route::group(['prefix' => '/addons/upload-from-url'], function () {
        Route::post('/upload', [Client\Servers\Rolexdev\UploadFromUrlController::class, 'upload']);
    });

    Route::group(['prefix' => '/addons/server-splitter'], function () {
        Route::get('/available-resources', [Client\Servers\Rolexdev\ServerSplitterController::class, 'availableResources']);
        Route::get('/splits', [Client\Servers\Rolexdev\ServerSplitterController::class, 'index']);
        Route::post('/splits', [Client\Servers\Rolexdev\ServerSplitterController::class, 'store']);
        Route::get('/splits/{split}', [Client\Servers\Rolexdev\ServerSplitterController::class, 'show']);
        Route::put('/splits/{split}', [Client\Servers\Rolexdev\ServerSplitterController::class, 'update']);
        Route::delete('/splits/{split}', [Client\Servers\Rolexdev\ServerSplitterController::class, 'destroy']);


    });

    Route::group(['prefix' => '/config-editor'], function () {
        Route::get('/files', [Client\Servers\Rolexdev\ConfigEditorController::class, 'getAvailableFiles'])->name('api:client:server.config-editor.files');
        Route::get('/content', [Client\Servers\Rolexdev\ConfigEditorController::class, 'getFileContent'])->name('api:client:server.config-editor.content.get');
        Route::put('/content', [Client\Servers\Rolexdev\ConfigEditorController::class, 'updateFileContent'])->name('api:client:server.config-editor.content.update');
    });


    Route::group(['prefix' => '/addons/startup-presets'], function () {
        Route::get('/presets', [Client\Servers\Rolexdev\StartupPresetsController::class, 'getPresets']);
        Route::post('/apply', [Client\Servers\Rolexdev\StartupPresetsController::class, 'applyPreset']);
        Route::put('/startup', [Client\Servers\Rolexdev\StartupPresetsController::class, 'updateStartup']);
    });

    Route::group(['prefix' => '/addons/schedule-presets'], function () {
        Route::post('/apply', [Client\Servers\Rolexdev\SchedulePresetsController::class, 'applyPreset']);
        Route::post('/import', [Client\Servers\Rolexdev\SchedulePresetsController::class, 'importSchedule']);
    });

    Route::group(['prefix' => '/addons/server-wiper'], function () {
        Route::get('/schedules', [Client\Servers\Rolexdev\ServerWiperController::class, 'getSchedules']);
        Route::post('/schedules', [Client\Servers\Rolexdev\ServerWiperController::class, 'createSchedule']);
        Route::put('/schedules/{scheduleId}', [Client\Servers\Rolexdev\ServerWiperController::class, 'updateSchedule']);
        Route::patch('/schedules/{scheduleId}/toggle', [Client\Servers\Rolexdev\ServerWiperController::class, 'toggleSchedule']);
        Route::delete('/schedules/{scheduleId}', [Client\Servers\Rolexdev\ServerWiperController::class, 'deleteSchedule']);
        Route::post('/schedules/{scheduleId}/execute', [Client\Servers\Rolexdev\ServerWiperController::class, 'executeNow']);
        Route::get('/history', [Client\Servers\Rolexdev\ServerWiperController::class, 'getHistory']);
        Route::get('/rust-maps', [Client\Servers\Rolexdev\ServerWiperController::class, 'getRustMaps']);
        Route::post('/rust-maps', [Client\Servers\Rolexdev\ServerWiperController::class, 'createRustMap']);
        Route::delete('/rust-maps/{mapId}', [Client\Servers\Rolexdev\ServerWiperController::class, 'deleteRustMap']);
    });

    Route::group(['prefix' => '/minecraft/votifier-tester'], function () {
        Route::post('/test', [Client\Servers\Rolexdev\MinecraftVotifierTesterController::class, 'test']);
    });

    Route::group(['prefix' => '/addons/reverse-proxy'], function () {
        Route::get('/', [Client\ReverseProxyController::class, 'index']);
        Route::post('/', [Client\ReverseProxyController::class, 'store']);
        Route::match(['put', 'patch'], '/{proxy}', [Client\ReverseProxyController::class, 'update']);
        Route::delete('/{proxy}', [Client\ReverseProxyController::class, 'delete']);
    });


    Route::group(['prefix' => '/rolexdev/command-history'], function () {
        Route::get('/', [Client\Servers\Rolexdev\CommandHistoryController::class, 'index'])->name('api:client:server.rolexdev.command-history.index');
        Route::post('/', [Client\Servers\Rolexdev\CommandHistoryController::class, 'store'])->name('api:client:server.rolexdev.command-history.store');
    });

    Route::group(['prefix' => '/addons/rolexdev/fastdl'], function () {
        Route::get('/', [Client\Servers\Rolexdev\FastDLController::class, 'index']);
        Route::post('/sync', [Client\Servers\Rolexdev\FastDLController::class, 'sync']);
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', [Client\Servers\ScheduleController::class, 'index']);
        Route::post('/', [Client\Servers\ScheduleController::class, 'store']);
        Route::get('/{schedule}', [Client\Servers\ScheduleController::class, 'view']);
        Route::post('/{schedule}', [Client\Servers\ScheduleController::class, 'update']);
        Route::post('/{schedule}/execute', [Client\Servers\ScheduleController::class, 'execute']);
        Route::get('/{schedule}/export', [Client\Servers\ScheduleController::class, 'export']);
        Route::delete('/{schedule}', [Client\Servers\ScheduleController::class, 'delete']);

        Route::post('/{schedule}/tasks', [Client\Servers\ScheduleTaskController::class, 'store']);
        Route::post('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'update']);
        Route::delete('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'delete']);
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', [Client\Servers\NetworkAllocationController::class, 'index']);
        Route::post('/allocations', [Client\Servers\NetworkAllocationController::class, 'store']);
        Route::post('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'update']);
        Route::post('/allocations/{allocation}/primary', [Client\Servers\NetworkAllocationController::class, 'setPrimary']);
        Route::delete('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'delete']);
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', [Client\Servers\SubuserController::class, 'index']);
        Route::post('/', [Client\Servers\SubuserController::class, 'store']);
        Route::get('/{user}', [Client\Servers\SubuserController::class, 'view']);
        Route::post('/{user}', [Client\Servers\SubuserController::class, 'update']);
        Route::delete('/{user}', [Client\Servers\SubuserController::class, 'delete']);
    });

    Route::get('/admin/users/search', [Client\AdminUserSearchController::class, 'search']);

    Route::group(['prefix' => '/backups'], function () {
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post('/', [Client\Servers\BackupController::class, 'store']);
        Route::get('/{backup}', [Client\Servers\BackupController::class, 'view']);
        Route::get('/{backup}/download', [Client\Servers\BackupController::class, 'download']);
        Route::post('/{backup}/lock', [Client\Servers\BackupController::class, 'toggleLock']);
        Route::post('/{backup}/restore', [Client\Servers\BackupController::class, 'restore']);
        Route::delete('/{backup}', [Client\Servers\BackupController::class, 'delete']);
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', [Client\Servers\StartupController::class, 'index']);
        Route::put('/variable', [Client\Servers\StartupController::class, 'update']);
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', [Client\Servers\SettingsController::class, 'rename']);
        Route::post('/reinstall', [Client\Servers\SettingsController::class, 'reinstall']);
        Route::put('/docker-image', [Client\Servers\SettingsController::class, 'dockerImage']);
    });



});

Route::group(['prefix' => '/addons/server-splitter'], function () {
    Route::get('/whitelist', [Client\Servers\Rolexdev\ServerSplitterWhitelistController::class, 'index']);
    Route::post('/whitelist', [Client\Servers\Rolexdev\ServerSplitterWhitelistController::class, 'store']);
    Route::put('/whitelist/{id}', [Client\Servers\Rolexdev\ServerSplitterWhitelistController::class, 'update']);
    Route::delete('/whitelist/{id}', [Client\Servers\Rolexdev\ServerSplitterWhitelistController::class, 'destroy']);
    Route::get('/search', [Client\Servers\Rolexdev\ServerSplitterWhitelistController::class, 'searchServers']);
});

