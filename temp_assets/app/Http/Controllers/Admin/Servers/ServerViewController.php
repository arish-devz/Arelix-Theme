<?php
namespace Pterodactyl\Http\Controllers\Admin\Servers;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Server;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\EnvironmentService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Pterodactyl\Repositories\Eloquent\NestRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\MountRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Illuminate\Support\Facades\DB;
use JavaScript;

class ServerViewController extends Controller
{
    use JavascriptInjection;
    public function __construct(
        private DatabaseHostRepository $databaseHostRepository,
        private LocationRepository $locationRepository,
        private MountRepository $mountRepository,
        private NestRepository $nestRepository,
        private NodeRepository $nodeRepository,
        private ServerRepository $repository,
        private EnvironmentService $environmentService,
        private ViewFactory $view
    ) {
    }
    public function index(Request $request, Server $server): View
    {
        return $this->view->make('admin.servers.view.index', compact('server'));
    }
    public function details(Request $request, Server $server): View
    {
        $settingsRaw = app(SettingsRepository::class)->get('settings::app:addons:hyperv1', '{}');
        $settings = json_decode($settingsRaw, true);
        $billingEnabled = $settings['addons']['billing']['enabled'] ?? false;
        $billingCategories = [];
        $billingGames = [];
        if ($billingEnabled) {
            $billingCategories = DB::table('game_category')->get();
            $billingGames = DB::table('games')
                ->join('game_category', 'games.category_id', '=', 'game_category.id')
                ->select('games.*', 'game_category.title as category_name')
                ->get();
        }
        return $this->view->make('admin.servers.view.details', compact('server', 'billingCategories', 'billingGames', 'billingEnabled'));
    }
    public function build(Request $request, Server $server): View
    {
        $allocations = $server->node->allocations->toBase();
        return $this->view->make('admin.servers.view.build', [
            'server' => $server,
            'assigned' => $allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
        ]);
    }
    public function startup(Request $request, Server $server): View
    {
        $nests = $this->nestRepository->getWithEggs();
        $variables = $this->environmentService->handle($server);
        $this->plainInject([
            'server' => $server,
            'server_variables' => $variables,
            'nests' => $nests->map(function (Nest $item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);
        return $this->view->make('admin.servers.view.startup', compact('server', 'nests'));
    }
    public function database(Request $request, Server $server): View
    {
        return $this->view->make('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }
    public function mounts(Request $request, Server $server): View
    {
        $server->load('mounts');
        return $this->view->make('admin.servers.view.mounts', [
            'mounts' => $this->mountRepository->getMountListForServer($server),
            'server' => $server,
        ]);
    }
    public function manage(Request $request, Server $server): View
    {
        if ($server->status === Server::STATUS_INSTALL_FAILED) {
            throw new DisplayException('This server is in a failed install state and cannot be recovered. Please delete and re-create the server.');
        }
        $nodes = $this->nodeRepository->all();
        $canTransfer = false;
        if (count($nodes) >= 2) {
            $canTransfer = true;
        }
        JavaScript::put([
            'nodeData' => $this->nodeRepository->getNodesForServerCreation(),
        ]);
        return $this->view->make('admin.servers.view.manage', [
            'server' => $server,
            'locations' => $this->locationRepository->all(),
            'canTransfer' => $canTransfer,
        ]);
    }
    public function delete(Request $request, Server $server): View
    {
        return $this->view->make('admin.servers.view.delete', compact('server'));
    }
}
