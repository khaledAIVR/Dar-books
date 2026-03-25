<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\Flysystem\Util;

class VoyagerAssetsController
{
    public function assets(Request $request)
    {
        try {
            $relative = Util::normalizeRelativePath(urldecode((string) $request->query('path', '')));
        } catch (\LogicException $e) {
            abort(404);
        }

        if ($relative === '' || Str::startsWith($relative, ['../', '..\\'])) {
            abort(404);
        }

        $base = base_path('vendor/tcg/voyager/publishable/assets');
        $path = $base . '/' . ltrim($relative, '/');

        if (!File::exists($path)) {
            return response('', 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $this->mimeForExtension($ext) ?: File::mimeType($path);

        $contents = File::get($path);

        // Make CSS references absolute and cache-busted so fonts always load correctly.
        if ($ext === 'css') {
            $ver = (string) @filemtime($path);
            $contents = str_replace('voyager-assets?path=', '/admin/voyager-assets?path=', $contents);
            // Add a cache-busting param to font URLs
            $contents = preg_replace(
                '/(\\/admin\\/voyager-assets\\?path=fonts\\/[a-zA-Z0-9_\\/-]+\\.(?:eot|woff2?|ttf|svg))(\\b)/',
                '$1&v='.$ver.'$2',
                $contents
            );

            // Ensure Voyager icons use the Voyager font-family (some builds miss this rule).
            if (strpos($contents, '[class^="voyager-"]:before') === false) {
                $contents .= "\n\n/* Icon font fix */\n"
                    . "[class^=\"voyager-\"]:before,\n"
                    . "[class*=\" voyager-\"]:before {\n"
                    . "  font-family: \"voyager\" !important;\n"
                    . "  font-style: normal !important;\n"
                    . "  font-weight: normal !important;\n"
                    . "  font-variant: normal !important;\n"
                    . "  text-transform: none !important;\n"
                    . "  speak: none;\n"
                    . "  line-height: 1;\n"
                    . "  -webkit-font-smoothing: antialiased;\n"
                    . "  -moz-osx-font-smoothing: grayscale;\n"
                    . "}\n";
            }
        }

        $response = response($contents, 200, ['Content-Type' => $mime]);

        // Fonts/CSS/JS often get aggressively cached by browsers.
        // When icons are broken, it is usually because the font was cached as missing.
        // Use no-cache for these asset types so a normal refresh picks up fixes.
        if (in_array($ext, ['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg'], true)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        } else {
            $response->setSharedMaxAge(31536000);
            $response->setMaxAge(31536000);
            $response->setExpires(new \DateTime('+1 year'));
        }

        return $response;
    }

    private function mimeForExtension(string $ext): ?string
    {
        // Ensure fonts are served with proper MIME types (icons otherwise render as letters in some browsers)
        switch ($ext) {
            case 'js':
                return 'text/javascript';
            case 'css':
                return 'text/css';
            case 'woff':
                return 'font/woff';
            case 'woff2':
                return 'font/woff2';
            case 'ttf':
                return 'font/ttf';
            case 'eot':
                return 'application/vnd.ms-fontobject';
            case 'svg':
                return 'image/svg+xml';
            case 'png':
                return 'image/png';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'gif':
                return 'image/gif';
            default:
                return null;
        }
    }
}

