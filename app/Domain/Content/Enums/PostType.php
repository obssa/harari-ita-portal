<?php

namespace App\Domain\Content\Enums;

/**
 * Kinds of post (PLAN.md §4 News & Media, §10).
 */
enum PostType: string
{
    case News = 'news';
    case PressRelease = 'press_release';
    case Statement = 'statement';
    case Speech = 'speech';
    case Announcement = 'announcement';
    case Advisory = 'advisory';

    public function label(?string $locale = null): string
    {
        return __("content.post_type.{$this->value}", [], $locale);
    }

    /** The badge colour of the type on cards and pages (ADR-0062). */
    public function tone(): string
    {
        return match ($this) {
            self::News => 'primary',
            self::PressRelease => 'ink',
            self::Statement => 'cyan',
            self::Speech => 'ruby',
            self::Announcement => 'saffron',
            self::Advisory => 'ruby',
        };
    }

    /** The public page of a post of this type (ADR-0034): advisories have their own section. */
    public function route(): string
    {
        return $this === self::Advisory ? 'advisories.show' : 'news.show';
    }

    /**
     * The types listed on the news pages: all but advisories.
     *
     * @return list<self>
     */
    public static function news(): array
    {
        return [self::News, self::PressRelease, self::Statement, self::Speech, self::Announcement];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
