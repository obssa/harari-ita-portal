<?php

namespace App\Domain\Academy\Support;

use App\Domain\Academy\Models\CourseCohort;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which cohorts a member of staff works with (ADR-0028): those who manage cohorts see all of them; instructors see
 * only the cohorts they teach, through the instructor record linked to their account.
 */
final class CohortScope
{
    public static function seesAll(User $actor): bool
    {
        return $actor->can('cohorts.manage');
    }

    public static function covers(User $actor, CourseCohort $cohort): bool
    {
        if (self::seesAll($actor)) {
            return true;
        }

        return $cohort->instructors()->where('user_id', $actor->id)->exists();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query  cohorts, or records with a `cohort` relation
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, User $actor, ?string $through = null): Builder
    {
        if (self::seesAll($actor)) {
            return $query;
        }

        $taught = fn (Builder $cohorts) => $cohorts->whereHas('instructors', fn (Builder $i) => $i->where('user_id', $actor->id));

        return $through === null ? $taught($query) : $query->whereHas($through, $taught);
    }
}
