<?php

namespace App\Filament\Resources\Opportunities\RelationManagers;

use App\Domain\Opportunities\Actions\PublishAddendum;
use App\Domain\Opportunities\Enums\AddendumKind;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Services\Calendar\EthiopianCalendar;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * An opportunity's log (ADR-0033): numbered, dated entries published at once. An extension moves the deadline, a
 * cancellation closes the opportunity. Entries are corrected, never deleted.
 */
class AddendaRelationManager extends RelationManager
{
    protected static string $relationship = 'addenda';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('opportunities.addendum.log');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord instanceof Opportunity ? (string) $ownerRecord->addenda()->count() : null;
    }

    /**
     * @return list<Section|Select|DateTimePicker|Hidden>
     */
    private static function fields(bool $creating): array
    {
        $kind = fn (Get $get): ?AddendumKind => AddendumKind::tryFrom((string) $get('kind'));

        return [
            $creating
                ? Select::make('kind')->label(__('opportunities.addendum.field.kind'))->options(AddendumKind::options())->required()->live()
                    ->helperText(__('opportunities.addendum.help.kind'))
                : Hidden::make('kind'),
            DateTimePicker::make('new_closes_at')->label(__('opportunities.addendum.field.new_closes_at'))->seconds(false)
                ->timezone(EthiopianCalendar::timezone())->required()
                ->visible(fn (Get $get): bool => $creating && $kind($get) === AddendumKind::Extension),
            ...array_map(fn (string $locale): Section => Section::make(Locales::nativeName($locale))
                ->collapsible()->collapsed($locale !== Locales::default())
                ->schema([
                    TextInput::make("titles.{$locale}")->maxLength(PublishAddendum::TITLE_MAX)->extraInputAttributes(['lang' => $locale])
                        ->label(fn (Get $get): string => $kind($get) === AddendumKind::Clarification ? __('opportunities.addendum.field.question') : __('opportunities.addendum.field.title')),
                    Textarea::make("bodies.{$locale}")->rows(4)->maxLength(PublishAddendum::BODY_MAX)->extraInputAttributes(['lang' => $locale])
                        ->label(fn (Get $get): string => match ($kind($get)) {
                            AddendumKind::Clarification => __('opportunities.addendum.field.answer'),
                            AddendumKind::Cancellation => __('opportunities.addendum.field.reason'),
                            default => __('opportunities.addendum.field.body'),
                        }),
                ]), Locales::all()),
            Select::make('document_id')->label(__('opportunities.addendum.field.document'))->searchable()
                ->options(fn (): array => OpportunityResource::documentOptions())->helperText(__('opportunities.addendum.help.document')),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var Opportunity $opportunity */
        $opportunity = $this->getOwnerRecord();

        return $table
            ->defaultSort('number', 'desc')
            ->columns([
                TextColumn::make('number')->label('#')->fontFamily('mono'),
                TextColumn::make('kind')->label(__('opportunities.addendum.field.kind'))->badge()
                    ->formatStateUsing(fn (AddendumKind $state): string => $state->label())->color(fn (AddendumKind $state): string => $state->color()),
                TextColumn::make('title')->label(__('opportunities.addendum.field.title'))->state(fn (OpportunityAddendum $record): string => $record->title())->wrap(),
                TextColumn::make('new_closes_at')->label(__('opportunities.addendum.field.new_closes_at'))->formatStateUsing(CalendarDate::format(withTime: true))->placeholder('—'),
                TextColumn::make('published_at')->label(__('opportunities.addendum.field.published_at'))->formatStateUsing(CalendarDate::format(withTime: true)),
                TextColumn::make('corrected_at')->label(__('opportunities.addendum.field.corrected_at'))->formatStateUsing(CalendarDate::format(withTime: true))->placeholder('—')->toggleable(),
            ])
            ->headerActions([
                Action::make('publish')->label(__('opportunities.addendum.publish'))->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => CurrentUser::get()->can('create', OpportunityAddendum::class) && ! $opportunity->isCancelled())
                    ->schema(self::fields(true))
                    ->modalDescription(__('opportunities.addendum.help.publish'))
                    ->modalSubmitActionLabel(__('opportunities.addendum.publish'))
                    ->action(function (array $data, Action $action) use ($opportunity): void {
                        try {
                            $addendum = app(PublishAddendum::class)->handle($opportunity, $data, CurrentUser::get());
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                            $action->halt();

                            return;
                        }
                        Notification::make()->success()->title(__('opportunities.addendum.published', ['number' => $addendum->number]))->send();
                        $this->dispatch('opportunity-log-changed');
                    }),
            ])
            ->recordActions([
                Action::make('correct')->label(__('opportunities.addendum.correct'))->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (OpportunityAddendum $record): bool => CurrentUser::get()->can('update', $record))
                    ->fillForm(fn (OpportunityAddendum $record): array => ['kind' => $record->kind->value, 'titles' => $record->titles, 'bodies' => $record->bodies ?? [], 'document_id' => $record->document_id])
                    ->schema(self::fields(false))
                    ->modalDescription(__('opportunities.addendum.help.correct'))
                    ->action(function (OpportunityAddendum $record, array $data, Action $action): void {
                        try {
                            app(PublishAddendum::class)->correct($record, $data, CurrentUser::get());
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                            $action->halt();

                            return;
                        }
                        Notification::make()->success()->title(__('opportunities.addendum.corrected'))->send();
                    }),
            ]);
    }
}
