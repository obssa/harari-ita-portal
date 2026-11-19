<?php

namespace App\Filament\Support;

use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\People\Models\PersonNote;
use App\Domain\Volunteers\Models\Volunteer;
use Filament\Infolists\Components\TextEntry;

/**
 * The history of a person the Agency deals with, newest first: notes, changes and the messages staff sent
 * (ADR-0048, ADR-0053).
 */
final class PersonHistory
{
    /**
     * @return list<TextEntry>
     */
    public static function entries(Application|ChallengeTeam|Volunteer $record): array
    {
        $notes = $record->notes()->with('user')->get();
        if ($notes->isEmpty()) {
            return [TextEntry::make('no_history')->hiddenLabel()->state(__('people.admin.no_history'))];
        }

        return $notes->values()->map(fn (PersonNote $note, int $i): TextEntry => TextEntry::make("note_{$i}")
            ->label((string) CalendarDate::format(withTime: true)($note->created_at).' · '.($note->user->name ?? __('people.admin.system')).' · '.__("people.note.kind.{$note->kind}"))
            ->state($note->body ?? '—')
            ->extraAttributes(['class' => 'whitespace-pre-line']))->all();
    }
}
