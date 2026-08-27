<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload limits & allowed types
    |--------------------------------------------------------------------------
    |
    | Centralised upload configuration. Values are read from .env at framework
    | boot (config is cached), so business code MUST use config('uploads.*')
    | — never env() — otherwise `php artisan config:cache` makes them return
    | null in production and silently breaks upload validation.
    |
    | MAX_FILE_SIZE caps the largest accepted upload. It is also bounded at
    | runtime by PHP's post_max_size / upload_max_filesize (see
    | AineHelpers::getUploadMaxFileSize), whichever is smallest wins.
    |
    */

    'max_file_size' => env('MAX_FILE_SIZE', '8M'),

    // Comma-separated extensions / MIME types allowed for uploads. Mirrored
    // by the explicit mimes: rule on the upload endpoints; widening these
    // does NOT bypass UploadGuard, which still rejects dangerous types.
    'supported_file_types' => env('SUPPORTED_FILE_TYPES', 'jpg,jpeg,png,bmp,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,tar,gz,mp3,wav,ogg,mp4,webm,mov,avi,json'),

    'supported_file_mimes' => env('SUPPORTED_FILE_MIMES', 'jpg,jpeg,png,bmp,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z,tar,gz,mp3,wav,ogg,mp4,webm,mov,avi,json'),

];