<?php
namespace Pterodactyl\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Str;
use Pterodactyl\Helpers\UserAgentHelper;
use Pterodactyl\Helpers\IpDetailsHelper;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cookie;

abstract class AbstractLoginController extends Controller
{
    use AuthenticatesUsers;
    protected AuthManager $auth;
    protected int $lockoutTime;
    protected int $maxLoginAttempts;
    protected string $redirectTo = '/';
    public function __construct()
    {
        $this->lockoutTime = config('auth.lockout.time');
        $this->maxLoginAttempts = config('auth.lockout.attempts');
        $this->auth = Container::getInstance()->make(AuthManager::class);
    }
    protected function sendFailedLoginResponse(Request $request, Authenticatable $user = null, string $message = null)
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedLoginEvent($user, [
            $this->getField($request->input('user')) => $request->input('user'),
        ]);
        if ($request->route()->named('auth.login-checkpoint')) {
            throw new DisplayException($message ?? trans('auth.two_factor.checkpoint_failed'));
        }
        throw new DisplayException(trans('auth.failed'));
    }
    protected function sendLoginResponse(User $user, Request $request): JsonResponse
    {
        $this->performLogin($user, $request);
        return new JsonResponse([
            'data' => [
                'complete' => true,
                'intended' => $this->redirectPath(),
                'user' => $user->toVueObject(),
            ],
        ]);
    }
    protected function performLogin(User $user, Request $request): void
    {
        $request->session()->remove('auth_confirmation_token');
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);
        $this->auth->guard()->login($user, true);
        $loginToken = Str::random(64);
        $sessionId = $request->session()->getId();
        $ip = $request->ip();
        foreach ($request->ips() as $candidateIp) {
            if (filter_var($candidateIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = $candidateIp;
                break;
            }
        }
        if (str_starts_with($ip, '::ffff:')) {
            $ip = substr($ip, 7);
        }
        $userAgent = $request->header('User-Agent');
        require_once app_path('Helpers/ActivityHelpers.php');
        $uaData = UserAgentHelper::parse($userAgent);
        $ipDetails = IpDetailsHelper::getDetails($ip);
        DB::table('user_active_sessions')
            ->where('user_id', $user->id)
            ->where('user_agent', $userAgent)
            ->where('ip_address', $ip)
            ->delete();
        DB::table('user_active_sessions')->insert([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'login_token' => $loginToken,  
            'device_type' => $uaData['deviceType'],
            'platform' => $uaData['platform'],
            'browser' => $uaData['browser'],
            'is_vpn' => $ipDetails['is_vpn'],
            'city' => $ipDetails['city'],
            'state' => $ipDetails['state'],
            'country' => $ipDetails['country'],
            'is_revoked' => false,
            'last_active_at' => CarbonImmutable::now(),
            'created_at' => CarbonImmutable::now(),
        ]);
        Cookie::queue('hyper_session_token', $loginToken, 2628000);
        Event::dispatch(new DirectLogin($user, true));
    }
    protected function getField(string $input = null): string
    {
        return ($input && str_contains($input, '@')) ? 'email' : 'username';
    }
    protected function fireFailedLoginEvent(Authenticatable $user = null, array $credentials = [])
    {
        Event::dispatch(new Failed('auth', $user, $credentials));
    }
}
