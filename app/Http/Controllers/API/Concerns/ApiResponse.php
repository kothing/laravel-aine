<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = 'Error', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Validation error response
     *
     * @param string $message
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationError(string $message = 'Validation failed', $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 422,
            'message' => $message,
            'data' => $errors,
        ], 422);
    }

    /**
     * Created response
     *
     * @param mixed $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function created($data = null, string $message = 'Created'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Updated response
     *
     * @param mixed $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function updated($data = null, string $message = 'Updated'): JsonResponse
    {
        return $this->success($data, $message);
    }

    /**
     * Deleted response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function deleted(string $message = 'Deleted'): JsonResponse
    {
        return $this->success(null, $message);
    }
}