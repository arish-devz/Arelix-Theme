<?php
namespace Pterodactyl\Http\Controllers\Api\Remote;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\ActivityLogSubject;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\ActivityEventRequest;
use Pterodactyl\Services\LicenseValidationService;
use Pterodactyl\Services\AddonConfigService;
class ActivityProcessingController extends Controller
{
    public function __construct(
        private \Pterodactyl\Services\ArmaReforger\ArmaReforgerService $armaService,
        private LicenseValidationService $licenseValidationService,
        private AddonConfigService $addonConfigService
    ) {
    }
    public function __invoke(ActivityEventRequest $request)
    {
        $tz = Carbon::now()->getTimezone();
        $node = $request->attributes->get('node');
        $servers = $node->servers()->whereIn('uuid', $request->servers())->get()->keyBy('uuid');
        $users = User::query()->whereIn('uuid', $request->users())->get()->keyBy('uuid');
        $logs = [];
        foreach ($request->input('data') as $datum) {
            $server = $servers->get($datum['server']);
            if (is_null($server) || !Str::startsWith($datum['event'], 'server:')) {
                continue;
            }
            if (in_array($datum['event'], ['server:power.start', 'server:power.restart'])) {
                 $enabled = $this->addonConfigService->getAddonConfigValue('arma-reforger-mod-manager', 'enabled', false);
                 $licenseValid = $this->licenseValidationService->shouldEnablePrivateAddon('arma_reforger_mod_manager');
                 if ($enabled && $licenseValid) {
                     try {
                         $this->armaService->processWebhooks($server);
                     } catch (\Exception $e) {
                     }
                 }
            }
            try {
                $when = Carbon::createFromFormat(
                    \DateTimeInterface::RFC3339,
                    preg_replace('/(\.\d+)Z$/', 'Z', $datum['timestamp']),
                    'UTC'
                );
            } catch (\Exception $exception) {
                Log::warning($exception, ['timestamp' => $datum['timestamp']]);
                $when = Carbon::now();
                $datum['metadata'] = array_merge($datum['metadata'] ?? [], ['original_timestamp' => $datum['timestamp']]);
            }
            $log = [
                'ip' => empty($datum['ip']) ? '127.0.0.1' : $datum['ip'],
                'event' => $datum['event'],
                'properties' => json_encode($datum['metadata'] ?? []),
                'timestamp' => $when->setTimezone($tz),
            ];
            if ($user = $users->get($datum['user'])) {
                $log['actor_id'] = $user->id;
                $log['actor_type'] = $user->getMorphClass();
            }
            if (!isset($logs[$datum['server']])) {
                $logs[$datum['server']] = [];
            }
            $logs[$datum['server']][] = $log;
        }
        foreach ($logs as $key => $data) {
            Assert::isInstanceOf($server = $servers->get($key), Server::class);
            $batch = [];
            foreach ($data as $datum) {
                $id = ActivityLog::insertGetId($datum);
                $batch[] = [
                    'activity_log_id' => $id,
                    'subject_id' => $server->id,
                    'subject_type' => $server->getMorphClass(),
                ];
            }
            ActivityLogSubject::insert($batch);
        }
    }
}
