<?php
namespace Pterodactyl\Http\Middleware;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;
class VerifyReCaptcha
{
    public function __construct(private Dispatcher $dispatcher, private Repository $config)
    {
    }
    public function handle(Request $request, \Closure $next): mixed
    {
        $turnstileEnabled = false;
        $turnstileSecret = '';
        $currentTheme = config('app.theme', 'default');
        if ($currentTheme === 'arelix') {
            try {
                $settingsRepository = app(\Pterodactyl\Repositories\Eloquent\SettingsRepository::class);
                $raw = $settingsRepository->get('settings::app:addons:arelix', '{}');
                $decoded = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
                $turnstileEnabled = isset($decoded['addons']['CloudflareTurnstile']['enabled']) && 
                                   $decoded['addons']['CloudflareTurnstile']['enabled'] &&
                                   !empty($decoded['addons']['CloudflareTurnstile']['secret_key']);
                if ($turnstileEnabled) {
                    $turnstileSecret = $decoded['addons']['CloudflareTurnstile']['secret_key'];
                    try {
                        $turnstileSecret = \Illuminate\Support\Facades\Crypt::decryptString($turnstileSecret);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {}
                }
            } catch (\Throwable) {
            }
        }
        if ($request->filled('cf-turnstile-response')) {
            if (!$turnstileEnabled || empty($turnstileSecret)) {
                 return $next($request);
            }
            $client = new Client();
            $res = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret' => $turnstileSecret,
                    'response' => $request->input('cf-turnstile-response'),
                ],
            ]);
            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());
                if ($result->success) {
                    return $next($request);
                }

                if (isset($result->{'error-codes'})) {
                    $errors = $result->{'error-codes'};
                    if (in_array('invalid-input-secret', $errors) || in_array('missing-input-secret', $errors)) {
                        return $next($request);
                    }
                }
            }
            $this->dispatcher->dispatch(
                new FailedCaptcha($request->ip(), null)
            );
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate captcha data.');
        }
        if (!$this->config->get('recaptcha.enabled')) {
            return $next($request);
        }
        if ($request->filled('g-recaptcha-response')) {
            $client = new Client();
            $res = $client->post($this->config->get('recaptcha.domain'), [
                'form_params' => [
                    'secret' => $this->config->get('recaptcha.secret_key'),
                    'response' => $request->input('g-recaptcha-response'),
                ],
            ]);
            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());
                if ($result->success && (!$this->config->get('recaptcha.verify_domain') || $this->isResponseVerified($result, $request))) {
                    return $next($request);
                }
            }
        }
        $this->dispatcher->dispatch(
            new FailedCaptcha($request->ip(), null)
        );
        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate captcha data.');
    }
    private function isResponseVerified(\stdClass $result, Request $request): bool
    {
        if (!$this->config->get('recaptcha.verify_domain')) {
            return false;
        }
        $url = parse_url($request->url());
        return $result->hostname === array_get($url, 'host');
    }
}
