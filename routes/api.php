<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\ContentController;
use App\Http\Controllers\API\ProjectsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ============================================
// Method 1: Explicit project identifier API
// Use case: Multi-project frontend applications (single domain accessing multiple backend projects)
// Requires explicit project identifier (UUID or slug) in URL
// Validates project access via domain whitelist
// ============================================
Route::middleware(['verify.domain.whitelist'])->prefix('project')->group(function () {
    Route::get('/{project_identifier}', [ProjectsController::class, 'getProject']);
    Route::get('/{project_identifier}/{slug}', [ContentController::class, 'getContentList']);
    Route::get('/{project_identifier}/{slug}/{slug_id}', [ContentController::class, 'getProjectContentByID']);
    Route::get('/{project_identifier}/{slug}/{slug_id}/{related_slug}', [ContentController::class, 'getProjectContentByRelation']);
    Route::post('/{project_identifier}/{slug}', [ContentController::class, 'createContent'])->middleware('auth:sanctum');
    Route::post('/{project_identifier}/{slug}/update/{slug_id}', [ContentController::class, 'updateContent'])->middleware('auth:sanctum');
    Route::delete('/{project_identifier}/{slug}/{slug_id}', [ContentController::class, 'deleteContent'])->middleware('auth:sanctum');

    Route::get('/{project_identifier}/media', [MediaController::class, 'getMediaList']);
    Route::get('/{project_identifier}/media/{media_id}', [MediaController::class, 'getMediaByID']);
    Route::get('/{project_identifier}/media/name/{media_name}', [MediaController::class, 'getMediaByName']);
    Route::delete('/{project_identifier}/media/{media_id}', [MediaController::class, 'deleteMedia'])->middleware('auth:sanctum');
    Route::post('/{project_identifier}/media/upload', [MediaController::class, 'uploadMedia'])->middleware('auth:sanctum');
});

// ============================================
// Method 2: Original API (requires UUID + Token)
// Use case: Laravel frontend projects, backend server calls
// Security: All operations require UUID validation + Token authentication
//           Prevents unauthorized cross-domain access from any website
// ============================================
Route::middleware(['validate.project.access', 'auth:sanctum'])->group(function () {
    Route::get('/{uuid}', [ProjectsController::class, 'getProjectByUuid']);
    Route::get('/{uuid}/{slug}', [ContentController::class, 'getContentListByUuid']);
    Route::get('/{uuid}/{slug}/{slug_id}', [ContentController::class, 'getContentByUuid']);
    Route::get('/{uuid}/{slug}/{slug_id}/{related_slug}', [ContentController::class, 'getContentByRelationByUuid']);
    Route::post('/{uuid}/{slug}', [ContentController::class, 'createContentByUuid']);
    Route::post('/{uuid}/{slug}/update/{slug_id}', [ContentController::class, 'updateContentByUuid']);
    Route::delete('/{uuid}/{slug}/{slug_id}', [ContentController::class, 'deleteContentByUuid']);

    Route::get('/{uuid}/project-media', [MediaController::class, 'getMediaListByUuid']);
    Route::get('/{uuid}/project-media/{media_id}', [MediaController::class, 'getMediaByUuid']);
    Route::get('/{uuid}/project-media/name/{media_name}', [MediaController::class, 'getMediaByNameByUuid']);
    Route::delete('/{uuid}/project-media/{media_id}', [MediaController::class, 'deleteMediaByUuid']);
    Route::post('/{uuid}/project-media/upload', [MediaController::class, 'uploadMediaByUuid']);
});

Route::options('{any}', function () {
    return response('', 204);
})->where('any', '.*');
