<?php

namespace App\Filament\Resources\Applications;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Programme;
use App\Filament\Resources\Applications\Pages\ApplicationBoard;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\PersonHistory;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Applications to programmes (ADR-0021): to screen, interview, score and decide.
 */
class ApplicationResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getModelLabel(): string
    {
        return __('innovation.admin.application');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.applications');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Application::query()->where('stage', ApplicationStage::Received->value)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['programme.translations', 'cohort']))
            ->columns([
                TextColumn::make('reference')->label(__('support.reference'))->fontFamily('mono')->searchable()->copyable(),
                TextColumn::make('team_name')->label(__('innovation.field.team_name'))->placeholder('—')->limit(40),
                TextColumn::make('programme')->label(__('innovation.admin.programme'))->state(fn (Application $record): string => $record->programme->title())
                    ->description(fn (Application $record): string => $record->cohort->name),
                TextColumn::make('stage')->label(__('innovation.field.stage'))->badge()
                    ->formatStateUsing(fn (ApplicationStage $state): string => $state->label())->color(fn (ApplicationStage $state): string => $state->color()),
                TextColumn::make('score')->label(__('innovation.field.score'))->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label(__('innovation.field.submitted_at'))->formatStateUsing(CalendarDate::format())->sortable(),
            ])
            ->filters([
                Filter::make('open')->label(__('innovation.admin.filter.open'))->default()
                    ->query(fn (Builder $query): Builder => $query->whereIn('stage', [ApplicationStage::Received->value, ApplicationStage::Screening->value, ApplicationStage::Interview->value])),
                SelectFilter::make('stage')->label(__('innovation.field.stage'))->options(ApplicationStage::options()),
                SelectFilter::make('programme_id')->label(__('innovation.admin.programme'))
                    ->options(fn (): array => Programme::query()->with('translations')->get()->mapWithKeys(fn (Programme $p): array => [$p->id => $p->title()])->all()),
            ])
            ->recordActions([ViewAction::make()])
            ->recordUrl(fn (Application $record): string => self::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('innovation.admin.no_applications'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('innovation.admin.answers'))->schema(fn (?Application $record): array => $record === null ? [] : self::answerEntries($record)),
                Section::make(__('people.admin.history'))->collapsible()->schema(fn (?Application $record): array => $record === null ? [] : PersonHistory::entries($record)),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('innovation.admin.handling'))->schema([
                    TextEntry::make('reference')->label(__('support.reference'))->fontFamily('mono')->copyable(),
                    TextEntry::make('stage')->label(__('innovation.field.stage'))->badge()
                        ->formatStateUsing(fn (ApplicationStage $state): string => $state->label())->color(fn (ApplicationStage $state): string => $state->color()),
                    TextEntry::make('score')->label(__('innovation.field.score'))->placeholder('—')
                        ->helperText(fn (Application $record): ?string => $record->scores === null ? null : collect($record->scores)
                            ->map(fn (int $value, string $criterion): string => __("innovation.criterion.{$criterion}").' '.$value)->implode(' · ')),
                    TextEntry::make('decision_note')->label(__('innovation.admin.decision_note'))->placeholder('—'),
                    TextEntry::make('cohort.name')->label(__('innovation.field.cohort_name')),
                ]),
                Section::make(__('innovation.admin.applicant'))->schema([
                    TextEntry::make('applicant_name')->label(__('support.field.name'))->placeholder('—'),
                    TextEntry::make('applicant_email')->label(__('support.field.email'))->copyable()->placeholder('—'),
                    TextEntry::make('applicant_phone')->label(__('support.field.phone'))->copyable()->placeholder('—'),
                    TextEntry::make('team_name')->label(__('innovation.field.team_name'))->placeholder('—'),
                    TextEntry::make('created_at')->label(__('innovation.field.submitted_at'))->formatStateUsing(CalendarDate::format(withTime: true)),
                ]),
            ]),
        ]);
    }

    /**
     * @return list<TextEntry>
     */
    public static function answerEntries(Application $record): array
    {
        $entries = [];
        $answers = $record->answers ?? [];
        foreach ($record->programme->questionsIn(app()->getLocale()) as $question) {
            $entries[] = TextEntry::make("answer_{$question['key']}")
                ->label($question['label'])
                ->state($answers[$question['key']] ?? null)
                ->placeholder('—')
                ->extraAttributes(['class' => 'whitespace-pre-line']);
        }

        return $entries;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'board' => ApplicationBoard::route('/board'),
            'view' => ViewApplication::route('/{record}'),
        ];
    }
}
