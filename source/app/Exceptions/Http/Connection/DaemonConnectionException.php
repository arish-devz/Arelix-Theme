<?php

namespace Pterodactyl\Exceptions\Http\Connection;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\DisplayException;


class DaemonConnectionException extends DisplayException
{
    private int $statusCode = Response::HTTP_GATEWAY_TIMEOUT;

    
    private ?string $requestId;

    
    public function __construct(GuzzleException $previous, bool $useStatusCode = true)
    {
        
        $response = method_exists($previous, 'getResponse') ? $previous->getResponse() : null;
        $this->requestId = $response?->getHeaderLine('X-Request-Id');

        if ($useStatusCode) {
            $this->statusCode = is_null($response) ? $this->statusCode : $response->getStatusCode();
            
            
            
            
            
            
            if ($this->statusCode < 400) {
                $this->statusCode = Response::HTTP_BAD_GATEWAY;
            }
        }

        if (is_null($response)) {
            $message = 'Could not establish a connection to the machine running this server. Please try again.';
        } else {
            $message = sprintf('There was an error while communicating with the machine running this server. This error has been logged, please try again. (code: %s) (request_id: %s)', $response->getStatusCode(), $this->requestId ?? '<nil>');
        }

        
        
        if ($this->statusCode < 500 && !is_null($response)) {
            $body = json_decode($response->getBody()->__toString(), true);
            $message = sprintf('An error occurred on the remote host: %s. (request id: %s)', $body['error'] ?? $message, $this->requestId ?? '<nil>');
        }

        $level = $this->statusCode >= 500 && $this->statusCode !== 504
            ? DisplayException::LEVEL_ERROR
            : DisplayException::LEVEL_WARNING;

        parent::__construct($message, $previous, $level);
    }

    
    public function report()
    {
        Log::{$this->getErrorLevel()}($this->getPrevious(), [
            'request_id' => $this->requestId,
        ]);
    }

    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }
}
