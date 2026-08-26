<?php

namespace Tests\Feature;

use App\Http\Controllers\API\ContentController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Regression tests for P0-3: pagination parameters on the public API must
 * be validated and bounded so an abusive `limit` / `offset` cannot force
 * the API to materialise (or skip) the whole collection.
 */
class PaginationLimitTest extends TestCase
{
    private function validatePagination(array $query): ?JsonResponse
    {
        $controller = new ContentController();
        $method = new \ReflectionMethod(ContentController::class, 'validatePagination');
        $method->setAccessible(true);

        $request = Request::create('/api/project/x/collection', 'GET', $query);
        $result = $method->invoke($controller, $request);

        return $result instanceof JsonResponse ? $result : null;
    }

    private function assertPaginationError(?JsonResponse $response, string $messageContains): void
    {
        $this->assertNotNull($response, 'Expected a validation error response.');
        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertStringContainsString($messageContains, $payload['message'] ?? '');
    }

    public function test_valid_limit_and_offset_pass(): void
    {
        $this->assertNull($this->validatePagination(['limit' => 20, 'offset' => 40]));
    }

    public function test_absent_parameters_pass(): void
    {
        $this->assertNull($this->validatePagination([]));
    }

    public function test_non_numeric_limit_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => 'abc']),
            'Invalid limit parameter'
        );
    }

    public function test_zero_limit_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => '0']),
            'Invalid limit parameter'
        );
    }

    public function test_negative_limit_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => '-5']),
            'Invalid limit parameter'
        );
    }

    public function test_huge_limit_is_allowed_for_clamping(): void
    {
        // validatePagination permits it (the caller clamps via min());
        // this documents that huge-but-numeric limits are neutralised
        // at the query builder, not rejected.
        $this->assertNull($this->validatePagination(['limit' => '1000000']));
    }

    public function test_non_numeric_offset_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => 10, 'offset' => 'abc']),
            'Invalid offset parameter'
        );
    }

    public function test_negative_offset_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => 10, 'offset' => '-1']),
            'Invalid offset parameter'
        );
    }

    public function test_offset_beyond_maximum_is_rejected(): void
    {
        $this->assertPaginationError(
            $this->validatePagination(['limit' => 10, 'offset' => '10001']),
            'Offset cannot exceed 10000'
        );
    }

    public function test_boundary_offset_passes(): void
    {
        $this->assertNull($this->validatePagination(['limit' => 10, 'offset' => '10000']));
    }
}
