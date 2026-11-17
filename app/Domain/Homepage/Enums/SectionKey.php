<?php

namespace App\Domain\Homepage\Enums;

/**
 * The homepage sections the builder offers (PLAN.md §4 Homepage). Events,
 * projects, the social hub, video, gallery and newsletter sections join
 * when their modules exist.
 */
enum SectionKey: string
{
    case Ticker = 'ticker';
    case Hero = 'hero';
    /** The top services (ADR-0016): featured first, then by rank. */
    case Services = 'services';
    /** Open calls, innovation stories and figures (ADR-0023). */
    case Innovation = 'innovation';
    case Leadership = 'leadership';
    case LatestNews = 'latest_news';
    case Press = 'press';
    case Counters = 'counters';
    case Values = 'values';
    case Pillars = 'pillars';
    case Partners = 'partners';
    case Gallery = 'gallery';
    case Video = 'video';
    case Events = 'events';
    case Projects = 'projects';
    case Social = 'social';

    public function label(?string $locale = null): string
    {
        return __("homepage.section.{$this->value}", [], $locale);
    }

    /** Whether the section starts switched on. */
    public function enabledByDefault(): bool
    {
        return ! in_array($this, [self::Ticker, self::Leadership, self::Counters, self::Partners], true);
    }

    /** Most posts (or other records) the section may pick or show. */
    public function maxItems(): int
    {
        return match ($this) {
            self::Ticker, self::Hero, self::Press => 5,
            self::LatestNews => 12,
            self::Counters, self::Values => 8,
            self::Pillars => 4,
            self::Events => 6,
            self::Projects => 6,
            self::Services => 6,
            self::Innovation => 3,
            self::Social => 12,
            self::Partners => 12,
            self::Gallery => 8,
            self::Leadership, self::Video => 1,
        };
    }

    /** How many items show when no limit is set. */
    public function defaultLimit(): int
    {
        return match ($this) {
            self::LatestNews => 6,
            self::Pillars => 2,
            self::Events => 3,
            self::Projects => 3,
            self::Social => 6,
            default => $this->maxItems(),
        };
    }

    /** Sections where editors may hand-pick posts. */
    public function takesCuratedPosts(): bool
    {
        return in_array($this, [self::Ticker, self::Hero], true);
    }
}
