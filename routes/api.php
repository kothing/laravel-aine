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
Route::middleware(['validate.project.access'])->prefix('project')->group(function () {
    Route::get('/{projectIdentifier}', [ProjectsController::class, 'showByDomain']);
    Route::get('/{projectIdentifier}/media', [MediaController::class, 'getProjectMediaByDomain']);
    Route::get('/{projectIdentifier}/media/{id}', [MediaController::class, 'getFileByIDByDomain']);
    Route::get('/{projectIdentifier}/{slug}', [ContentController::class, 'getContentByDomain']);
    Route::get('/{projectIdentifier}/{slug}/{id}', [ContentController::class, 'getContentByIDByDomain']);
});

// ============================================
// Method 2: Original API (requires UUID + Token)
// Use case: Laravel frontend projects, backend server calls
// ============================================
Route::middleware(['verify.domain.whitelist'])->group(function () {
    Route::get('/{uuid}/project-media', [MediaController::class, 'getProjectMedia']);
    Route::get('/{uuid}/project-media/{id}', [MediaController::class, 'getFileByID']);
    Route::get('/{uuid}/project-media/name/{name}', [MediaController::class, 'getFileByName']);
    Route::delete('/{uuid}/project-media/{id}', [MediaController::class, 'deleteFile'])->middleware('auth:sanctum');
    Route::post('/{uuid}/project-media/upload', [MediaController::class, 'uploadFile'])->middleware('auth:sanctum');

    Route::get('/{uuid}', [ProjectsController::class, 'show']);
    Route::get('/{uuid}/{slug}', [ContentController::class, 'getContent']);
    Route::get('/{uuid}/{slug}/{id}', [ContentController::class, 'getContentByID']);
    Route::post('/{uuid}/{slug}', [ContentController::class, 'create'])->middleware('auth:sanctum');
    Route::post('/{uuid}/{slug}/update/{id}', [ContentController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{uuid}/{slug}/{id}', [ContentController::class, 'delete'])->middleware('auth:sanctum');
});

Route::options('{any}', function () {
    return response('', 204);
})->where('any', '.*');
