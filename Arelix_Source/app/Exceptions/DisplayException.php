<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Container\Container;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DisplayException extends PterodactylException implements HttpExceptionInterface
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    
    public function __construct(string $message, ?\Throwable $previous = null, protected string $level = self::LEVEL_ERROR, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorLevel(): string
    {
        return $this->level;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getHeaders(): array
    {
        return [];
    }

    
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(Handler::toArray($this), $this->getStatusCode(), $this->getHeaders());
        }

        app(AlertsMessageBag::class)->danger($this->getMessage())->flash();

        return redirect()->back()->withInput();
    }

    
    public function report()
    {
        if (!$this->getPrevious() instanceof \Exception || !Handler::isReportable($this->getPrevious())) {
            return null;
        }

        try {
            $logger = Container::getInstance()->make(LoggerInterface::class);
        } catch (Exception) {
            throw $this->getPrevious();
        }

        return $logger->{$this->getErrorLevel()}($this->getPrevious());
    }
}
