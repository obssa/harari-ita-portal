<?php

namespace App\Domain\Innovation\Queries;

use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Queries\PublicDirectory;
use Carbon\CarbonImmutable;

/**
 * What innovators can take part in now (ADR-0023): programme cohorts taking applications, challenges taking
 * teams, and calls for proposals and tenders (ADR-0032), the nearest deadline first. When nothing is open, the next call to open.
 */
final class OpenCalls
{
    /**
     * @return list<array{kind: string, title: string, summary: ?string, url: string, date: CarbonImmutable, open: bool, locale: string}>
     */
    public static function for(string $locale, int $limit = 3, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $open = [];
        $next = [];

        foreach (PublicDirectory::visible(Programme::query(), $locale)->with('cohorts')->get() as $programme) {
            $shown = PublicDirectory::shown($programme, $locale);
            if ($shown === null) {
                continue;
            }
            $call = fn (CarbonImmutable $date, bool $isOpen): array => [
                'kind' => 'programme',
                'title' => (string) $shown->getAttribute('title'),
                'summary' => $shown->getAttribute('summary'),
                'url' => route('programmes.show', ['locale' => $shown->locale, 'slug' => $shown->slug]),
                'date' => $date,
                'open' => $isOpen,
                'locale' => $shown->locale,
            ];
            if ($cohort = $programme->openCohort($now)) {
                $open[] = $call($cohort->closes_at, true);
            } elseif ($cohort = $programme->nextCohort($now)) {
                $next[] = $call($cohort->opens_at, false);
            }
        }

        foreach (PublicDirectory::visible(Challenge::query(), $locale)->where('registration_closes_at', '>', $now)->get() as $challenge) {
            $shown = PublicDirectory::shown($challenge, $locale);
            if ($shown === null) {
                continue;
            }
            $isOpen = $challenge->phase($now) === Challenge::PHASE_REGISTRATION;
            $call = [
                'kind' => 'challenge',
                'title' => (string) $shown->getAttribute('title'),
                'summary' => $shown->getAttribute('summary'),
                'url' => route('challenges.show', ['locale' => $shown->locale, 'slug' => $shown->slug]),
                'date' => $isOpen ? $challenge->registration_closes_at : $challenge->registration_opens_at,
                'open' => $isOpen,
                'locale' => $shown->locale,
            ];
            if ($isOpen) {
                $open[] = $call;
            } else {
                $next[] = $call;
            }
        }

        // ADR-0032: calls for proposals and tenders open now (or opening) join the innovators' calls.
        $opportunities = PublicDirectory::visible(Opportunity::query(), $locale)->current()
            ->whereIn('type', [OpportunityType::Call->value, OpportunityType::Tender->value])->orderBy('closes_at')->limit(12)->get();
        foreach ($opportunities as $opportunity) {
            $shown = PublicDirectory::shown($opportunity, $locale);
            if ($shown === null) {
                continue;
            }
            $isOpen = $opportunity->isOpen($now);
            $call = [
                'kind' => $opportunity->type->value,
                'title' => (string) $shown->getAttribute('title'),
                'summary' => $shown->getAttribute('summary'),
                'url' => route('opportunities.show', ['locale' => $shown->locale, 'slug' => $shown->slug]),
                'date' => $isOpen || $opportunity->opens_at === null ? $opportunity->closes_at : $opportunity->opens_at,
                'open' => $isOpen,
                'locale' => $shown->locale,
            ];
            if ($isOpen) {
                $open[] = $call;
            } else {
                $next[] = $call;
            }
        }

        $byDate = fn (array $a, array $b): int => $a['date'] <=> $b['date'];
        usort($open, $byDate);
        usort($next, $byDate);

        return array_slice($open !== [] ? $open : array_slice($next, 0, 1), 0, $limit);
    }
}
