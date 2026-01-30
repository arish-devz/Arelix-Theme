<?php
namespace Pterodactyl\Http\Controllers\Admin;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Model;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Pterodactyl\Models\UserLoginHistory;
use Exception;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Http\Requests\Admin\NewUserFormRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
class UserController extends Controller
{
    use AvailableLanguages;
    public function __construct(
        protected AlertsMessageBag $alert,
        protected UserCreationService $creationService,
        protected UserDeletionService $deletionService,
        protected Translator $translator,
        protected UserUpdateService $updateService,
        protected UserRepositoryInterface $repository,
        protected ViewFactory $view,
        protected \Pterodactyl\Repositories\Eloquent\SettingsRepository $settingsRepository
    ) {
    }
    public function index(Request $request): View
    {
        $users = QueryBuilder::for(
            User::query()->select('users.*')
                ->selectRaw('COUNT(DISTINCT(subusers.id)) as subuser_of_count')
                ->selectRaw('COUNT(DISTINCT(servers.id)) as servers_count')
                ->addSelect(['last_login_at' => DB::table('user_active_sessions')->select('last_active_at')->whereColumn('user_id', 'users.id')->orderBy('last_active_at', 'desc')->take(1)])
                ->addSelect(['last_login_ip' => UserLoginHistory::select('ip_address')->whereColumn('user_id', 'users.id')->latest()->take(1)])
                ->leftJoin('subusers', 'subusers.user_id', '=', 'users.id')
                ->leftJoin('servers', 'servers.owner_id', '=', 'users.id')
                ->groupBy('users.id')
        )
            ->allowedFilters(['username', 'email', 'uuid'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate(50);
        return $this->view->make('admin.users.index', ['users' => $users]);
    }
    public function create(): View
    {
        return $this->view->make('admin.users.new', [
            'languages' => $this->getAvailableLanguages(true),
        ]);
    }
    public function view(User $user): View
    {
        $activeSessions = DB::table('user_active_sessions')
            ->where('user_id', $user->id)
            ->where('is_revoked', false)
            ->orderBy('last_active_at', 'desc')
            ->get();
        $loginHistory = DB::table('user_login_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        $settingsRaw = $this->settingsRepository->get('settings::app:addons:arelix', '{}');
        $settings = json_decode($settingsRaw, true);
        $currency = $settings['addons']['billing']['currency_symbol'] ?? '$';
        return $this->view->make('admin.users.view', [
            'user' => $user,
            'languages' => $this->getAvailableLanguages(true),
            'activeSessions' => $activeSessions,
            'loginHistory' => $loginHistory,
            'currency' => $currency,
        ]);
    }
    public function delete(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            throw new DisplayException($this->translator->get('admin/user.exceptions.user_has_servers'));
        }
        $this->deletionService->handle($user);
        return redirect()->route('admin.users');
    }
    public function store(NewUserFormRequest $request): RedirectResponse
    {
        $data = $request->normalize();
        if (!$request->user()->root_admin) {
            unset($data['root_admin']);
        }

        $user = $this->creationService->handle($data);
        $this->alert->success($this->translator->get('admin/user.notices.account_created'))->flash();
        return redirect()->route('admin.users.view', $user->id);
    }
    public function update(UserFormRequest $request, User $user): RedirectResponse
    {
        if ($user->root_admin && !$request->user()->root_admin) {
            throw new DisplayException($this->translator->get('admin/user.exceptions.user_has_servers'));
        }

        $data = $request->normalize();
        if (!$request->user()->root_admin) {
            unset($data['root_admin']);
        }

        $this->updateService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($user, $data);
        $this->alert->success(trans('admin/user.notices.account_updated'))->flash();
        return redirect()->route('admin.users.view', $user->id);
    }
    public function json(Request $request): Model|Collection
    {
        $users = QueryBuilder::for(User::query())->allowedFilters(['email'])->paginate(25);
        if ($request->query('user_id')) {
            $user = User::query()->findOrFail($request->input('user_id'));
            $user->md5 = md5(strtolower($user->email));
            return $user;
        }
        return $users->map(function ($item) {
            $item->md5 = md5(strtolower($item->email));
            return $item;
        });
    }
    public function revokeSession(Request $request, User $user, string $session): RedirectResponse
    {
        DB::table('user_active_sessions')
            ->where('user_id', $user->id)
            ->where('session_id', $session)
            ->delete();
        try {
            $driver = config('session.driver');
            Session::getHandler()->destroy($session);
            if ($driver === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('id', $session)
                    ->delete();
            }
        } catch (Exception $e) {
        }
        $this->alert->success('Target session has been marked for revocation.')->flash();
        return redirect()->route('admin.users.view', $user->id);
    }

    public function revokeAllSessions(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($user) {
            $sessions = DB::table('user_active_sessions')
                ->where('user_id', $user->id)
                ->get();

            DB::table('user_active_sessions')
                ->where('user_id', $user->id)
                ->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->whereIn('id', $sessions->pluck('session_id'))
                    ->delete();
            }
        });

        $this->alert->success('All active sessions for this user have been revoked.')->flash();

        return redirect()->route('admin.users.view', $user->id);
    }
}
