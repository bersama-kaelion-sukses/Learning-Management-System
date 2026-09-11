<?php

if (!function_exists('json_lang')) {
    function json_lang(string $text): string
    {
        // static $cache = [];

        // Default language
        $locale = session('locale', 'en');

        // Load JSON per locale (cache per request)
        if (!isset($cache[$locale])) {
            $path = resource_path("lang/{$locale}.json");
            $cache[$locale] = file_exists($path)
                ? json_decode(file_get_contents($path), true)
                : [];
        }

        // EN = original text
        if ($locale === 'en') {
            return $text;
        }

        // Translate if exists, otherwise fallback to EN text
        return $cache[$locale][$text] ?? $text;
    }
}

if (!function_exists('json_lang_all')) {
    function json_lang_all(): array 
    {
        $locale = session('locale', 'en');

        $path = resource_path("lang/{$locale}.json");
        if (!file_exists($path)) return[];

        return json_decode(file_get_contents($path), true) ?? [];
    }
}