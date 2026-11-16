<?php

namespace App\Domain\Access\Enums;

/**
 * Narrows a role to a slice of the portal (PLAN.md §8): one office, one
 * pillar, or one language.
 */
enum ScopeType: string
{
    case Office = 'office';
    case Pillar = 'pillar';
    case Language = 'language';

    public function label(): string
    {
        return __('access.scope.'.$this->value);
    }
}
