<?php
namespace Pterodactyl\Http\Middleware;
use Illuminate\Http\Request;
use Pterodactyl\Services\PermissionRegistryService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
class AdminAuthenticate
{
    private PermissionRegistryService $permissionRegistry;
    public function __construct(PermissionRegistryService $permissionRegistry)
    {
        $this->permissionRegistry = $permissionRegistry;
    }
    public function handle(Request $request, \Closure $next): mixed
    {
        $user = $request->user();
        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        if ($user->root_admin) {
            return $next($request);
        }
        if (!$user->permissionRole) {
            throw new AccessDeniedHttpException();
        }
        $route = $request->route();
        $routeName = $route->getName();
        $actionName = $route->getActionName();
        $requiredPermission = $this->permissionRegistry->resolvePermission($routeName, $actionName);
        if ($routeName === 'admin.index') {
            return $next($request);
        }

        if ($requiredPermission && $user->hasAdminPermission($requiredPermission)) {
             return $next($request);
        }
        throw new AccessDeniedHttpException();
    }
}
