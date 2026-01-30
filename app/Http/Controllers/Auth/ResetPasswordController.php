<?php
namespace Pterodactyl\Http\Controllers\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Pterodactyl\Http\Requests\Auth\ResetPasswordRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
class ResetPasswordController extends AbstractLoginController
{
    use ResetsPasswords;
    public string $redirectTo = '/';
    protected bool $hasTwoFactor = false;
    public function __construct(
        private Dispatcher $dispatcher,
        private Hasher $hasher,
        private UserRepositoryInterface $userRepository
    ) {
        parent::__construct();
    }
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $response = $this->broker()->reset(
            $this->credentials($request),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );
        if ($response === Password::PASSWORD_RESET) {
            return $this->sendResetResponse();
        }
        throw new DisplayException(trans($response));
    }
    protected function resetPassword($user, $password)
    {
        $user = $this->userRepository->update($user->id, [
            'password' => $this->hasher->make($password),
            $user->getRememberTokenName() => Str::random(60),
        ]);
        $this->dispatcher->dispatch(new PasswordReset($user));
        if (!$user->use_totp) {
            $this->performLogin($user, request());
        }
        $this->hasTwoFactor = $user->use_totp;
    }
    protected function sendResetResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'redirect_to' => $this->redirectTo,
            'send_to_login' => $this->hasTwoFactor,
        ]);
    }
}
