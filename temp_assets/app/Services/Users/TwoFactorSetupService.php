<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class TwoFactorSetupService
{
    public const VALID_BASE32_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    
    public function __construct(
        private ConfigRepository $config,
        private Encrypter $encrypter,
        private UserRepositoryInterface $repository
    ) {
    }

    
    public function handle(User $user): array
    {
        $secret = '';
        try {
            for ($i = 0; $i < $this->config->get('pterodactyl.auth.2fa.bytes', 16); ++$i) {
                $secret .= substr(self::VALID_BASE32_CHARACTERS, random_int(0, 31), 1);
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }

        $this->repository->withoutFreshModel()->update($user->id, [
            'totp_secret' => $this->encrypter->encrypt($secret),
        ]);

        $company = urlencode(preg_replace('/\s/', '', $this->config->get('app.name')));

        return [
            'image_url_data' => sprintf(
                'otpauth://totp/%1$s:%2$s?secret=%3$s&issuer=%1$s',
                rawurlencode($company),
                rawurlencode($user->email),
                rawurlencode($secret),
            ),
            'secret' => $secret,
        ];
    }
}
