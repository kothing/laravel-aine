<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\ForbiddenHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // Handle API requests with unified response format
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with unified response format.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $exception)
    {
        // Validation exception
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'Validation failed',
                'data' => $exception->errors(),
            ], 422);
        }

        // Model not found exception
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Resource not found',
                'data' => null,
            ], 404);
        }

        // Not found exception
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Not found',
                'data' => null,
            ], 404);
        }

        // Unauthorized exception
        if ($exception instanceof UnauthorizedHttpException) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        // Forbidden exception
        if ($exception instanceof ForbiddenHttpException) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Forbidden',
                'data' => null,
            ], 403);
        }

        // Default error response
        return response()->json([
            'success' => false,
            'code' => 500,
            'message' => 'Internal server error',
            'data' => null,
        ], 500);
    }
}
