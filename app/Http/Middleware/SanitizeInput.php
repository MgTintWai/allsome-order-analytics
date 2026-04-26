<?php

/**
 * Planning
 *
 * - Middleware for recursively sanitizing incoming request data (including nested arrays).
 * - Applies:
 *   - Key sanitization using `strip_tags` for string keys.
 *   - Value sanitization using `strip_tags` for string values.
 * - Ensures all levels of input arrays are processed consistently.
 *
 * Considerations:
 * - This provides basic input sanitization but is NOT a complete XSS protection strategy.
 * - Laravel Blade templates escape output by default, which should be relied on for XSS prevention.
 * - Global sanitization may alter or remove valid user input (e.g., HTML content).
 * - Key mutation during sanitization may affect request structure in edge cases.
 *
 * Purpose:
 * - Reduce the risk of simple malicious input payloads early in the request lifecycle.
 * - Provide a consistent, centralized layer for basic input cleaning before reaching controllers.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->replace($this->sanitizeArray($request->all()));

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $cleanKey = is_string($key) ? strip_tags($key) : $key;

            if (is_array($value)) {
                $sanitized[$cleanKey] = $this->sanitizeArray($value);
            } else {
                $sanitized[$cleanKey] = is_string($value) ? strip_tags($value) : $value;
            }
        }

        return $sanitized;
    }
}