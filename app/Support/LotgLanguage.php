<?php

namespace App\Support;

class LotgLanguage
{
    /**
     * @return array<string, string>
     */
    public static function supported(): array
    {
        return [
            'id' => 'Indonesia',
            'en' => 'English',
        ];
    }

    public static function default(): string
    {
        return self::normalize(config('app.locale', 'id'));
    }

    public static function normalize(?string $language): string
    {
        $language = strtolower((string) $language);

        return array_key_exists($language, self::supported()) ? $language : self::defaultFallback();
    }

    public static function label(string $language): string
    {
        return self::supported()[$language] ?? strtoupper($language);
    }

    protected static function defaultFallback(): string
    {
        return 'id';
    }
}
