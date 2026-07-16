<?php

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [EnsureAccountIsActive::class]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Record not found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 401) {
                return response()->view('errors.401', [], 401);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            if (! config('app.debug')) {
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
