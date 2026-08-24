<?php

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ProjectsController;
use App\Http\Controllers\Admin\CollectionsController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\CollectionFieldsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TranslationsController;
use App\Http\Controllers\Admin\ProjectTranslationsController;
use App\Http\Controllers\Admin\LocalizationController;
use App\Http\Controllers\Frontend\FormController;

// API Routes - prefixed with /admin-api to avoid conflict with Vue Router
Route::middleware('auth:web')->prefix('admin-api')->group(function(){
    Route::get('/user', function () {
        $user = Auth::user();
        return new UserResource($user);
    });
    Route::post('/user/update_name', [UsersController::class, 'updateName']);
    Route::post('/user/update_email', [UsersController::class, 'updateEmail']);
    Route::post('/user/update_password', [UsersController::class, 'updatePassword']);

    Route::prefix('settings')->group(function(){
        Route::get('/', [SettingsController::class, 'index']);
        // Only super admins may change global site settings.
        Route::post('/update', [SettingsController::class, 'update'])->middleware(['role:super_admin']);
    });

    Route::prefix('translations')->group(function(){
        Route::get('/', [TranslationsController::class, 'index']);
        Route::get('/locales', [TranslationsController::class, 'localesList']);
        Route::get('/dict', [TranslationsController::class, 'dict']);
        // Only super admins may write to the global UI translation strings.
        Route::post('/save', [TranslationsController::class, 'save'])->middleware(['role:super_admin']);
        Route::post('/add', [TranslationsController::class, 'addString'])->middleware(['role:super_admin']);
    });

    // Admin UI languages — managed here (Localization), not in Translations.
    Route::prefix('localization')->group(function(){
        Route::get('/', [LocalizationController::class, 'index']);
        // Only super admins may add/remove UI languages or change the default.
        Route::post('/', [LocalizationController::class, 'store'])->middleware(['role:super_admin']);
        Route::post('/set-default', [LocalizationController::class, 'setDefault'])->middleware(['role:super_admin']);
        Route::delete('/{code}', [LocalizationController::class, 'destroy'])->middleware(['role:super_admin']);
    });

    Route::prefix('projects')->group(function(){
        Route::get('/', [ProjectsController::class, 'index']);
        Route::post('/', [ProjectsController::class, 'store'])->middleware(['role:super_admin']);
        Route::get('/{id}', [ProjectsController::class, 'show']);
        Route::post('/update/{id}', [ProjectsController::class, 'update']);
        Route::post('/toggle-status/{id}', [ProjectsController::class, 'toggleStatus']);
        Route::delete('/delete/{id}', [ProjectsController::class, 'delete'])->middleware(['role:super_admin']);
        Route::get('/check-slug/{slug}', [ProjectsController::class, 'checkSlug']);

        Route::prefix('settings')->middleware(['role:super_admin'])->group(function(){
            Route::get('/locales/{id}', [ProjectsController::class, 'locales']);
            Route::post('/locales/add/{id}', [ProjectsController::class, 'addLocale']);
            Route::post('/locales/change-default-locale/{id}', [ProjectsController::class, 'changeDefaultLocale']);
            Route::post('/locales/delete-locale/{id}', [ProjectsController::class, 'deleteLocale']);

            Route::prefix('translations')->group(function(){
                Route::get('/{id}', [ProjectTranslationsController::class, 'index']);
                Route::get('/{id}/dict', [ProjectTranslationsController::class, 'dict']);
                Route::post('/{id}/save', [ProjectTranslationsController::class, 'save']);
                Route::post('/{id}/add', [ProjectTranslationsController::class, 'addString']);
            });

            Route::get('/users/{id}', [ProjectsController::class, 'users']);
            Route::post('/users/assign/{id}', [ProjectsController::class, 'assignUser']);
            Route::post('/users/remove-user/{id}', [ProjectsController::class, 'removeUser']);
            Route::post('/users/new/{id}', [ProjectsController::class, 'newUser']);

            Route::get('/api/{id}', [ProjectsController::class, 'api']);
            Route::post('/api/new-token/{id}', [ProjectsController::class, 'newToken']);
            Route::post('/api/update-token/{id}', [ProjectsController::class, 'updateToken']);
            Route::post('/api/delete-token/{id}', [ProjectsController::class, 'deleteToken']);
            Route::post('/api/enable_public_access/{id}', [ProjectsController::class, 'enablePublicAPIAccess']);
            Route::post('/api/disable_public_access/{id}', [ProjectsController::class, 'disablePublicAPIAccess']);
            Route::post('/api/update-domain-whitelist/{id}', [ProjectsController::class, 'updateDomainWhitelist']);

            Route::get('/webhooks/{project_id}', [ProjectsController::class, 'webhooks']);
            Route::get('/webhooks/{project_id}/logs/{webhook_id}', [ProjectsController::class, 'webhookLogs']);
            Route::delete('/webhooks/{project_id}/logs/{webhook_id}', [ProjectsController::class, 'deleteWebhookLogs']);
            Route::post('/webhooks/new/{project_id}', [ProjectsController::class, 'newWebhook']);
            Route::post('/webhooks/update/{project_id}', [ProjectsController::class, 'updateWebhook']);
            Route::post('/webhooks/delete/{project_id}', [ProjectsController::class, 'deleteWebhook']);
        });
    });

    Route::prefix('collections')->group(function(){
        Route::get('/project/{id}', [CollectionsController::class, 'project']);
        Route::post('/store/{project_id}', [CollectionsController::class, 'store']);
        Route::post('/update-order/{project_id}', [CollectionsController::class, 'updateOrder']);
        Route::get('/show/{project_id}/{collection_id}', [CollectionsController::class, 'show']);
        Route::post('/update/{project_id}/{collection_id}', [CollectionsController::class, 'update']);
        Route::delete('/delete/{project_id}/{collection_id}', [CollectionsController::class, 'delete']);

        Route::prefix('fields')->group(function(){
            Route::post('/store/{project_id}/{collection_id}', [CollectionFieldsController::class, 'store']);
            Route::post('/update/{project_id}/{collection_id}/{field_id}', [CollectionFieldsController::class, 'update']);
            Route::post('/update-order/{project_id}/{collection_id}', [CollectionFieldsController::class, 'updateOrder']);
            Route::delete('/delete/{project_id}/{collection_id}/{field_id}', [CollectionFieldsController::class, 'delete']);
        });
    });

    Route::prefix('content')->group(function(){
        Route::get('/project/{id}', [ContentController::class, 'project']);

        // Form builder CRUD. The controller lives under Frontend but these
        // endpoints are admin-only and are guarded like the rest of content.
        // NOTE: must be registered BEFORE the dynamic content/{project_id}/
        // {collection_id} routes below, otherwise 'forms' would be captured
        // as a project id and every call would 404.
        Route::get('/forms/{project_id}/{collection_id}', [FormController::class, 'forms']);
        Route::post('/forms/{project_id}/{collection_id}', [FormController::class, 'store']);
        Route::post('/forms/save/{project_id}/{collection_id}/{form_id}', [FormController::class, 'save']);
        Route::delete('/forms/delete/{project_id}/{collection_id}/{form_id}', [FormController::class, 'delete']);

        Route::get('/{project_id}/{collection_id}', [ContentController::class, 'index']);
        Route::get('/new/{project_id}/{collection_id}', [ContentController::class, 'new']);
        Route::post('/store/{project_id}/{collection_id}', [ContentController::class, 'store']);
        Route::get('/edit/{project_id}/{collection_id}/{content_id}', [ContentController::class, 'edit']);
        Route::post('/update/{project_id}/{collection_id}/{content_id}', [ContentController::class, 'update']);
        Route::get('/unpublish/{project_id}/{collection_id}/{content_id}', [ContentController::class, 'unpublish']);
        Route::delete('/move-to-trash/{project_id}/{collection_id}/{content_id}', [ContentController::class, 'moveToTrash']);
        Route::delete('/delete/{project_id}/{collection_id}/{content_id}', [ContentController::class, 'delete']);

        Route::post('/get-selected-records/{project_id}', [ContentController::class, 'getSelectedRecords']);
        Route::post('/get-selected-files/{project_id}', [ContentController::class, 'getSelectedFiles']);

        Route::post('/publish-selected/{project_id}/{collection_id}', [ContentController::class, 'publishSelected']);
        Route::post('/unpublish-selected/{project_id}/{collection_id}', [ContentController::class, 'unPublishSelected']);
        Route::post('/move-to-trash-selected/{project_id}/{collection_id}', [ContentController::class, 'moveToTrashSelected']);
        Route::post('/delete-selected/{project_id}/{collection_id}', [ContentController::class, 'deleteSelected']);
        Route::post('/restore-selected/{project_id}/{collection_id}', [ContentController::class, 'restoreSelected']);
    });

    Route::prefix('media')->group(function(){
        Route::get('/get-files/{project_id}', [MediaLibraryController::class, 'getFiles']);
        Route::post('/upload/{project_id}', [MediaLibraryController::class, 'upload']);
        Route::delete('/delete/{project_id}/{file_id}', [MediaLibraryController::class, 'delete']);
        Route::post('/delete-selected/{project_id}', [MediaLibraryController::class, 'deleteSelected']);
        Route::post('/update/{project_id}/{file_id}', [MediaLibraryController::class, 'update']);
    });
});

// Vue Router SPA catch-all - MUST be last!
Route::middleware('auth:web')->prefix('admin')->group(function(){
    Route::get('/{any?}', function () {
        return view('admin.admin');
    })->where('any', '.*');
});
