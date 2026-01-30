<?php
namespace Pterodactyl\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
class VerifyCsrfToken extends BaseVerifier
{
    protected $except = ['remote/*', 'daemon/*', 'api/application/license/*', 'api/public/license/*'];
}
