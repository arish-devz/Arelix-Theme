<?php

namespace Pterodactyl\Exceptions\Repository;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecordNotFoundException extends RepositoryException implements HttpExceptionInterface
{
    
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    
    public function getHeaders(): array
    {
        return [];
    }
}
