@php
    $isArabic = app()->getLocale() === 'ar';
    $logo = $isArabic ? 'darin-logo-rtl.png' : 'darin-logo-ltr.png';
    $logoPath = public_path($logo);
    $appUrl = config('app.url', '');

    // Prefer embedding the image (works even when APP_URL is localhost).
    // Fallback to absolute URL (needed when embedding isn't available).
    $src = null;
    try {
        if (isset($message) && is_object($message) && method_exists($message, 'embed') && file_exists($logoPath)) {
            $src = $message->embed($logoPath);
        }
    } catch (\Throwable $e) {
        $src = null;
    }

    if (!$src) {
        $src = rtrim($appUrl, '/') . '/' . $logo;
    }
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $src }}" class="logo" alt="{{ config('app.name') }}" style="height: 40px; max-height: 40px;">
</a>
</td>
</tr>

