<?php

use App\Exceptions\Handler as AppExceptionHandler;
use App\Http\Middleware\AcceptJsonForApi;
use App\Http\Middleware\SanitizeInput;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            AcceptJsonForApi::class,
        ]);
        $middleware->append(SanitizeInput::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(static function ($request, $_e) {
            return $request->is('api/*')
                || $request->expectsJson()
                || $request->wantsJson();
        });
        AppExceptionHandler::register($exceptions);
    })->create();
