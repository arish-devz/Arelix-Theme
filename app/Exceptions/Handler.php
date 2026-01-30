<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    
    private const PTERODACTYL_RULE_STRING = 'pterodactyl\_rules\_';

    
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        RecordNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];

    
    protected static array $exceptionResponseCodes = [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
        ValidationException::class => 422,
    ];

    
    protected $dontFlash = [
        'token',
        'secret',
        'password',
        'password_confirmation',
    ];

    
    public function register(): void
    {
        if (config('app.exceptions.report_all', false)) {
            $this->dontReport = [];
        }

        $this->reportable(function (\PDOException $ex) {
            $ex = $this->generateCleanedExceptionStack($ex);
        });

        $this->reportable(function (TransportException $ex) {
            $ex = $this->generateCleanedExceptionStack($ex);
        });
    }

    private function generateCleanedExceptionStack(\Throwable $exception): string
    {
        $cleanedStack = '';
        foreach ($exception->getTrace() as $index => $item) {
            $cleanedStack .= sprintf(
                "#%d %s(%d): %s%s%s\n",
                $index,
                Arr::get($item, 'file'),
                Arr::get($item, 'line'),
                Arr::get($item, 'class'),
                Arr::get($item, 'type'),
                Arr::get($item, 'function')
            );
        }

        $message = sprintf(
            '%s: %s in %s:%d',
            class_basename($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        return $message . "\nStack trace:\n" . trim($cleanedStack);
    }

    
    public function render($request, \Throwable $e): Response
    {
        $connections = $this->container->make(Connection::class);

        
        
        
        
        
        
        
        
        
        if ($connections->transactionLevel()) {
            $connections->rollBack(0);
        }

        return parent::render($request, $e);
    }

    
    public function invalidJson($request, ValidationException $exception): JsonResponse
    {
        $codes = Collection::make($exception->validator->failed())->mapWithKeys(function ($reasons, $field) {
            $cleaned = [];
            foreach ($reasons as $reason => $attrs) {
                $cleaned[] = Str::snake($reason);
            }

            return [str_replace('.', '_', $field) => $cleaned];
        })->toArray();

        $errors = Collection::make($exception->errors())->map(function ($errors, $field) use ($codes, $exception) {
            $response = [];
            foreach ($errors as $key => $error) {
                $meta = [
                    'source_field' => $field,
                    'rule' => str_replace(self::PTERODACTYL_RULE_STRING, 'p_', Arr::get(
                        $codes,
                        str_replace('.', '_', $field) . '.' . $key
                    )),
                ];

                $converted = $this->convertExceptionToArray($exception)['errors'][0];
                $converted['detail'] = $error;
                $converted['meta'] = array_merge($converted['meta'] ?? [], $meta);

                $response[] = $converted;
            }

            return $response;
        })->flatMap(function ($errors) {
            return $errors;
        })->toArray();

        return response()->json(['errors' => $errors], $exception->status);
    }

    
    protected function convertExceptionToArray(\Throwable $e, array $override = []): array
    {
        $match = self::$exceptionResponseCodes[get_class($e)] ?? null;

        $error = [
            'code' => class_basename($e),
            'status' => method_exists($e, 'getStatusCode')
                ? strval($e->getStatusCode())
                : strval($match ?? '500'),
            'detail' => $e instanceof HttpExceptionInterface || !is_null($match)
                ? $e->getMessage()
                : 'An unexpected error was encountered while processing this request, please try again.',
        ];

        if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {
            
            
            
            $error['detail'] = 'The requested resource could not be found on the server.';
        }

        if (config('app.debug')) {
            $error = array_merge($error, [
                'detail' => $e->getMessage(),
                'source' => [
                    'line' => $e->getLine(),
                    'file' => str_replace(Application::getInstance()->basePath(), '', $e->getFile()),
                ],
                'meta' => [
                    'trace' => Collection::make($e->getTrace())
                        ->map(fn ($trace) => Arr::except($trace, ['args']))
                        ->all(),
                    'previous' => Collection::make($this->extractPrevious($e))
                        ->map(fn ($exception) => $e->getTrace())
                        ->map(fn ($trace) => Arr::except($trace, ['args']))
                        ->all(),
                ],
            ]);
        }

        return ['errors' => [array_merge($error, $override)]];
    }

    
    public static function isReportable(\Exception $exception): bool
    {
        return (new static(Container::getInstance()))->shouldReport($exception);
    }

    
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse($this->convertExceptionToArray($exception), JsonResponse::HTTP_UNAUTHORIZED);
        }

        return redirect()->guest('/auth/login');
    }

    
    protected function extractPrevious(\Throwable $e): array
    {
        $previous = [];
        while ($value = $e->getPrevious()) {
            if (!$value instanceof \Throwable) {
                break;
            }
            $previous[] = $value;
            $e = $value;
        }

        return $previous;
    }

    
    public static function toArray(\Throwable $e): array
    {
        return (new self(app()))->convertExceptionToArray($e);
    }
}
