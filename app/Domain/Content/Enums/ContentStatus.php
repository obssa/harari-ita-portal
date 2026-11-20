<?php

namespace App\Domain\Content\Enums;

/**
 * Status of one language of a record (PLAN.md §6: each translation has its
 * own status and publish date, so Amharic can go live before Harari).
 * Transitions and scheduling are formalised in step 2.3 (ADR-0013). A language waiting for its translation is
 * a draft; machine translation is marked on the translation itself (ADR-0035), so there is no "translating"
 * status (ADR-0054: nothing could set it).
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __("content.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'warning',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }

    /** Needs `posts.publish` (or the module's publish permission) to set. */
    public function needsPublisher(): bool
    {
        return in_array($this, [self::Scheduled, self::Published, self::Archived], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(bool $canPublish): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($canPublish || ! $case->needsPublisher()) {
                $options[$case->value] = $case->label();
            }
        }

        return $options;
    }
}
