<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prefer JSON error responses for /api/* when Accept is missing (e.g. some Postman clients).
 */
final class AcceptJsonForApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') && ! $request->headers->has('Accept')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
