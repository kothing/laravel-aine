<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Frontend\FormController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\MediaTransformController;
use App\Http\Controllers\HealthController;

Route::get('/', function () {
    return view('frontend.app');
});

Route::get('/settings', function () {
    $setting = App\Models\Setting::first();

    return response()->json([
        'name' => $setting->name ?? config('app.name', 'Aine'),
        'description' => $setting->description ?? '',
        'version' => $setting->version ?? config('app.version', '0.0.1'),
    ]);
});

Route::get('uploads/{dir}/{file}', function($dir, $file){
    $path = 'public/' . $dir . '/' . $file;

    if(Storage::disk('local')->exists($path)){
        return Storage::response($path);
    }

    abort(404);
});

Route::get('uploads/thumb/{dir}/{file}', function($dir, $file){
    $path = 'public/' . $dir . '/thumbnails/' . $file;

    if(Storage::disk('local')->exists($path)){
        return Storage::response($path);
    }

    abort(404);
});

Route::get('storage/{path}', function (string $path) {
    $file = storage_path('app/public/'.$path);

    $real = realpath($file);
    $base = realpath(storage_path('app/public'));

    // Must resolve to a real file inside storage/app/public.
    if ($real === false || $base === false
        || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR)
        || ! is_file($real)) {
        abort(404);
    }

    return response()->file($real, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*');

Route::get('forms/preview/{form_uuid}', [FormController::class, 'showPreview']);
Route::get('forms/{form_uuid}', [FormController::class, 'showEmbeded']);
Route::post('forms/{form_uuid}', [FormController::class, 'getEmbeded']);
Route::post('forms/submit/{form_uuid}', [FormController::class, 'submit'])->middleware('throttle:form-submit');
Route::post('forms/{form_uuid}/upload', [FormController::class, 'upload'])->middleware('throttle:form-upload');
Route::get('preview/{token}', [PreviewController::class, 'show'])->middleware('throttle:preview');
Route::get('media/transform/{media_id}', [MediaTransformController::class, 'transform']);
Route::get('health', [HealthController::class, 'health']);
Route::get('up', [HealthController::class, 'up']);

Route::get('/{any}', function () {
    return view('frontend.app');
})->where('any', '^(?!admin|admin-api|api|install|update|storage|uploads|forms|settings|login|register|logout|forgot-password|reset-password|verify-email|confirm-password|email|_ignition|documents).*');