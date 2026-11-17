<?php

namespace App\Filament\Resources\ChallengeTeams;

use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\JudgeAssignment;
use App\Filament\Resources\ChallengeTeams\Pages\ListChallengeTeams;
use App\Filament\Resources\ChallengeTeams\Pages\ViewChallengeTeam;
use App\Filament\Support\CalendarDate;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Teams in challenges (ADR-0022): the submissions, the judges' scores and the ranking. Staff read them here; teams
 * register and submit on the public site.
 */
class ChallengeTeamResource extends Resource
{
    protected static ?string $model = ChallengeTeam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('innovation.admin.team');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.teams');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('erased_at')->with('challenge.translations')->withCount(['assignments as judges_count' => fn (Builder $q) => $q->whereNull('conflict_declared_at')]))
            ->columns([
                TextColumn::make('reference')->label(__('support.reference'))->fontFamily('mono')->searchable()->copyable(),
                TextColumn::make('name')->label(__('innovation.field.team_name'))->searchable()->limit(40),
                TextColumn::make('challenge')->label(__('innovation.admin.challenge'))->state(fn (ChallengeTeam $record): string => $record->challenge->title()),
                TextColumn::make('submitted_at')->label(__('innovation.admin.submission'))->formatStateUsing(CalendarDate::format())->placeholder(__('innovation.admin.not_submitted')),
                TextColumn::make('judges_count')->label(__('innovation.admin.judges')),
                TextColumn::make('score')->label(__('innovation.field.score'))->placeholder('—')->sortable(),
                TextColumn::make('rank')->label(__('innovation.field.rank'))->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('challenge_id')->label(__('innovation.admin.challenge'))
                    ->options(fn (): array => Challenge::query()->with('translations')->get()->mapWithKeys(fn (Challenge $c): array => [$c->id => $c->title()])->all()),
            ])
            ->recordActions([ViewAction::make()])
            ->recordUrl(fn (ChallengeTeam $record): string => self::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('innovation.admin.no_teams'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('innovation.admin.submission'))->schema([
                    TextEntry::make('submission_title')->label(__('innovation.field.submission_title'))->placeholder(__('innovation.admin.not_submitted')),
                    TextEntry::make('submission_description')->label(__('innovation.field.submission_description'))->placeholder('—')->extraAttributes(['class' => 'whitespace-pre-line']),
                    TextEntry::make('submission_links')->label(__('innovation.field.submission_links'))->placeholder('—')->listWithLineBreaks()
                        ->url(fn (?string $state): ?string => $state)->openUrlInNewTab(),
                    TextEntry::make('submitted_at')->label(__('innovation.field.submitted_at'))->formatStateUsing(CalendarDate::format(withTime: true))->placeholder('—'),
                ]),
                Section::make(__('innovation.admin.judging'))->schema(fn (?ChallengeTeam $record): array => $record === null ? [] : self::judgeEntries($record)),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('innovation.admin.result'))->schema([
                    TextEntry::make('reference')->label(__('support.reference'))->fontFamily('mono')->copyable(),
                    TextEntry::make('score')->label(__('innovation.field.score'))->placeholder('—'),
                    TextEntry::make('rank')->label(__('innovation.field.rank'))->placeholder('—'),
                    TextEntry::make('award')->label(__('innovation.field.award'))->placeholder('—'),
                    IconEntry::make('show_in_results')->label(__('innovation.field.show_in_results'))->boolean(),
                ]),
                Section::make(__('innovation.admin.contact'))->schema([
                    TextEntry::make('name')->label(__('innovation.field.team_name')),
                    TextEntry::make('contact_name')->label(__('support.field.name'))->placeholder('—'),
                    TextEntry::make('contact_email')->label(__('support.field.email'))->copyable()->placeholder('—'),
                    TextEntry::make('contact_phone')->label(__('support.field.phone'))->copyable()->placeholder('—'),
                    TextEntry::make('members')->label(__('innovation.field.members'))->placeholder('—')->extraAttributes(['class' => 'whitespace-pre-line']),
                    TextEntry::make('created_at')->label(__('innovation.admin.registered_at'))->formatStateUsing(CalendarDate::format(withTime: true)),
                ]),
            ]),
        ]);
    }

    /**
     * Each judge's weighted total, or the conflict they declared.
     *
     * @return list<TextEntry>
     */
    public static function judgeEntries(ChallengeTeam $record): array
    {
        $entries = [];
        foreach ($record->assignments()->with('judge')->get() as $assignment) {
            /** @var JudgeAssignment $assignment */
            $entries[] = TextEntry::make("judge_{$assignment->id}")
                ->label($assignment->judge->name)
                ->state(match (true) {
                    $assignment->conflict_declared_at !== null => __('innovation.admin.conflict_declared'),
                    $assignment->total !== null => $assignment->total,
                    default => __('innovation.admin.not_scored'),
                })
                ->helperText($assignment->conflict_note ?? $assignment->comment);
        }

        return $entries === [] ? [TextEntry::make('no_judges')->hiddenLabel()->state(__('innovation.admin.no_judges'))] : $entries;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChallengeTeams::route('/'),
            'view' => ViewChallengeTeam::route('/{record}'),
        ];
    }
}
