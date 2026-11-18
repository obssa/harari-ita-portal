<?php

namespace App\Filament\Resources\JudgeAssignments;

use App\Domain\Innovation\Models\JudgeAssignment;
use App\Filament\Resources\JudgeAssignments\Pages\ListJudgeAssignments;
use App\Filament\Resources\JudgeAssignments\Pages\ViewJudgeAssignment;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\SentenceCaseLabels;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "My judging" (ADR-0022): a judge's own assignments only, never anyone else's, and never the team's contact or
 * members, so that the scoring rests on the submission itself.
 */
class JudgeAssignmentResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = JudgeAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'my-judging';

    public static function getModelLabel(): string
    {
        return __('innovation.admin.assignment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.my_judging');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = self::getEloquentQuery()->whereNull('scored_at')->whereNull('conflict_declared_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    /**
     * @return Builder<JudgeAssignment>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<JudgeAssignment> */
        return parent::getEloquentQuery()->where('user_id', (int) auth()->id())
            ->whereHas('team', fn (Builder $q) => $q->whereNull('erased_at'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('team.challenge.translations'))
            ->columns([
                TextColumn::make('team.name')->label(__('innovation.field.team_name'))->limit(40),
                TextColumn::make('team.submission_title')->label(__('innovation.field.submission_title'))->limit(50),
                TextColumn::make('challenge')->label(__('innovation.admin.challenge'))->state(fn (JudgeAssignment $record): string => $record->team->challenge->title()),
                TextColumn::make('state')->label(__('innovation.field.stage'))->badge()
                    ->state(fn (JudgeAssignment $record): string => match (true) {
                        $record->conflict_declared_at !== null => __('innovation.admin.conflict_declared'),
                        $record->scored_at !== null => __('innovation.admin.scored'),
                        default => __('innovation.admin.not_scored'),
                    })
                    ->color(fn (JudgeAssignment $record): string => match (true) {
                        $record->conflict_declared_at !== null => 'gray',
                        $record->scored_at !== null => 'success',
                        default => 'warning',
                    }),
                TextColumn::make('total')->label(__('innovation.field.score'))->placeholder('—'),
            ])
            ->filters([
                Filter::make('open')->label(__('innovation.admin.filter.to_score'))->default()
                    ->query(fn (Builder $query): Builder => $query->whereNull('scored_at')->whereNull('conflict_declared_at')),
            ])
            ->recordActions([ViewAction::make()])
            ->recordUrl(fn (JudgeAssignment $record): string => self::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('innovation.admin.no_assignments'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('innovation.admin.submission'))->schema([
                    TextEntry::make('team.name')->label(__('innovation.field.team_name')),
                    TextEntry::make('team.submission_title')->label(__('innovation.field.submission_title'))->placeholder('—'),
                    TextEntry::make('team.submission_description')->label(__('innovation.field.submission_description'))->placeholder('—')->extraAttributes(['class' => 'whitespace-pre-line']),
                    TextEntry::make('team.submission_links')->label(__('innovation.field.submission_links'))->placeholder('—')->listWithLineBreaks()
                        ->url(fn (?string $state): ?string => $state)->openUrlInNewTab(),
                    TextEntry::make('team.submitted_at')->label(__('innovation.field.submitted_at'))->formatStateUsing(CalendarDate::format(withTime: true))->placeholder('—'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('innovation.admin.your_score'))->schema([
                    TextEntry::make('total')->label(__('innovation.field.score'))->placeholder(__('innovation.admin.not_scored'))
                        ->helperText(fn (JudgeAssignment $record): ?string => $record->scores === null ? null : collect($record->team->challenge->criteriaIn(app()->getLocale()))
                            ->map(fn (array $criterion): string => $criterion['label'].' '.($record->scores[$criterion['key']] ?? '—'))->implode(' · ')),
                    TextEntry::make('comment')->label(__('innovation.field.comment'))->placeholder('—'),
                    TextEntry::make('conflict_note')->label(__('innovation.admin.conflict_declared'))->visible(fn (JudgeAssignment $record): bool => $record->conflict_declared_at !== null),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJudgeAssignments::route('/'),
            'view' => ViewJudgeAssignment::route('/{record}'),
        ];
    }
}
