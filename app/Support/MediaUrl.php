<?php

namespace App\Support;

class MediaUrl
{
    public static function resolve(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim(self::baseUrl(), '/').'/'.ltrim($url, '/');
    }

    private static function baseUrl(): string
    {
        if (! app()->runningInConsole() && request()->hasHeader('Host')) {
            return request()->getSchemeAndHttpHost();
        }

        return rtrim((string) config('app.url'), '/');
    }
}
