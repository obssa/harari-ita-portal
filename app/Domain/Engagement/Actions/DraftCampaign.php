<?php

namespace App\Domain\Engagement\Actions;

use App\Domain\Content\Enums\PostType;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Translation\Support\Locales;
use App\Models\User;
use App\Services\Calendar\EthiopianCalendar;
use Illuminate\Validation\ValidationException;

/**
 * A campaign drafted from published content (ADR-0035): an advisory goes to subscribers of the "advisories" topic,
 * an opportunity to those of "opportunities", by email, with the text of each language that is live. It is only a
 * draft: staff read it, send themselves a test and send it as any campaign (ADR-0052).
 */
final class DraftCampaign
{
    public function __construct(private readonly SaveCampaign $save, private readonly EthiopianCalendar $calendar) {}

    public static function supports(mixed $record): bool
    {
        return ($record instanceof Post && $record->type === PostType::Advisory) || $record instanceof Opportunity;
    }

    public function handle(Post|Opportunity $record, User $actor): Campaign
    {
        abort_unless($actor->can('campaigns.manage'), 403);
        $record->loadMissing('translations');

        $content = [];
        foreach (Locales::all() as $locale) {
            $text = $record instanceof Post ? $this->advisory($record, $locale) : $this->opportunity($record, $locale);
            if ($text !== null) {
                $content[$locale] = $text;
            }
        }
        if ($content === []) {
            throw ValidationException::withMessages(['record' => __('campaigns.draft.not_live')]);
        }

        $topic = $record instanceof Post ? 'advisories' : 'opportunities';

        return $this->save->handle(new Campaign, [
            'name' => mb_substr(__('campaigns.draft.name', ['title' => $record->title()]), 0, 120),
            'channel' => Campaign::EMAIL,
            'audience' => ['type' => 'subscribers', 'topics' => [$topic]],
            'content' => $content,
        ], $actor);
    }

    /**
     * @return array{subject: string, body: string}|null
     */
    private function advisory(Post $post, string $locale): ?array
    {
        $translation = $post->translations->firstWhere('locale', $locale);
        if (! $translation instanceof PostTranslation || ! $translation->isLive()) {
            return null;
        }
        $severity = $post->severity?->label($locale);
        $lines = [];
        $lines[] = '**'.($severity ?? __('content.post_type.advisory', [], $locale)).'** · '.$this->calendar->format($translation->published_at ?? now(), $locale);
        if (filled($translation->excerpt)) {
            $lines[] = (string) $translation->excerpt;
        }
        if ($translation->affectedList() !== []) {
            $lines[] = '**'.__('advisories.affected', [], $locale).'**'."\n\n".implode("\n", array_map(fn (string $line): string => '- '.self::plain($line), $translation->affectedList()));
        }
        if ($translation->actionList() !== []) {
            $steps = [];
            foreach ($translation->actionList() as $i => $step) {
                $steps[] = ($i + 1).'. '.self::plain($step);
            }
            $lines[] = '**'.__('advisories.actions', [], $locale).'**'."\n\n".implode("\n", $steps);
        }
        $lines[] = '['.__('campaigns.draft.read_advisory', [], $locale).']('.route('advisories.show', ['locale' => $locale, 'slug' => $translation->slug]).')';

        return ['subject' => mb_substr(($severity !== null ? "{$severity}: " : '').$translation->title, 0, 150), 'body' => implode("\n\n", $lines)];
    }

    /**
     * @return array{subject: string, body: string}|null
     */
    private function opportunity(Opportunity $opportunity, string $locale): ?array
    {
        $translation = $opportunity->translations->firstWhere('locale', $locale);
        if (! $translation instanceof OpportunityTranslation || ! PublicDirectory::live($translation)) {
            return null;
        }
        $type = $opportunity->type->label($locale);
        $lines = [];
        $lines[] = '**'.$type.'**'.(filled($opportunity->reference) ? ' · '.$opportunity->reference : '');
        if (filled($translation->summary)) {
            $lines[] = (string) $translation->summary;
        }
        $lines[] = '**'.__('opportunities.field.closes_at', [], $locale).':** '.$this->calendar->format($opportunity->closes_at, $locale, withTime: true);
        $lines[] = '['.__('campaigns.draft.read_opportunity', [], $locale).']('.route('opportunities.show', ['locale' => $locale, 'slug' => $translation->slug]).')';

        return ['subject' => mb_substr("{$type}: {$translation->title}", 0, 150), 'body' => implode("\n\n", $lines)];
    }

    /** One line of text, kept from turning into Markdown of its own. */
    private static function plain(string $line): string
    {
        return addcslashes($line, '*_[]`<>#');
    }
}
