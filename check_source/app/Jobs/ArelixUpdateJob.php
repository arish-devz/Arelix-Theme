<?php

namespace Pterodactyl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ArelixUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('[Arelix] Starting Arelix Theme Update...');

        // Define GitHub Raw URL
        $url = 'https://raw.githubusercontent.com/arish-devz/Arelix-Theme/main/assets/ArelixTheme.tar';
        $tempPath = base_path('ArelixTheme_Update.tar');

        try {
            // 1. Download
            Log::info("[Arelix] Downloading assets from $url");
            $response = Http::withOptions(['sink' => $tempPath])->timeout(120)->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to download theme assets. HTTP Status: " . $response->status());
            }

            // 2. Extract
            Log::info("[Arelix] Extracting assets...");
            $basePath = base_path();
            // Use shell_exec/exec for tar overwrite. 
            // -o = overwrite, -x = extract, -f = file
            $command = "tar -xf \"$tempPath\" -C \"$basePath\" --overwrite 2>&1";
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // Try without overwrite flag if busybox tar
                $commandRetry = "tar -xf \"$tempPath\" -C \"$basePath\" 2>&1";
                exec($commandRetry, $outputRetry, $returnVarRetry);

                if ($returnVarRetry !== 0) {
                    throw new \Exception("Failed to extract assets. Output: " . implode("\n", $output));
                }
            }

            // 3. Cleanup
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            // 4. Post-Install Tasks
            Log::info("[Arelix] Running post-install tasks...");
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);

            Log::info('[Arelix] Update Job Completed Successfully.');

        } catch (\Exception $e) {
            Log::error('[Arelix] Update Job Failed: ' . $e->getMessage());
            // Cleanup on failure
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw $e;
        }
    }
}
