<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Spatie\Permission\Exceptions\UnauthorizedException;

// Fresh deployment bootstrap: the installer itself runs through the `web`
// middleware (encrypted cookies/session need a valid APP_KEY). If there is
// no .env yet, create one from the template; if it has no APP_KEY yet,
// generate one — the installer wizard then rewrites the rest of the file.
$bootstrapEnv = dirname(__DIR__).'/.env';
if (! file_exists(dirname(__DIR__).'/storage/installed')) {
    if (! file_exists($bootstrapEnv) && file_exists(dirname(__DIR__).'/.env.example')) {
        copy(dirname(__DIR__).'/.env.example', $bootstrapEnv);
    }
    if (file_exists($bootstrapEnv)) {
        $bootstrapEnvContent = (string) file_get_contents($bootstrapEnv);
        if (! preg_match('/^APP_KEY=.+/m', $bootstrapEnvContent)) {
            $bootstrapKey = 'base64:'.base64_encode(random_bytes(32));
            file_put_contents(
                $bootstrapEnv,
                preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$bootstrapKey, $bootstrapEnvContent)
            );
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/frontend.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\RedirectIfNotInstalled::class);
        $middleware->prepend(\App\Http\Middleware\DynamicCors::class);
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->prepend(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class);

        $middleware->appendToGroup('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
        ]);

        $middleware->alias([
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'verify.domain.whitelist' => \App\Http\Middleware\VerifyDomainWhitelist::class,
            'validate.project.access' => \App\Http\Middleware\ValidateProjectAccess::class,
            'dynamic.cors' => \App\Http\Middleware\DynamicCors::class,
        ]);

        // The frontend app owns the `/` route (frontend.php), so a logged-in
        // user hitting a guest-only page (login, forgot-password, ...) is
        // sent to the admin dashboard instead of the public frontend.
        $middleware->redirectUsersTo('/admin');

        $middleware->validateCsrfTokens(except: [
            // Public form endpoints only: embedded forms are submitted from
            // third-party pages that have no Laravel CSRF token. The admin
            // form builder lives under admin-api/content/forms/* and must
            // NOT be exempted (it is session-authenticated).
            'forms/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, Request $request) {
            // Return uniform JSON errors for both the public API (api/*) and
            // the admin SPA endpoints (admin-api/*). Everything else (auth
            // views, the frontend app, form pages) keeps HTML rendering.
            if (!$request->is('api/*', 'admin-api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false, 'code' => 422,
                    'message' => 'Validation failed', 'data' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false, 'code' => 404,
                    'message' => 'Resource not found', 'data' => null,
                ], 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false, 'code' => 404,
                    'message' => 'Not found', 'data' => null,
                ], 404);
            }

            if ($exception instanceof UnauthorizedHttpException) {
                return response()->json([
                    'success' => false, 'code' => 401,
                    'message' => 'Unauthorized', 'data' => null,
                ], 401);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false, 'code' => 401,
                    'message' => 'Unauthenticated', 'data' => null,
                ], 401);
            }

            if ($exception instanceof AccessDeniedHttpException) {
                return response()->json([
                    'success' => false, 'code' => 403,
                    'message' => 'Forbidden', 'data' => null,
                ], 403);
            }

            if ($exception instanceof UnauthorizedException) {
                return response()->json([
                    'success' => false, 'code' => 403,
                    'message' => 'Forbidden', 'data' => null,
                ], 403);
            }

            return response()->json([
                'success' => false, 'code' => 500,
                'message' => 'Internal server error', 'data' => null,
            ], 500);
        });
    })
    ->create();
