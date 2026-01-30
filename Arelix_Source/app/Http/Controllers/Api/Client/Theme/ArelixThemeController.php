<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Theme;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Permission;

class ArelixThemeController extends ClientApiController
{
    /**
     * Show theme settings (stub).
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'settings' => [],
            'message' => 'Arelix Theme: Clean Version'
        ]);
    }

    /**
     * Update theme settings (stub).
     */
    public function update(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Settings saved.']);
    }

    /**
     * Get sidebar items (stub).
     */
    public function getAvailableSidebarItems()
    {
        return response()->json(['items' => []]);
    }

    /**
     * Check for updates via GitHub.
     */
    public function checkVersion()
    {
        $localVersion = '1.0.0';
        $versionFile = base_path('arelix_version.json');

        if (file_exists($versionFile)) {
            $json = json_decode(file_get_contents($versionFile), true);
            $localVersion = $json['version'] ?? '1.0.0';
        }

        try {
            // Fetch from GitHub raw content
            $url = 'https://raw.githubusercontent.com/arish-devz/Arelix-Theme/main/arelix_version.json';
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                $remoteJson = $response->json();
                $remoteVersion = $remoteJson['version'] ?? $localVersion;

                $updateAvailable = version_compare($remoteVersion, $localVersion, '>');

                return response()->json([
                    'current_version' => $localVersion,
                    'latest_version' => $remoteVersion,
                    'update_available' => $updateAvailable,
                    'changelog' => $remoteJson['changelog'] ?? 'No changelog available.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Arelix Theme Update Check Failed: ' . $e->getMessage());
        }

        return response()->json([
            'current_version' => $localVersion,
            'latest_version' => 'Unknown',
            'update_available' => false
        ]);
    }

    /**
     * Start the update process.
     */
    public function startUpdate(Request $request)
    {
        // Dispatch the update job
        \App\Jobs\ArelixUpdateJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Update started in background. Please wait a few moments.'
        ]);
    }

    /**
     * Get update status.
     */
    public function getUpdateStatus()
    {
        // In a complex implementation, we would check cache/DB.
        // For now, assume idle or check if maintenance is active.
        return response()->json(['status' => 'idle']);
    }
}
