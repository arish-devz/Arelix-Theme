<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
    Route::get('/', [Admin\ApiController::class, 'index'])->name('admin.api.index');
    Route::get('/new', [Admin\ApiController::class, 'create'])->name('admin.api.new');

    Route::post('/new', [Admin\ApiController::class, 'store'])->name('admin.api.store');

    Route::delete('/revoke/{identifier}', [Admin\ApiController::class, 'delete'])->name('admin.api.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [Admin\LocationController::class, 'index'])->name('admin.locations');
    Route::get('/view/{location:id}', [Admin\LocationController::class, 'view'])->name('admin.locations.view');

    Route::post('/', [Admin\LocationController::class, 'create'])->name('admin.locations.store');
    Route::patch('/view/{location:id}', [Admin\LocationController::class, 'update'])->name('admin.locations.update');
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', [Admin\DatabaseController::class, 'index'])->name('admin.databases');
    Route::get('/view/{host:id}', [Admin\DatabaseController::class, 'view'])->name('admin.databases.view');

    Route::post('/', [Admin\DatabaseController::class, 'create'])->name('admin.databases.store');
    Route::patch('/view/{host:id}', [Admin\DatabaseController::class, 'update'])->name('admin.databases.update');
    Route::delete('/view/{host:id}', [Admin\DatabaseController::class, 'delete'])->name('admin.databases.delete');
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/', [Admin\Settings\IndexController::class, 'index'])->name('admin.settings');
    Route::get('/mail', [Admin\Settings\MailController::class, 'index'])->name('admin.settings.mail');
    Route::get('/advanced', [Admin\Settings\AdvancedController::class, 'index'])->name('admin.settings.advanced');

    Route::post('/mail/test', [Admin\Settings\MailController::class, 'test'])->name('admin.settings.mail.test');

    Route::patch('/', [Admin\Settings\IndexController::class, 'update'])->name('admin.settings.update');
    Route::patch('/mail', [Admin\Settings\MailController::class, 'update'])->name('admin.settings.mail.update');
    Route::patch('/advanced', [Admin\Settings\AdvancedController::class, 'update'])->name('admin.settings.advanced.update');
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [Admin\UserController::class, 'index'])->name('admin.users');
    Route::get('/accounts.json', [Admin\UserController::class, 'json'])->name('admin.users.json');
    Route::get('/new', [Admin\UserController::class, 'create'])->name('admin.users.new');
    Route::get('/view/{user:id}', [Admin\UserController::class, 'view'])->name('admin.users.view');

    Route::post('/new', [Admin\UserController::class, 'store'])->name('admin.users.store');

    Route::patch('/view/{user:id}', [Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/view/{user:id}', [Admin\UserController::class, 'delete'])->name('admin.users.delete');
    Route::delete('/view/{user:id}/session/{session}', [Admin\UserController::class, 'revokeSession'])->name('admin.users.session.revoke');
    Route::delete('/view/{user:id}/sessions', [Admin\UserController::class, 'revokeAllSessions'])->name('admin.users.session.revoke_all');
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', [Admin\Servers\ServerController::class, 'index'])->name('admin.servers');
    Route::get('/new', [Admin\Servers\CreateServerController::class, 'index'])->name('admin.servers.new');
    Route::get('/view/{server:id}', [Admin\Servers\ServerViewController::class, 'index'])->name('admin.servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server:id}/details', [Admin\Servers\ServerViewController::class, 'details'])->name('admin.servers.view.details');
        Route::get('/view/{server:id}/build', [Admin\Servers\ServerViewController::class, 'build'])->name('admin.servers.view.build');
        Route::get('/view/{server:id}/startup', [Admin\Servers\ServerViewController::class, 'startup'])->name('admin.servers.view.startup');
        Route::get('/view/{server:id}/database', [Admin\Servers\ServerViewController::class, 'database'])->name('admin.servers.view.database');
        Route::get('/view/{server:id}/mounts', [Admin\Servers\ServerViewController::class, 'mounts'])->name('admin.servers.view.mounts');
    });

    Route::get('/view/{server:id}/manage', [Admin\Servers\ServerViewController::class, 'manage'])->name('admin.servers.view.manage');
    Route::get('/view/{server:id}/delete', [Admin\Servers\ServerViewController::class, 'delete'])->name('admin.servers.view.delete');

    Route::post('/new', [Admin\Servers\CreateServerController::class, 'store'])->name('admin.servers.store');
    Route::post('/view/{server:id}/build', [Admin\ServersController::class, 'updateBuild'])->name('admin.servers.view.build.update');
    Route::post('/view/{server:id}/startup', [Admin\ServersController::class, 'saveStartup'])->name('admin.servers.view.startup.update');
    Route::post('/view/{server:id}/database', [Admin\ServersController::class, 'newDatabase'])->name('admin.servers.view.database.store');
    Route::post('/view/{server:id}/mounts', [Admin\ServersController::class, 'addMount'])->name('admin.servers.view.mounts.store');
    Route::post('/view/{server:id}/manage/toggle', [Admin\ServersController::class, 'toggleInstall'])->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server:id}/manage/suspension', [Admin\ServersController::class, 'manageSuspension'])->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server:id}/manage/reinstall', [Admin\ServersController::class, 'reinstallServer'])->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server:id}/manage/transfer', [Admin\Servers\ServerTransferController::class, 'transfer'])->name('admin.servers.view.manage.transfer');
    Route::post('/view/{server:id}/delete', [Admin\ServersController::class, 'delete'])->name('admin.servers.view.delete.post');

    Route::patch('/view/{server:id}/details', [Admin\ServersController::class, 'setDetails'])->name('admin.servers.view.details.update');
    Route::patch('/view/{server:id}/database', [Admin\ServersController::class, 'resetDatabasePassword'])->name('admin.servers.view.database.update');

    Route::delete('/view/{server:id}/database/{database:id}/delete', [Admin\ServersController::class, 'deleteDatabase'])->name('admin.servers.view.database.delete');
    Route::delete('/view/{server:id}/mounts/{mount:id}', [Admin\ServersController::class, 'deleteMount'])
        ->name('admin.servers.view.mounts.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes'], function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index'])->name('admin.nodes');
    Route::get('/new', [Admin\NodesController::class, 'create'])->name('admin.nodes.new');
    Route::get('/view/{node:id}', [Admin\Nodes\NodeViewController::class, 'index'])->name('admin.nodes.view');
    Route::get('/view/{node:id}/settings', [Admin\Nodes\NodeViewController::class, 'settings'])->name('admin.nodes.view.settings');
    Route::get('/view/{node:id}/configuration', [Admin\Nodes\NodeViewController::class, 'configuration'])->name('admin.nodes.view.configuration');
    Route::get('/view/{node:id}/allocation', [Admin\Nodes\NodeViewController::class, 'allocations'])->name('admin.nodes.view.allocation');
    Route::get('/view/{node:id}/servers', [Admin\Nodes\NodeViewController::class, 'servers'])->name('admin.nodes.view.servers');
    Route::get('/view/{node:id}/system-information', Admin\Nodes\SystemInformationController::class)->name('admin.nodes.view.system-information');

    Route::post('/new', [Admin\NodesController::class, 'store'])->name('admin.nodes.store');
    Route::post('/view/{node:id}/allocation', [Admin\NodesController::class, 'createAllocation'])->name('admin.nodes.view.allocation.store');
    Route::post('/view/{node:id}/allocation/remove', [Admin\NodesController::class, 'allocationRemoveBlock'])->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node:id}/allocation/alias', [Admin\NodesController::class, 'allocationSetAlias'])->name('admin.nodes.view.allocation.setAlias');
    Route::post('/view/{node:id}/settings/token', Admin\NodeAutoDeployController::class)->name('admin.nodes.view.configuration.token');

    Route::patch('/view/{node:id}/settings', [Admin\NodesController::class, 'updateSettings'])->name('admin.nodes.view.settings.update');

    Route::delete('/view/{node:id}/delete', [Admin\NodesController::class, 'delete'])->name('admin.nodes.view.delete');
    Route::delete('/view/{node:id}/allocation/remove/{allocation:id}', [Admin\NodesController::class, 'allocationRemoveSingle'])->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node:id}/allocations', [Admin\NodesController::class, 'allocationRemoveMultiple'])->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts'], function () {
    Route::get('/', [Admin\MountController::class, 'index'])->name('admin.mounts');
    Route::get('/view/{mount:id}', [Admin\MountController::class, 'view'])->name('admin.mounts.view');

    Route::post('/', [Admin\MountController::class, 'create'])->name('admin.mounts.store');
    Route::post('/{mount:id}/eggs', [Admin\MountController::class, 'addEggs'])->name('admin.mounts.eggs');
    Route::post('/{mount:id}/nodes', [Admin\MountController::class, 'addNodes'])->name('admin.mounts.nodes');

    Route::patch('/view/{mount:id}', [Admin\MountController::class, 'update'])->name('admin.mounts.update');

    Route::delete('/{mount:id}/eggs/{egg_id}', [Admin\MountController::class, 'deleteEgg'])->name('admin.mounts.eggs.delete');
    Route::delete('/{mount:id}/nodes/{node_id}', [Admin\MountController::class, 'deleteNode'])->name('admin.mounts.nodes.delete');
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests'], function () {
    Route::get('/', [Admin\Nests\NestController::class, 'index'])->name('admin.nests');
    Route::get('/new', [Admin\Nests\NestController::class, 'create'])->name('admin.nests.new');
    Route::get('/view/{nest:id}', [Admin\Nests\NestController::class, 'view'])->name('admin.nests.view');
    Route::get('/egg/new', [Admin\Nests\EggController::class, 'create'])->name('admin.nests.egg.new');
    Route::get('/egg/{egg:id}', [Admin\Nests\EggController::class, 'view'])->name('admin.nests.egg.view');
    Route::get('/egg/{egg:id}/export', [Admin\Nests\EggShareController::class, 'export'])->name('admin.nests.egg.export');
    Route::get('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'view'])->name('admin.nests.egg.variables');
    Route::get('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'index'])->name('admin.nests.egg.scripts');

    Route::post('/new', [Admin\Nests\NestController::class, 'store'])->name('admin.nests.store');
    Route::post('/import', [Admin\Nests\EggShareController::class, 'import'])->name('admin.nests.egg.import');
    Route::post('/egg/new', [Admin\Nests\EggController::class, 'store'])->name('admin.nests.egg.store');
    Route::post('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'store'])->name('admin.nests.egg.variables.store');

    Route::put('/egg/{egg:id}', [Admin\Nests\EggShareController::class, 'update'])->name('admin.nests.egg.share.update');

    Route::patch('/view/{nest:id}', [Admin\Nests\NestController::class, 'update'])->name('admin.nests.update');
    Route::patch('/egg/{egg:id}', [Admin\Nests\EggController::class, 'update'])->name('admin.nests.egg.update');
    Route::patch('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'update'])->name('admin.nests.egg.scripts.update');
    Route::patch('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'update'])->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest:id}', [Admin\Nests\NestController::class, 'destroy'])->name('admin.nests.delete');
    Route::delete('/egg/{egg:id}', [Admin\Nests\EggController::class, 'destroy'])->name('admin.nests.egg.delete');
    Route::delete('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'destroy'])->name('admin.nests.egg.variables.delete');
});

/*
|--------------------------------------------------------------------------
| Arelix Addons Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/admin/arelix
|
*/
/*
|--------------------------------------------------------------------------
| Arelix Extensions Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/extensions
|
*/
Route::group(['prefix' => 'extensions'], function () {
    Route::get('/server-splitter', [Admin\Arelix\ExtensionsController::class, 'serverSplitter'])->name('admin.extensions.server-splitter');
    Route::get('/staff-requests', [Admin\Arelix\ExtensionsController::class, 'staffRequests'])->name('admin.extensions.staff-requests');
    Route::get('/billing', [Admin\Arelix\ExtensionsController::class, 'billing'])->name('admin.extensions.billing');
});

/*
|--------------------------------------------------------------------------
| Arelix Addons Routes

    Route::get('/roles', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'index'])->name('roles');
    Route::post('/roles', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'show'])->name('roles.show');
    Route::put('/roles/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/permissions', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'listPermissions'])->name('permissions');

    Route::get('/members', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'members'])->name('members');
    Route::post('/members/{user:id}/assign', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'assignUser'])->name('members.assign');
    Route::post('/members/{user:id}/unassign', [Pterodactyl\Http\Controllers\Api\Client\Admin\Arelix\PermissionRoleController::class, 'unassignUser'])->name('members.unassign');

    Route::group(['prefix' => 'billing'], function () {
        Route::get('/categories', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'getCategories']);
        Route::post('/categories', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'upsertCategory']);
        Route::delete('/categories/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'deleteCategory']);

        Route::get('/subcategories', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'getSubcategories']);
        Route::post('/subcategories', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'upsertSubcategory']);
        Route::delete('/subcategories/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'deleteSubcategory']);

        Route::get('/games', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'getGames']);
        Route::post('/games', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'upsertGame']);
        Route::put('/games/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'upsertGame']);
        Route::delete('/games/{id}', [Pterodactyl\Http\Controllers\Api\Client\Admin\arelix\AdminBillingController::class, 'deleteGame']);

        Route::get('/promocodes', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\PromoCodeController::class, 'index']);
        Route::post('/promocodes', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\PromoCodeController::class, 'store']);
        Route::put('/promocodes/{id}', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\PromoCodeController::class, 'update']);
        Route::delete('/promocodes/{id}', [Pterodactyl\Http\Controllers\Api\Client\arelix\Billing\PromoCodeController::class, 'destroy']);
    });
});
*/
