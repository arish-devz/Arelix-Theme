<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Events\Auth\ProvidedAuthenticationToken;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class LoginCheckpointController extends AbstractLoginController
{
    private const TOKEN_EXPIRED_MESSAGE = 'The authentication token provided has expired, please refresh the page and try again.';

    
    public function __construct(
        private Encrypter $encrypter,
        private Google2FA $google2FA,
        private ValidationFactory $validation
    ) {
        parent::__construct();
    }

    
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $details = $request->session()->get('auth_confirmation_token');
        if (!$this->hasValidSessionData($details)) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        if (!hash_equals($request->input('confirmation_token') ?? '', $details['token_value'])) {
            $this->sendFailedLoginResponse($request);
        }

        try {
            
            $user = User::query()->findOrFail($details['user_id']);
        } catch (ModelNotFoundException) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        
        if (!is_null($recoveryToken = $request->input('recovery_token'))) {
            if ($this->isValidRecoveryToken($user, $recoveryToken)) {
                Event::dispatch(new ProvidedAuthenticationToken($user, true));

                return $this->sendLoginResponse($user, $request);
            }
        } else {
            $decrypted = $this->encrypter->decrypt($user->totp_secret);

            $code = (string) $request->input('authentication_code') ?? '';
            $cacheKey = 'totp_used_' . $user->id . '_' . $code;

            if (Cache::has($cacheKey)) {
                $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
            }

            if ($this->google2FA->verifyKey($decrypted, $code, config('pterodactyl.auth.2fa.window'))) {
                Cache::put($cacheKey, true, \Carbon\CarbonImmutable::now()->addMinutes(5));
                Event::dispatch(new ProvidedAuthenticationToken($user));

                return $this->sendLoginResponse($user, $request);
            }
        }

        $this->sendFailedLoginResponse($request, $user, !empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }

    
    protected function isValidRecoveryToken(User $user, string $value): bool
    {
        foreach ($user->recoveryTokens as $token) {
            if (password_verify($value, $token->token)) {
                $token->delete();

                return true;
            }
        }

        return false;
    }

    
    protected function hasValidSessionData(array $data): bool
    {
        $validator = $this->validation->make($data, [
            'user_id' => 'required|integer|min:1',
            'token_value' => 'required|string',
            'expires_at' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        if (!$data['expires_at'] instanceof CarbonInterface) {
            return false;
        }

        if ($data['expires_at']->isBefore(CarbonImmutable::now())) {
            return false;
        }

        return true;
    }
}
