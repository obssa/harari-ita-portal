<?php

namespace App\Domain\Status\Support;

use App\Domain\Translation\Support\Locales;

/**
 * Short texts stored per language in one JSON column (status names, incident titles and updates, ADR-0018): the
 * reader's language, then its fallbacks, then any language that has words.
 */
final class LocalisedText
{
    /**
     * @param  array<string, mixed>|null  $texts
     */
    public static function pick(?array $texts, ?string $locale = null): string
    {
        $texts = array_filter(array_map(fn (mixed $text): string => trim((string) $text), $texts ?? []), fn (string $text): bool => $text !== '');
        foreach (Locales::fallbackChain($locale ?? app()->getLocale()) as $candidate) {
            if (isset($texts[$candidate])) {
                return $texts[$candidate];
            }
        }

        return (string) (reset($texts) ?: '');
    }

    /**
     * Keeps the supported languages that have words, trimmed and shortened.
     *
     * @return array<string, string>
     */
    public static function clean(mixed $texts, int $max): array
    {
        $clean = [];
        foreach (Locales::all() as $locale) {
            $text = trim((string) (is_array($texts) ? ($texts[$locale] ?? '') : ''));
            if ($text !== '') {
                $clean[$locale] = mb_substr($text, 0, $max);
            }
        }

        return $clean;
    }
}
