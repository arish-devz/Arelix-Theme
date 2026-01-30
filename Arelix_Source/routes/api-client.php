<?php

use Pterodactyl\Http\Controllers\Api\Client\Servers\arelix\FiveMUtilsController;

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Activity\AccountSubject;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Controllers\Api\Client\Servers\arelix\NodeStatusController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\arelix\CustomMonitorController;
use Pterodactyl\Http\Controllers\Api\Client\arelix\LoginActivityController;

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
        Route::get('/', [Client\arelix\LoginActivityController::class, 'index']);
        Route::post('/revoke/{sessionId}', [Client\arelix\LoginActivityController::class, 'revoke']);
    });
});

Route::group(['prefix' => '/theme'], function () {
    Route::get('/arelix', [Client\Theme\ArelixThemeController::class, 'show']);
    Route::put('/arelix', [Client\Theme\ArelixThemeController::class, 'update']);
    Route::get('/arelix/version', [Client\Theme\ArelixThemeController::class, 'checkVersion']);
    Route::post('/arelix/update', [Client\Theme\ArelixThemeController::class, 'startUpdate']);
    Route::get('/arelix/update/status', [Client\Theme\ArelixThemeController::class, 'getUpdateStatus']);
    Route::get('/arelix/sidebar', [Client\Theme\ArelixThemeController::class, 'getAvailableSidebarItems']);
});

Route::group(['prefix' => '/addons', 'middleware' => [\Pterodactyl\Http\Middleware\CompressResponse::class]], function () {
    Route::get('/defaults', [Client\Theme\ArelixAddonController::class, 'defaults']);
    Route::put('/', [Client\Theme\ArelixAddonController::class, 'update']);
    Route::get('/check-server-availability', [Client\Theme\ArelixAddonController::class, 'checkServerAvailability']);
    
    Route::post('/subdomain-manager/test-connection', [Client\Servers\arelix\SubdomainManagerController::class, 'testConnection']);
    Route::post('/subdomain-manager/fetch-domains', [Client\Servers\arelix\SubdomainManagerController::class, 'fetchDomains']);
    Route::get('/subdomain-manager/fetch-all-subdomains', [Client\Servers\arelix\SubdomainManagerController::class, 'fetchAllSubdomains']);
    Route::delete('/subdomain-manager/subdomains/{id}', [Client\Servers\arelix\SubdomainManagerController::class, 'deleteSubdomainAdmin']);

    Route::get('/template-installer/templates', [Client\Servers\arelix\TemplateInstallerController::class, 'index']);
    Route::post('/template-installer/templates', [Client\Servers\arelix\TemplateInstallerController::class, 'store']);
    Route::put('/template-installer/templates/{id}', [Client\Servers\arelix\TemplateInstallerController::class, 'update']);
    Route::delete('/template-installer/templates/{id}', [Client\Servers\arelix\TemplateInstallerController::class, 'destroy']);

    Route::group(['prefix' => '/discord-bot'], function () {
        Route::get('/stats', [Client\Admin\Arelix\DiscordBotController::class, 'stats']);
        Route::post('/sync', [Client\Admin\Arelix\DiscordBotController::class, 'triggerSync']);
        Route::get('/bot-status', [Client\Admin\Arelix\DiscordBotController::class, 'botStatus']);
        Route::post('/restart', [Client\Admin\Arelix\DiscordBotController::class, 'restartBot']);
    });

})->withoutMiddleware(['client-api']);

Route::get('admin/addons/server-type-changer/all-nests-eggs', [Pterodactyl\Http\Controllers\Api\Client\Servers\arelix\ServerTypeChangerController::class, 'getAllNestsAndEggs'])->withoutMiddleware(['client-api']);

Route::group(['prefix' => '/addons/server-importer'], function () {
    Route::post('/test-connection', [Client\Servers\arelix\ServerImporterController::class, 'testConnection']);
    Route::get('/imports', [Client\Servers\arelix\ServerImporterController::class, 'userImports']);
});

Route::group(['prefix' => '/addons/upload-from-url'], function () {
    Route::post('/query', [Client\Servers\arelix\UploadFromUrlController::class, 'query']);
});

Route::group(['prefix' => 'addons'], function () {
    Route::get('/node-status', [NodeStatusController::class, 'index']);
    Route::get('/node-status/monitors', [CustomMonitorController::class, 'index']);
    Route::post('/node-status/monitors', [CustomMonitorController::class, 'store']);
    Route::put('/node-status/monitors/{id}', [CustomMonitorController::class, 'update']);
    Route::delete('/node-status/monitors/{id}', [CustomMonitorController::class, 'destroy']);
    Route::get('/login-activity', [LoginActivityController::class, 'index']);
    Route::post('/login-activity/revoke', [LoginActivityController::class, 'revoke']);

    Route::post('/arelix/server-stats', [Pterodactyl\Http\Controllers\Api\Client\Servers\arelix\ServerStatsController::class, 'batch']);

    Route::group(['prefix' => '/billing'], function () {
        Route::get('/balance', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getBalance']);
        Route::post('/top-up', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'initiateTopUp']);
        Route::post('/verify', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'verifyTransaction']);
        Route::get('/transactions', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getTransactions']);
        
        Route::get('/admin/stats', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getAdminStats']);
        Route::get('/admin/transactions', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getAllTransactions']);
        Route::get('/admin/users-with-credits', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getUsersWithCredits']);
        
        Route::get('/store/categories', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\StoreController::class, 'index']);
        Route::get('/store/nodes', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\StoreController::class, 'getNodes']);
        Route::get('/store/categories/{shortUrl}', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\StoreController::class, 'showCategory']);
        
        Route::post('/order/create', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\OrderController::class, 'createServer']);
        Route::post('/order/renew/{serverUuid}', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\OrderController::class, 'renewServer']);
        Route::get('/services', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\OrderController::class, 'getServices']);
        Route::post('/promocodes/validate', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\PromoCodeController::class, 'validateCode']);
        Route::get('/discount', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getDiscount']);

        Route::get('/referral', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'getReferralInfo']);
        Route::post('/referral/withdraw', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\BillingController::class, 'withdrawReferralBalance']);
    });
});



Route::group(['prefix' => '/addons/staff-request'], function () {
    Route::get('/requests', [Client\Servers\arelix\StaffRequestController::class, 'index']);
    Route::get('/owner-requests', [Client\Servers\arelix\StaffRequestController::class, 'ownerRequests']);
    Route::post('/requests', [Client\Servers\arelix\StaffRequestController::class, 'store']);
    Route::post('/requests/{staffRequest}/accept', [Client\Servers\arelix\StaffRequestController::class, 'accept']);
    Route::post('/requests/{staffRequest}/reject', [Client\Servers\arelix\StaffRequestController::class, 'reject']);
    Route::delete('/requests/{staffRequest}', [Client\Servers\arelix\StaffRequestController::class, 'destroy']);
    Route::post('/auto-reject', [Client\Servers\arelix\StaffRequestController::class, 'autoReject']);
    Route::get('/servers', [Client\Servers\arelix\StaffRequestController::class, 'searchServers']);
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

    Route::get('/addons/template-installer/templates', [Client\Servers\arelix\TemplateInstallerController::class, 'listForServer']);
    Route::post('/addons/template-installer/install', [Client\Servers\arelix\TemplateInstallerController::class, 'install']);
    Route::get('/addons/template-installer/progress', [Client\Servers\arelix\TemplateInstallerController::class, 'getProgress']);

    Route::get('/minecraft/player-count', [Client\Servers\arelix\MinecraftController::class, 'getPlayerCount'])->name('api:client:server.minecraft.player-count');

    Route::group(['prefix' => '/minecraft'], function () {
        Route::get('/configuration', [Client\Servers\arelix\MinecraftController::class, 'getConfiguration'])->name('api:client:server.minecraft.configuration');
        Route::get('/icon', [Client\Servers\arelix\MinecraftController::class, 'getIcon'])->name('api:client:server.minecraft.icon.get');
        Route::post('/icon', [Client\Servers\arelix\MinecraftController::class, 'uploadIcon'])->name('api:client:server.minecraft.icon.upload');
        Route::delete('/icon', [Client\Servers\arelix\MinecraftController::class, 'deleteIcon'])->name('api:client:server.minecraft.icon.delete');
        Route::get('/motd', [Client\Servers\arelix\MinecraftController::class, 'getMotd'])->name('api:client:server.minecraft.motd.get');
        Route::put('/motd', [Client\Servers\arelix\MinecraftController::class, 'updateMotd'])->name('api:client:server.minecraft.motd.update');
        Route::get('/properties', [Client\Servers\arelix\MinecraftController::class, 'getProperties'])->name('api:client:server.minecraft.properties.get');
        Route::put('/properties', [Client\Servers\arelix\MinecraftController::class, 'updateProperties'])->name('api:client:server.minecraft.properties.update');
        Route::get('/config', [Client\Servers\arelix\MinecraftController::class, 'getConfig'])->name('api:client:server.minecraft.config.get');
        Route::put('/config', [Client\Servers\arelix\MinecraftController::class, 'updateConfig'])->name('api:client:server.minecraft.config.update');
        Route::get('/files', [Client\Servers\arelix\MinecraftController::class, 'listYamlFiles'])->name('api:client:server.minecraft.files.get');
        Route::get('/yaml', [Client\Servers\arelix\MinecraftController::class, 'getYamlFile'])->name('api:client:server.minecraft.yaml.get');
        Route::put('/yaml', [Client\Servers\arelix\MinecraftController::class, 'updateYamlFile'])->name('api:client:server.minecraft.yaml.update');
        Route::get('/debug-scan', [Client\Servers\arelix\MinecraftController::class, 'debugDirectoryScan'])->name('api:client:server.minecraft.debug-scan.get');
        
        Route::get('/version-changer/types', [Client\Servers\arelix\MinecraftVersionController::class, 'getServerTypes'])->name('api:client:server.minecraft.version-changer.types');
        Route::get('/version-changer/versions/{type}', [Client\Servers\arelix\MinecraftVersionController::class, 'getVersions'])->name('api:client:server.minecraft.version-changer.versions');
        Route::get('/version-changer/builds/{type}/{version}', [Client\Servers\arelix\MinecraftVersionController::class, 'getBuilds'])->name('api:client:server.minecraft.version-changer.builds');
        Route::post('/version-changer/change', [Client\Servers\arelix\MinecraftVersionController::class, 'changeVersion'])->name('api:client:server.minecraft.version-changer.change');
        Route::get('/version-changer/progress', [Client\Servers\arelix\MinecraftVersionController::class, 'getProgress'])->name('api:client:server.minecraft.version-changer.progress');
        
        Route::get('/plugin-installer/installed', [Client\Servers\arelix\MinecraftPluginController::class, 'getInstalledPlugins'])->name('api:client:server.minecraft.plugin-installer.installed');
        Route::post('/plugin-installer/install', [Client\Servers\arelix\MinecraftPluginController::class, 'installPlugin'])->name('api:client:server.minecraft.plugin-installer.install');
        Route::delete('/plugin-installer/uninstall', [Client\Servers\arelix\MinecraftPluginController::class, 'uninstallPlugin'])->name('api:client:server.minecraft.plugin-installer.uninstall');
        Route::get('/plugin-installer/progress', [Client\Servers\arelix\MinecraftPluginController::class, 'getProgress'])->name('api:client:server.minecraft.plugin-installer.progress');
        Route::get('/plugin-installer/versions/{provider}/{pluginId}', [Client\Servers\arelix\MinecraftPluginController::class, 'getPluginVersions'])->name('api:client:server.minecraft.plugin-installer.versions');
        Route::get('/plugin-installer/details/{provider}/{pluginId}', [Client\Servers\arelix\MinecraftPluginController::class, 'getPluginDetails'])->name('api:client:server.minecraft.plugin-installer.details');
        Route::get('/plugin-installer/icon/{provider}/{iconPath}', [Client\Servers\arelix\MinecraftPluginController::class, 'getPluginIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.plugin-installer.icon');


        Route::get('/mod-installer/check-availability', [Client\Servers\arelix\MinecraftModController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.mod-installer.check-availability');

        Route::get('/plugin-installer/cache', [Client\Servers\arelix\MinecraftPluginCacheController::class, 'getCachedPlugins'])->name('api:client:server.minecraft.plugin-installer.cache.get');
        Route::post('/plugin-installer/cache', [Client\Servers\arelix\MinecraftPluginCacheController::class, 'cachePluginData'])->name('api:client:server.minecraft.plugin-installer.cache.post');
        Route::delete('/plugin-installer/cache', [Client\Servers\arelix\MinecraftPluginCacheController::class, 'clearCache'])->name('api:client:server.minecraft.plugin-installer.cache.clear');
        Route::get('/plugin-installer/cache/status', [Client\Servers\arelix\MinecraftPluginCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.plugin-installer.cache.status');
        Route::get('/plugin-installer/game-versions', [Client\Servers\arelix\MinecraftPluginCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.plugin-installer.game-versions');

        Route::get('/mod-installer/installed', [Client\Servers\arelix\MinecraftModController::class, 'getInstalledMods'])->name('api:client:server.minecraft.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\arelix\MinecraftModController::class, 'installMod'])->name('api:client:server.minecraft.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\arelix\MinecraftModController::class, 'uninstallMod'])->name('api:client:server.minecraft.mod-installer.uninstall');
        Route::get('/mod-installer/progress', [Client\Servers\arelix\MinecraftModController::class, 'getProgress'])->name('api:client:server.minecraft.mod-installer.progress');
        Route::get('/mod-installer/versions/{provider}/{modId}', [Client\Servers\arelix\MinecraftModController::class, 'getModVersions'])->name('api:client:server.minecraft.mod-installer.versions');
        Route::get('/mod-installer/cache', [Client\Servers\arelix\MinecraftModCacheController::class, 'getCachedMods'])->name('api:client:server.minecraft.mod-installer.cache.get');
        Route::post('/mod-installer/cache', [Client\Servers\arelix\MinecraftModCacheController::class, 'cacheModData'])->name('api:client:server.minecraft.mod-installer.cache.post');
        Route::delete('/mod-installer/cache', [Client\Servers\arelix\MinecraftModCacheController::class, 'clearCache'])->name('api:client:server.minecraft.mod-installer.cache.clear');
        Route::get('/mod-installer/cache/status', [Client\Servers\arelix\MinecraftModCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.mod-installer.cache.status');
        Route::get('/mod-installer/game-versions', [Client\Servers\arelix\MinecraftModCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.mod-installer.game-versions');

        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\arelix\MinecraftModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.mod-installer.icon');

        Route::get('/modpack-installer/installed', [Client\Servers\arelix\MinecraftModpackController::class, 'getInstalledModpacks'])->name('api:client:server.minecraft.modpack-installer.installed');
        Route::post('/modpack-installer/install', [Client\Servers\arelix\MinecraftModpackController::class, 'installModpack'])->name('api:client:server.minecraft.modpack-installer.install');
        Route::delete('/modpack-installer/uninstall', [Client\Servers\arelix\MinecraftModpackController::class, 'uninstallModpack'])->name('api:client:server.minecraft.modpack-installer.uninstall');
        Route::get('/modpack-installer/progress', [Client\Servers\arelix\MinecraftModpackController::class, 'getProgress'])->name('api:client:server.minecraft.modpack-installer.progress');
        Route::get('/modpack-installer/versions/{provider}/{modpackId}', [Client\Servers\arelix\MinecraftModpackController::class, 'getModpackVersions'])->name('api:client:server.minecraft.modpack-installer.versions');
        Route::post('/modpack-installer/restore', [Client\Servers\arelix\MinecraftModpackController::class, 'restoreModpackServer'])->name('api:client:server.minecraft.modpack-installer.restore');
        Route::get('/modpack-installer/status', [Client\Servers\arelix\MinecraftModpackController::class, 'getModpackInstallStatus'])->name('api:client:server.minecraft.modpack-installer.status');
        Route::get('/modpack-installer/check-addon-availability', [Client\Servers\arelix\MinecraftModpackController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.modpack-installer.check-addon-availability');
        Route::get('/modpack-installer/cache', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'getCachedModpacks'])->name('api:client:server.minecraft.modpack-installer.cache.get');
        Route::get('/modpack-installer/game-versions', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.modpack-installer.game-versions');
        Route::post('/modpack-installer/cache', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'cacheModpackData'])->name('api:client:server.minecraft.modpack-installer.cache.post');
        Route::get('/modpack-installer/modpack/{modpack}/versions', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'getModpackVersions'])->name('api:client:server.minecraft.modpack-installer.modpack.versions');
        Route::delete('/modpack-installer/cache', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'clearCache'])->name('api:client:server.minecraft.modpack-installer.cache.clear');
        Route::get('/modpack-installer/cache/status', [Client\Servers\arelix\MinecraftModpackCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.modpack-installer.cache.status');

        Route::get('/modpack-installer/icon/{provider}/{iconPath}', [Client\Servers\arelix\MinecraftModpackController::class, 'getModpackIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.modpack-installer.icon');

        Route::get('/world-manager/installed', [Client\Servers\arelix\MinecraftWorldController::class, 'getInstalledWorlds'])->name('api:client:server.minecraft.world-manager.installed');
        Route::post('/world-manager/install', [Client\Servers\arelix\MinecraftWorldController::class, 'installWorld'])->name('api:client:server.minecraft.world-manager.install');
        Route::delete('/world-manager/uninstall', [Client\Servers\arelix\MinecraftWorldController::class, 'uninstallWorld'])->name('api:client:server.minecraft.world-manager.uninstall');
        Route::get('/world-manager/progress', [Client\Servers\arelix\MinecraftWorldController::class, 'getProgress'])->name('api:client:server.minecraft.world-manager.progress');
        Route::get('/world-manager/inspect', [Client\Servers\arelix\MinecraftWorldController::class, 'inspectServer'])->name('api:client:server.minecraft.world-manager.inspect');

        Route::get('/world-manager/level-name', [Client\Servers\arelix\MinecraftWorldController::class, 'getLevelName'])->name('api:client:server.minecraft.world-manager.level-name.get');
        Route::post('/world-manager/level-name', [Client\Servers\arelix\MinecraftWorldController::class, 'updateLevelName'])->name('api:client:server.minecraft.world-manager.level-name.update');

        Route::get('/world-manager/cache', [Client\Servers\arelix\MinecraftWorldCacheController::class, 'getCachedWorlds'])->name('api:client:server.minecraft.world-manager.cache.get');
        Route::post('/world-manager/cache', [Client\Servers\arelix\MinecraftWorldCacheController::class, 'cacheWorldData'])->name('api:client:server.minecraft.world-manager.cache.post');
        Route::delete('/world-manager/cache', [Client\Servers\arelix\MinecraftWorldCacheController::class, 'clearCache'])->name('api:client:server.minecraft.world-manager.cache.clear');
        Route::get('/world-manager/cache/status', [Client\Servers\arelix\MinecraftWorldCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.world-manager.cache.status');
        Route::get('/world-manager/game-versions', [Client\Servers\arelix\MinecraftWorldCacheController::class, 'getGameVersions'])->name('api:client:server.minecraft.world-manager.game-versions');
        
        Route::get('/world-manager/versions/{worldId}', [Client\Servers\arelix\MinecraftWorldController::class, 'getWorldVersions'])->name('api:client:server.minecraft.world-manager.versions');
        
        Route::get('/world-manager/icon/{avatarPath}', [Client\Servers\arelix\MinecraftWorldController::class, 'getWorldIcon'])
            ->where('avatarPath', '.*')
            ->name('api:client:server.minecraft.world-manager.icon');

        Route::get('/world-manager/check-addon-availability', [Client\Servers\arelix\MinecraftWorldController::class, 'checkAddonAvailability'])->name('api:client:server.minecraft.world-manager.check-addon-availability');

        Route::get('/bedrock-addon-installer/installed', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'getInstalledAddons'])->name('api:client:server.minecraft.bedrock-addon-installer.installed');
        Route::post('/bedrock-addon-installer/install', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'installAddon'])->name('api:client:server.minecraft.bedrock-addon-installer.install');
        Route::delete('/bedrock-addon-installer/uninstall', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'uninstallAddon'])->name('api:client:server.minecraft.bedrock-addon-installer.uninstall');
        Route::get('/bedrock-addon-installer/progress', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'getProgress'])->name('api:client:server.minecraft.bedrock-addon-installer.progress');
        Route::get('/bedrock-addon-installer/versions/{addonId}', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'getAddonVersions'])->name('api:client:server.minecraft.bedrock-addon-installer.versions');
        Route::get('/bedrock-addon-installer/icon/{iconPath}', [Client\Servers\arelix\MinecraftBedrockAddonController::class, 'getAddonIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.minecraft.bedrock-addon-installer.icon');

        Route::get('/bedrock-addon-installer/cache', [Client\Servers\arelix\MinecraftBedrockAddonCacheController::class, 'getCachedAddons'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.get');
        Route::post('/bedrock-addon-installer/cache', [Client\Servers\arelix\MinecraftBedrockAddonCacheController::class, 'cacheAddonData'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.post');
        Route::delete('/bedrock-addon-installer/cache', [Client\Servers\arelix\MinecraftBedrockAddonCacheController::class, 'clearCache'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.clear');
        Route::get('/bedrock-addon-installer/cache/status', [Client\Servers\arelix\MinecraftBedrockAddonCacheController::class, 'getCacheStatus'])->name('api:client:server.minecraft.bedrock-addon-installer.cache.status');

        Route::get('/bedrock-version-changer/versions', [Client\Servers\arelix\MinecraftBedrockVersionController::class, 'getVersions'])->name('api:client:server.minecraft.bedrock-version-changer.versions');
        Route::get('/bedrock-version-changer/specific/{type}/{version}', [Client\Servers\arelix\MinecraftBedrockVersionController::class, 'getSpecificVersions'])->name('api:client:server.minecraft.bedrock-version-changer.specific');
        Route::post('/bedrock-version-changer/change', [Client\Servers\arelix\MinecraftBedrockVersionController::class, 'changeVersion'])->name('api:client:server.minecraft.bedrock-version-changer.change');
        Route::get('/bedrock-version-changer/progress', [Client\Servers\arelix\MinecraftBedrockVersionController::class, 'getProgress'])->name('api:client:server.minecraft.bedrock-version-changer.progress');
        Route::group(['prefix' => '/player-manager'], function () {
            Route::get('/', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'index'])->name('api:client:server.minecraft.player-manager.index');
            Route::post('/fix-rcon', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'fixRcon'])->name('api:client:server.minecraft.player-manager.fix-rcon');
            Route::get('/details/{playerUuid}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'details'])->name('api:client:server.minecraft.player-manager.details');
            Route::post('/details/{playerUuid}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'saveDetails'])->name('api:client:server.minecraft.player-manager.save-details');
            Route::post('/icons', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'batchIcons'])->name('api.client.servers.arelix.minecraft.player-manager.icons-batch');
            Route::get('/icon/{item}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'icon'])->name('api.client.servers.arelix.minecraft.player-manager.icon');
            Route::get('/worlds', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'worlds'])->name('api:client:server.minecraft.player-manager.worlds');
            Route::post('/action', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'action'])->name('api:client:server.minecraft.player-manager.action');
            Route::post('/health/{player}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'setHealth'])->name('api:client:server.minecraft.player-manager.health');
            Route::post('/food/{player}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'setFood'])->name('api:client:server.minecraft.player-manager.food');
            Route::post('/experience/{player}', [Client\Servers\arelix\MinecraftPlayerManagerController::class, 'setExperience'])->name('api:client:server.minecraft.player-manager.experience');
        });
    });

    Route::group(['prefix' => '/ark'], function () {
        Route::get('/mod-installer/server-info', [Client\Servers\arelix\ArkModController::class, 'getServerInfo'])->name('api:client:server.ark.mod-installer.server-info');
        Route::get('/mod-installer/mod-ids', [Client\Servers\arelix\ArkModController::class, 'getModIds'])->name('api:client:server.ark.mod-installer.mod-ids');
        Route::get('/mod-installer/installed', [Client\Servers\arelix\ArkModController::class, 'getInstalledMods'])->name('api:client:server.ark.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\arelix\ArkModController::class, 'installMod'])->name('api:client:server.ark.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\arelix\ArkModController::class, 'uninstallMod'])->name('api:client:server.ark.mod-installer.uninstall');
        Route::get('/mod-installer/search', [Client\Servers\arelix\ArkModController::class, 'searchMods'])->name('api:client:server.ark.mod-installer.search');
        Route::get('/mod-installer/versions/{modId}', [Client\Servers\arelix\ArkModController::class, 'getModVersions'])->name('api:client:server.ark.mod-installer.versions');
        Route::get('/mod-installer/progress', [Client\Servers\arelix\ArkModController::class, 'getProgress'])->name('api:client:server.ark.mod-installer.progress');
        Route::get('/mod-installer/check-availability', [Client\Servers\arelix\ArkModController::class, 'checkAddonAvailability'])->name('api:client:server.ark.mod-installer.check-availability');

        Route::get('/mod-installer/cache', [Client\Servers\arelix\ArkModCacheController::class, 'getCachedMods'])->name('api:client:server.ark.mod-installer.cache.get');
        Route::post('/mod-installer/cache', [Client\Servers\arelix\ArkModCacheController::class, 'cacheModData'])->name('api:client:server.ark.mod-installer.cache.post');
        Route::delete('/mod-installer/cache', [Client\Servers\arelix\ArkModCacheController::class, 'clearCache'])->name('api:client:server.ark.mod-installer.cache.clear');
        Route::get('/mod-installer/cache/status', [Client\Servers\arelix\ArkModCacheController::class, 'getCacheStatus'])->name('api:client:server.ark.mod-installer.cache.status');

        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\arelix\ArkModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.ark.mod-installer.icon');
    });

    Route::group(['prefix' => '/hytale'], function () {
        Route::get('/mod-installer/check-availability', [Client\Servers\arelix\HytaleModController::class, 'checkAddonAvailability'])->name('api:client:server.hytale.mod-installer.check-availability');
        Route::get('/mod-installer/cache', [Client\Servers\arelix\HytaleModController::class, 'getCachedMods'])->name('api:client:server.hytale.mod-installer.cache');
        Route::get('/mod-installer/installed', [Client\Servers\arelix\HytaleModController::class, 'getInstalledMods'])->name('api:client:server.hytale.mod-installer.installed');
        Route::post('/mod-installer/install', [Client\Servers\arelix\HytaleModController::class, 'installMod'])->name('api:client:server.hytale.mod-installer.install');
        Route::delete('/mod-installer/uninstall', [Client\Servers\arelix\HytaleModController::class, 'uninstallMod'])->name('api:client:server.hytale.mod-installer.uninstall');
        Route::get('/mod-installer/progress', [Client\Servers\arelix\HytaleModController::class, 'getProgress'])->name('api:client:server.hytale.mod-installer.progress');
        Route::get('/mod-installer/versions/{provider}/{modId}', [Client\Servers\arelix\HytaleModController::class, 'getModVersions'])->name('api:client:server.hytale.mod-installer.versions');
        Route::get('/mod-installer/icon/{provider}/{iconPath}', [Client\Servers\arelix\HytaleModController::class, 'getModIcon'])
            ->where('iconPath', '.*')
            ->name('api:client:server.hytale.mod-installer.icon');

        Route::get('/world-manager/installed', [Client\Servers\arelix\HytaleWorldController::class, 'getInstalledWorlds'])->name('api:client:server.hytale.world-manager.installed');
        Route::post('/world-manager/install', [Client\Servers\arelix\HytaleWorldController::class, 'installWorld'])->name('api:client:server.hytale.world-manager.install');
        Route::get('/world-manager/progress', [Client\Servers\arelix\HytaleWorldController::class, 'getProgress'])->name('api:client:server.hytale.world-manager.progress');
        Route::get('/world-manager/versions/{worldId}', [Client\Servers\arelix\HytaleWorldController::class, 'getWorldVersions'])->name('api:client:server.hytale.world-manager.versions');
        
        Route::get('/world-manager/icon/{avatarPath}', [Client\Servers\arelix\HytaleWorldController::class, 'getWorldIcon'])
            ->where('avatarPath', '.*')
            ->name('api:client:server.hytale.world-manager.icon');
            
        Route::get('/world-manager/check-addon-availability', [Client\Servers\arelix\HytaleWorldController::class, 'checkAddonAvailability'])->name('api:client:server.hytale.world-manager.check-availability');
        
        Route::get('/world-manager/inspect', [Client\Servers\arelix\HytaleWorldController::class, 'inspectServer'])->name('api:client:server.hytale.world-manager.inspect');
        Route::delete('/world-manager/uninstall', [Client\Servers\arelix\HytaleWorldController::class, 'uninstallWorld'])->name('api:client:server.hytale.world-manager.uninstall');

        Route::get('/world-manager/level-name', [Client\Servers\arelix\HytaleWorldController::class, 'getLevelName'])->name('api:client:server.hytale.world-manager.level-name.get');
        Route::post('/world-manager/level-name', [Client\Servers\arelix\HytaleWorldController::class, 'updateLevelName'])->name('api:client:server.hytale.world-manager.level-name.update');

        Route::get('/world-manager/cache', [Client\Servers\arelix\HytaleWorldCacheController::class, 'getCachedWorlds'])->name('api:client:server.hytale.world-manager.cache.get');
        Route::post('/world-manager/cache', [Client\Servers\arelix\HytaleWorldCacheController::class, 'cacheWorldData'])->name('api:client:server.hytale.world-manager.cache.post');
        Route::delete('/world-manager/cache', [Client\Servers\arelix\HytaleWorldCacheController::class, 'clearCache'])->name('api:client:server.hytale.world-manager.cache.clear');
        Route::get('/world-manager/cache/status', [Client\Servers\arelix\HytaleWorldCacheController::class, 'getCacheStatus'])->name('api:client:server.hytale.world-manager.cache.status');
    });

    Route::group(['prefix' => '/fivem-utils'], function () {
        Route::get('/components', [Client\Servers\arelix\FiveMUtilsController::class, 'getComponents']);
        Route::post('cache', [Client\Servers\arelix\FiveMUtilsController::class, 'clearCache']);
        Route::post('build', [Client\Servers\arelix\FiveMUtilsController::class, 'setGameBuild']);
        Route::post('txadmin', [Client\Servers\arelix\FiveMUtilsController::class, 'toggleTxAdmin']);
        Route::post('txadmin-port', [Client\Servers\arelix\FiveMUtilsController::class, 'setTxAdminPort']);
        Route::post('database', [Client\Servers\arelix\FiveMUtilsController::class, 'configureDatabase']);
        Route::post('artifact', [Client\Servers\arelix\FiveMUtilsController::class, 'changeArtifact']);
    });

    Route::group(['prefix' => '/arma-reforger'], function () {
        Route::get('/mod-manager/test', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'test'])->name('api:client:server.arma-reforger.mod-manager.test');
        Route::get('/mod-manager/config', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getSelectedConfig'])->name('api:client:server.arma-reforger.mod-manager.config');
        Route::post('/mod-manager/dependencies', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'fetchDependencies'])->name('api:client:server.arma-reforger.mod-manager.dependencies');

        Route::get('/mod-manager/mods', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'fetchMods'])->name('api:client:server.arma-reforger.mod-manager.mods');
        Route::get('/mod-manager/mods/{modId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'fetchModById'])->name('api:client:server.arma-reforger.mod-manager.mod-details');
        Route::get('/mod-manager/mod/{modId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'fetchModById'])->name('api:client:server.arma-reforger.mod-manager.mod-details-alias');
        Route::get('/mod-manager/search', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'fetchMods'])->name('api:client:server.arma-reforger.mod-manager.search');

        Route::get('/mod-manager/installed', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getInstalledMods'])->name('api:client:server.arma-reforger.mod-manager.installed');
        Route::post('/mod-manager/install', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.install');
        Route::post('/mod-manager/uninstall', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.uninstall');

        Route::post('/mod-manager/batch-details', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'batchFetchModDetails'])->name('api:client:server.arma-reforger.mod-manager.batch-details');
        Route::post('/mod-manager/refresh-cache', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'refreshModCache'])->name('api:client:server.arma-reforger.mod-manager.refresh-cache');
        Route::post('/mod-manager/clear-dependency-cache', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'clearDependencyCache'])->name('api:client:server.arma-reforger.mod-manager.clear-dependency-cache');
        
        Route::get('/mod-manager/webhook-settings', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getWebhookSettings'])->name('api:client:server.arma-reforger.mod-manager.webhook-settings.get');
        Route::post('/mod-manager/webhook-settings', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'saveWebhookSettings'])->name('api:client:server.arma-reforger.mod-manager.webhook-settings.save');

        Route::get('/mod-manager/collections', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getCollections'])->name('api:client:server.arma-reforger.mod-manager.collections');
        Route::post('/mod-manager/collections', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'createCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.create');
        Route::put('/mod-manager/collections/{collectionId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'updateCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.update');
        Route::delete('/mod-manager/collections/{collectionId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'deleteCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.delete');
        Route::post('/mod-manager/collections/{collectionId}/apply', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'applyCollection'])->name('api:client:server.arma-reforger.mod-manager.collections.apply');
        Route::post('/mod-manager/collections/{collectionId}/toggle-visibility', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'toggleCollectionVisibility'])->name('api:client:server.arma-reforger.mod-manager.collections.toggle-visibility');

        Route::get('/mod-manager/collections/{collectionId}/members', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getCollectionMembers'])->name('api:client:server.arma-reforger.mod-manager.collections.members.index');
        Route::post('/mod-manager/collections/{collectionId}/members', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'addCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.store');
        Route::delete('/mod-manager/collections/{collectionId}/members/{userId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'removeCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.destroy');
        Route::put('/mod-manager/collections/{collectionId}/members/{userId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'updateCollectionMember'])->name('api:client:server.arma-reforger.mod-manager.collections.members.update');

        Route::post('/mod-manager/update', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'updateMod'])->name('api:client:server.arma-reforger.mod-manager.update');
        Route::post('/mod-manager/bulk-update', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'bulkUpdateMods'])->name('api:client:server.arma-reforger.mod-manager.bulk-update');
        Route::post('/mod-manager/reorder', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'reorderMods'])->name('api:client:server.arma-reforger.mod-manager.reorder');
        Route::post('/mod-manager/send-to-discord', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'sendToDiscord'])->name('api:client:server.arma-reforger.mod-manager.send-to-discord');
        Route::get('/mod-manager/addons-size', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getAddonsSize'])->name('api:client:server.arma-reforger.mod-manager.addons-size');
        Route::get('/mod-manager/versions/{modId}', [Client\Servers\arelix\ArmaReforgerModManagerController::class, 'getModVersions'])->name('api:client:server.arma-reforger.mod-manager.versions');

        Route::get('/config-editor', [Client\Servers\arelix\ArmaReforgerConfigController::class, 'index'])->name('api:client:server.arma-reforger.config-editor.index');
        Route::post('/config-editor', [Client\Servers\arelix\ArmaReforgerConfigController::class, 'save'])->name('api:client:server.arma-reforger.config-editor.save');
        Route::get('/config-editor/mods-scenarios', [Client\Servers\arelix\ArmaReforgerConfigController::class, 'getModsWithScenarios'])->name('api:client:server.arma-reforger.config-editor.mods-scenarios');

        Route::group(['prefix' => '/admin-tools'], function () {
            Route::get('/test', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'test'])->name('api:client:server.arma-reforger.admin-tools.test');
            Route::get('/config', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'getToolsConfig'])->name('api:client:server.arma-reforger.admin-tools.config');
            Route::post('/config', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'updateConfig'])->name('api:client:server.arma-reforger.admin-tools.config.update');
            Route::post('/reset', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'resetConfig'])->name('api:client:server.arma-reforger.admin-tools.reset');
            Route::get('/priority-queue', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'getPriorityQueue'])->name('api:client:server.arma-reforger.admin-tools.priority-queue');
            Route::post('/priority-queue', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'updatePriorityQueue'])->name('api:client:server.arma-reforger.admin-tools.priority-queue.update');
            Route::get('/stats', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'getStats'])->name('api:client:server.arma-reforger.admin-tools.stats');
                Route::get('/player-count', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'getPlayerCount'])->name('api:client:server.arma-reforger.admin-tools.player-count');
            Route::get('/webhook-config', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'getWebhookConfig'])->name('api:client:server.arma-reforger.admin-tools.webhook-config');
            Route::post('/webhook-config', [Client\Servers\arelix\ArmaReforgerAdminToolsController::class, 'updateWebhookConfig'])->name('api:client:server.arma-reforger.admin-tools.webhook-config.update');
        });
    });

    Route::get('/arelix-addon/check-server-availability', [Client\Servers\arelix\MinecraftPluginController::class, 'checkAddonAvailability'])->name('api:client:server.arelix-addon.check-availability');

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
            Route::get('/', [Client\Servers\arelix\RecycleBinController::class, 'index']);
            Route::get('/stats', [Client\Servers\arelix\RecycleBinController::class, 'stats']);
            Route::post('/', [Client\Servers\arelix\RecycleBinController::class, 'store']);
            Route::post('/restore', [Client\Servers\arelix\RecycleBinController::class, 'restore']);
            Route::post('/restore/multiple', [Client\Servers\arelix\RecycleBinController::class, 'restoreMultiple']);
            Route::delete('/permanent', [Client\Servers\arelix\RecycleBinController::class, 'permanentDelete']);
            Route::delete('/empty', [Client\Servers\arelix\RecycleBinController::class, 'empty']);
            Route::get('/{fileId}', [Client\Servers\arelix\RecycleBinController::class, 'show']);
            Route::get('/{fileId}/preview', [Client\Servers\arelix\RecycleBinController::class, 'preview']);
            Route::get('/{fileId}/download', [Client\Servers\arelix\RecycleBinController::class, 'download']);
        });

        Route::group(['prefix' => '/subdomain-manager'], function () {
            Route::get('/', [Client\Servers\arelix\SubdomainManagerController::class, 'index']);
            Route::post('/', [Client\Servers\arelix\SubdomainManagerController::class, 'store']);
            Route::post('/check', [Client\Servers\arelix\SubdomainManagerController::class, 'checkAvailability']);
            Route::delete('/{id}', [Client\Servers\arelix\SubdomainManagerController::class, 'destroy']);
        });

        Route::group(['prefix' => '/addons/auto-suspend'], function () {
            Route::get('/expiry', [Client\Servers\arelix\AutoSuspendController::class, 'getExpiry']);
            Route::post('/expiry', [Client\Servers\arelix\AutoSuspendController::class, 'setExpiry']);
            Route::delete('/expiry', [Client\Servers\arelix\AutoSuspendController::class, 'removeExpiry']);
        });

        Route::group(['prefix' => '/quick-access'], function () {
            Route::get('/', [Client\Servers\arelix\QuickFileAccessController::class, 'index']);
            Route::post('/', [Client\Servers\arelix\QuickFileAccessController::class, 'store']);
            Route::post('/toggle', [Client\Servers\arelix\QuickFileAccessController::class, 'toggle']);
            Route::post('/check', [Client\Servers\arelix\QuickFileAccessController::class, 'check']);
            Route::post('/validate', [Client\Servers\arelix\QuickFileAccessController::class, 'validateItems']);
            Route::delete('/{id}', [Client\Servers\arelix\QuickFileAccessController::class, 'destroy']);
            Route::delete('/', [Client\Servers\arelix\QuickFileAccessController::class, 'destroyByPath']);
        });
    });

    Route::group(['prefix' => '/addons/staff-request'], function () {
        Route::get('/requests', [Client\Servers\arelix\StaffRequestController::class, 'serverRequests']);
        Route::post('/requests', [Client\Servers\arelix\StaffRequestController::class, 'store']);
        Route::post('/requests/{staffRequest}/accept', [Client\Servers\arelix\StaffRequestController::class, 'accept']);
        Route::post('/requests/{staffRequest}/reject', [Client\Servers\arelix\StaffRequestController::class, 'reject']);
        Route::get('/search-servers', [Client\Servers\arelix\StaffRequestController::class, 'searchServers']);
    });

    Route::group(['prefix' => '/addons/server-importer'], function () {
        Route::get('/imports', [Client\Servers\arelix\ServerImporterController::class, 'index']);
        Route::post('/imports', [Client\Servers\arelix\ServerImporterController::class, 'store']);
        Route::get('/imports/{import}', [Client\Servers\arelix\ServerImporterController::class, 'show']);
        Route::patch('/imports/{import}', [Client\Servers\arelix\ServerImporterController::class, 'update']);
        Route::delete('/imports/{import}', [Client\Servers\arelix\ServerImporterController::class, 'destroy']);
        Route::post('/imports/{import}/import', [Client\Servers\arelix\ServerImporterController::class, 'import']);
        Route::post('/restore', [Client\Servers\arelix\ServerImporterController::class, 'restore']);
        Route::get('/status', [Client\Servers\arelix\ServerImporterController::class, 'status']);
    });

    Route::group(['prefix' => '/addons/server-type-changer'], function () {
        Route::get('/nests', [Client\Servers\arelix\ServerTypeChangerController::class, 'getNests']);
        Route::get('/current', [Client\Servers\arelix\ServerTypeChangerController::class, 'getCurrentServerType']);
        Route::post('/change', [Client\Servers\arelix\ServerTypeChangerController::class, 'changeServerType']);
        Route::get('/progress', [Client\Servers\arelix\ServerTypeChangerController::class, 'getProgress']);
    });

    Route::group(['prefix' => '/addons/upload-from-url'], function () {
        Route::post('/upload', [Client\Servers\arelix\UploadFromUrlController::class, 'upload']);
    });

    Route::group(['prefix' => '/addons/server-splitter'], function () {
        Route::get('/available-resources', [Client\Servers\arelix\ServerSplitterController::class, 'availableResources']);
        Route::get('/splits', [Client\Servers\arelix\ServerSplitterController::class, 'index']);
        Route::post('/splits', [Client\Servers\arelix\ServerSplitterController::class, 'store']);
        Route::get('/splits/{split}', [Client\Servers\arelix\ServerSplitterController::class, 'show']);
        Route::put('/splits/{split}', [Client\Servers\arelix\ServerSplitterController::class, 'update']);
        Route::delete('/splits/{split}', [Client\Servers\arelix\ServerSplitterController::class, 'destroy']);


    });

    Route::group(['prefix' => '/config-editor'], function () {
        Route::get('/files', [Client\Servers\arelix\ConfigEditorController::class, 'getAvailableFiles'])->name('api:client:server.config-editor.files');
        Route::get('/content', [Client\Servers\arelix\ConfigEditorController::class, 'getFileContent'])->name('api:client:server.config-editor.content.get');
        Route::put('/content', [Client\Servers\arelix\ConfigEditorController::class, 'updateFileContent'])->name('api:client:server.config-editor.content.update');
    });


    Route::group(['prefix' => '/addons/startup-presets'], function () {
        Route::get('/presets', [Client\Servers\arelix\StartupPresetsController::class, 'getPresets']);
        Route::post('/apply', [Client\Servers\arelix\StartupPresetsController::class, 'applyPreset']);
        Route::put('/startup', [Client\Servers\arelix\StartupPresetsController::class, 'updateStartup']);
    });

    Route::group(['prefix' => '/addons/schedule-presets'], function () {
        Route::post('/apply', [Client\Servers\arelix\SchedulePresetsController::class, 'applyPreset']);
        Route::post('/import', [Client\Servers\arelix\SchedulePresetsController::class, 'importSchedule']);
    });

    Route::group(['prefix' => '/addons/server-wiper'], function () {
        Route::get('/schedules', [Client\Servers\arelix\ServerWiperController::class, 'getSchedules']);
        Route::post('/schedules', [Client\Servers\arelix\ServerWiperController::class, 'createSchedule']);
        Route::put('/schedules/{scheduleId}', [Client\Servers\arelix\ServerWiperController::class, 'updateSchedule']);
        Route::patch('/schedules/{scheduleId}/toggle', [Client\Servers\arelix\ServerWiperController::class, 'toggleSchedule']);
        Route::delete('/schedules/{scheduleId}', [Client\Servers\arelix\ServerWiperController::class, 'deleteSchedule']);
        Route::post('/schedules/{scheduleId}/execute', [Client\Servers\arelix\ServerWiperController::class, 'executeNow']);
        Route::get('/history', [Client\Servers\arelix\ServerWiperController::class, 'getHistory']);
        Route::get('/rust-maps', [Client\Servers\arelix\ServerWiperController::class, 'getRustMaps']);
        Route::post('/rust-maps', [Client\Servers\arelix\ServerWiperController::class, 'createRustMap']);
        Route::delete('/rust-maps/{mapId}', [Client\Servers\arelix\ServerWiperController::class, 'deleteRustMap']);
    });

    Route::group(['prefix' => '/minecraft/votifier-tester'], function () {
        Route::post('/test', [Client\Servers\arelix\MinecraftVotifierTesterController::class, 'test']);
    });

    Route::group(['prefix' => '/addons/reverse-proxy'], function () {
        Route::get('/', [Client\ReverseProxyController::class, 'index']);
        Route::post('/', [Client\ReverseProxyController::class, 'store']);
        Route::match(['put', 'patch'], '/{proxy}', [Client\ReverseProxyController::class, 'update']);
        Route::delete('/{proxy}', [Client\ReverseProxyController::class, 'delete']);
    });


    Route::group(['prefix' => '/arelix/command-history'], function () {
        Route::get('/', [Client\Servers\arelix\CommandHistoryController::class, 'index'])->name('api:client:server.arelix.command-history.index');
        Route::post('/', [Client\Servers\arelix\CommandHistoryController::class, 'store'])->name('api:client:server.arelix.command-history.store');
    });

    Route::group(['prefix' => '/addons/arelix/fastdl'], function () {
        Route::get('/', [Client\Servers\arelix\FastDLController::class, 'index']);
        Route::post('/sync', [Client\Servers\arelix\FastDLController::class, 'sync']);
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
    Route::get('/whitelist', [Client\Servers\arelix\ServerSplitterWhitelistController::class, 'index']);
    Route::post('/whitelist', [Client\Servers\arelix\ServerSplitterWhitelistController::class, 'store']);
    Route::put('/whitelist/{id}', [Client\Servers\arelix\ServerSplitterWhitelistController::class, 'update']);
    Route::delete('/whitelist/{id}', [Client\Servers\arelix\ServerSplitterWhitelistController::class, 'destroy']);
    Route::get('/search', [Client\Servers\arelix\ServerSplitterWhitelistController::class, 'searchServers']);
});

