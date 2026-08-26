<?php

namespace App\Aine;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Defense-in-depth layer for file uploads.
 *
 * The upload endpoints already whitelist MIME types; this guard adds an
 * explicit deny-list on top so that dangerous file types can never sneak in
 * through a misconfigured or widened whitelist, and double-checks the
 * detected content MIME (catches e.g. a .jpg polyglot that actually contains
 * PHP).
 */
class UploadGuard
{
    /** File extensions that must never be stored. */
    public const DANGEROUS_EXTENSIONS = [
        'php', 'phar', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'htaccess', 'html', 'htm', 'xhtml', 'shtml', 'js', 'mjs', 'sh',
        'bash', 'csh', 'zsh', 'svg', 'svgz', 'swf', 'exe', 'dll', 'bat',
        'cmd', 'com', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp', 'vbs',
    ];

    /** Content MIME types that are never acceptable for stored uploads. */
    public const DANGEROUS_MIMES = [
        'text/html', 'application/xhtml+xml',
        'application/x-php', 'text/x-php', 'application/php',
        'application/x-httpd-php', 'application/x-httpd-php-source',
        'application/javascript', 'text/javascript', 'application/x-sh',
        'text/x-shellscript', 'image/svg+xml',
    ];

    /**
     * Abort the request when the uploaded file is dangerous.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function rejectDangerous(?UploadedFile $file): void
    {
        if ($file === null) {
            return;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['The file type is not allowed.'],
            ]);
        }

        $mime = strtolower((string) $file->getMimeType());

        foreach (self::DANGEROUS_MIMES as $dangerous) {
            if (str_starts_with($mime, $dangerous)) {
                throw ValidationException::withMessages([
                    'file' => ['The file type is not allowed.'],
                ]);
            }
        }

        // Content marker scan: catches polyglots whose content is clearly
        // executable even when fileinfo reports a generic MIME (PHP/HTML
        // fragments are frequently detected as text/plain).
        $handle = $file->openFile('r');
        $head = (string) $handle->fread(1024 * 1024);
        unset($handle);

        if (preg_match('/<\?(php|=|\s)/i', $head)
            || preg_match('/<script[\s>]/i', $head)) {
            throw ValidationException::withMessages([
                'file' => ['The file type is not allowed.'],
            ]);
        }
    }
}
