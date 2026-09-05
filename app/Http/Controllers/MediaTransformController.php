<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/*
|--------------------------------------------------------------------------
| On-the-fly media transform
|--------------------------------------------------------------------------
|
| GET /media/transform/{media_id}?w=&h=&fit=crop|contain&fmt=webp|jpeg|png&q=80
|
| Serves a resized/reformatted version of an image that already lives in the
| media library, computed on demand using Intervention (the same GD pipeline
| the upload path uses to make thumbnails) and cached by the browser for a
| day. Lets frontends request versions tailored to their viewport / format
| without pre-generating every variant.
|
| Public, like the existing /storage/{uuid}/{name} media URLs — the media is
| already served openly. Width/height are bounded to mitigate resize-DoS.
*/

class MediaTransformController extends Controller
{
    /** Extensions we allow transforming (matches the upload thumbnail set). */
    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'];

    /** Resizing strategies. `contain` keeps aspect, `crop` fills exactly. */
    private const FITS = ['crop', 'contain'];

    /** Output formats we allow encoding to. */
    private const FORMATS = ['webp', 'jpeg', 'jpg', 'png'];

    /** Cap on requested width/height to avoid generating huge images. */
    private const MAX_DIM = 4000;

    /** Default output format when none/expired is requested. */
    private const DEFAULT_FORMAT = 'webp';

    public function transform($media_id, Request $request)
    {
        $media = Media::with('project')->find($media_id);
        if (! $media) {
            return $this->errorJson(404, 'Media not found.');
        }

        $type = strtolower((string) $media->type);
        if (! in_array($type, self::IMAGE_TYPES, true)) {
            return $this->errorJson(422, 'Media is not an image and cannot be transformed.');
        }

        $width = $this->dimension($request, 'w');
        $height = $this->dimension($request, 'h');
        if ($width === false || $height === false) {
            return $this->errorJson(422, 'Width and height must be integers between 1 and ' . self::MAX_DIM . '.');
        }

        $fit = in_array($request->get('fit'), self::FITS, true) ? $request->get('fit') : 'contain';

        $format = strtolower((string) $request->get('fmt'));
        if (! in_array($format, self::FORMATS, true)) {
            $format = self::DEFAULT_FORMAT;
        }

        $quality = max(1, min(100, (int) $request->get('q', 80)));

        // Reuse the exact storage path scheme used by MediaController upload
        // (file lives at {storagePath}/{name}, where storagePath is the project
        // UUID for the `public` disk, or `public/{uuid}` for the `local` disk).
        $diskName = $media->disk ?: ($media->project->disk ?? 'local');
        $disk = Storage::disk($diskName);
        $storageSub = $diskName === 'public' ? $media->project->uuid : 'public/' . $media->project->uuid;
        $relPath = $storageSub . '/' . $media->name;

        if (! $disk->exists($relPath)) {
            return $this->errorJson(404, 'Source media file is missing.');
        }

        try {
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($disk->get($relPath));

            if ($fit === 'crop' && $width && $height) {
                $image->cover($width, $height);
            } elseif ($width || $height) {
                $image->scale(width: $width, height: $height);
            }

            $encoded = match ($format) {
                'png' => $image->toPng(),
                'jpeg', 'jpg' => $image->toJpeg($quality),
                default => $image->toWebp($quality),
            };

            return response((string) $encoded)
                ->header('Content-Type', $encoded->mimetype())
                ->header('Cache-Control', 'public, max-age=86400, immutable');
        } catch (\Throwable $e) {
            Log::error('Media transform failed: ' . $e->getMessage());
            return $this->errorJson(500, 'Failed to process the image.');
        }
    }

    private function dimension(Request $request, string $key)
    {
        if (! $request->has($key)) {
            return null;
        }

        $val = $request->get($key);
        if (! is_numeric($val)) {
            return false;
        }

        $i = (int) $val;
        return ($i >= 1 && $i <= self::MAX_DIM) ? $i : false;
    }

    private function errorJson(int $code, string $message)
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => null,
        ], $code);
    }
}