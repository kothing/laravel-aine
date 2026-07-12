<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Frontend\FormController;

Route::get('/', function () {
    return view('frontend.app');
});

Route::get('/settings', function () {
    return view('frontend.app');
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

Route::get('storage/{dir}/{file}', function($dir, $file){
    $path = 'public/' . $dir . '/' . $file;

    if(Storage::disk('local')->exists($path)){
        return Storage::response($path);
    }

    abort(404);
});

Route::get('storage/{dir}/thumbnails/{file}', function($dir, $file){
    $path = 'public/' . $dir . '/thumbnails/' . $file;

    if(Storage::disk('local')->exists($path)){
        return Storage::response($path);
    }

    abort(404);
});

Route::get('forms/preview/{form_uuid}', [FormController::class, 'showPreview']);
Route::get('forms/{form_uuid}', [FormController::class, 'showEmbeded']);
Route::post('forms/{form_uuid}', [FormController::class, 'getEmbeded']);
Route::post('forms/submit/{form_uuid}', [FormController::class, 'submit']);
Route::post('forms/{form_uuid}/upload', [FormController::class, 'upload']);

require __DIR__.'/auth.php';