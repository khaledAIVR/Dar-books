<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicStorageUrl
{
    /**
     * Public URL for a file on the public disk (uses APP_URL + /storage/...).
     * Paths under public/media/... are versioned static assets: use asset() (no storage:link).
     * If the DB still has a full URL from another environment ending in /storage/..., rewrite to this app so
     * uploaded volume files and Railway APP_URL stay in sync.
     *
     * @param  string|null  $path
     */
    public static function url($path)
    {
        if ($path === null || $path === '') {
            return '';
        }

        $path = trim((string) $path);

        if (preg_match('#^https?://#i', $path)) {
            if (preg_match('#/storage/(.+)$#', $path, $m)) {
                return Storage::disk('public')->url(ltrim(rawurldecode($m[1]), '/'));
            }

            if (preg_match('#/media/(.+)$#', $path, $m)) {
                return asset('media/'.ltrim(rawurldecode($m[1]), '/'));
            }

            return $path;
        }

        if (Str::startsWith($path, 'media/')) {
            return asset($path);
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
}
