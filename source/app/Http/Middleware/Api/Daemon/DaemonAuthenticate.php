<?php

namespace Pterodactyl\Http\Middleware\Api\Daemon;

use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticate
{
    
    protected array $except = [
        'daemon.configuration',
    ];

    
    public function __construct(private Encrypter $encrypter, private NodeRepository $repository)
    {
    }

    
    public function handle(Request $request, \Closure $next): mixed
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (is_null($bearer = $request->bearerToken())) {
            throw new HttpException(401, 'Access to this endpoint must include an Authorization header.', null, ['WWW-Authenticate' => 'Bearer']);
        }

        $parts = explode('.', $bearer);
        
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            throw new BadRequestHttpException('The Authorization header provided was not in a valid format.');
        }

        try {
            
            $node = $this->repository->findFirstWhere([
                'daemon_token_id' => $parts[0],
            ]);

            if (hash_equals((string) $this->encrypter->decrypt($node->daemon_token), $parts[1])) {
                $request->attributes->set('node', $node);

                return $next($request);
            }
        } catch (RecordNotFoundException $exception) {
            
        }

        throw new AccessDeniedHttpException('You are not authorized to access this resource.');
    }
}
