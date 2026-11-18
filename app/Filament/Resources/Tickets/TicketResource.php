<?php

namespace App\Filament\Resources\Tickets;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Organisation\Models\Office;
use App\Domain\Support\Enums\RequesterType;
use App\Domain\Support\Enums\SlaState;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketChannel;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\TicketBoard;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
 * The IT service desk (PLAN.md §6; ADR-0017): the tickets of the directorates one looks after, with their SLA
 * state, a board by status, and the conversation with the requester.
 */
class TicketResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'tickets';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getModelLabel(): string
    {
        return __('support.admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('support.admin.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.services');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Filament::auth()->user();
        if (! $user instanceof User || ! $user->can('tickets.view')) {
            return null;
        }
        $open = OfficeScope::apply(Ticket::query(), $user, 'tickets.view')
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])
            ->count();

        return $open > 0 ? (string) $open : null;
    }

    /**
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Ticket> $query */
        $query = parent::getEloquentQuery();

        return OfficeScope::apply($query, CurrentUser::get(), 'tickets.view');
    }

    /** Recording a ticket taken by phone, email or at the desk. */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('support.admin.request'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Grid::make(2)->schema([
                    Select::make('category')->label(__('support.field.category'))->options(TicketCategory::options())->required(),
                    Select::make('priority')->label(__('support.field.priority'))->options(TicketPriority::options())->default(TicketPriority::Normal->value)->required(),
                ]),
                TextInput::make('subject')->label(__('support.field.subject'))->required()->maxLength(200),
                Textarea::make('description')->label(__('support.field.description'))->required()->rows(6)->maxLength(5000),
            ]),
            Section::make(__('support.admin.requester'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Select::make('channel')->label(__('support.field.channel'))
                    ->options(collect(TicketChannel::options())->except(TicketChannel::Web->value)->all())
                    ->default(TicketChannel::Phone->value)->required(),
                Select::make('requester_type')->label(__('support.field.requester_type'))->options(RequesterType::options())->required(),
                TextInput::make('requester_office')->label(__('support.field.requester_office'))->maxLength(200),
                TextInput::make('name')->label(__('support.field.name'))->required()->maxLength(120),
                TextInput::make('email')->label(__('support.field.email'))->email()->maxLength(190),
                TextInput::make('phone')->label(__('support.field.phone'))->tel()->maxLength(40),
                Select::make('office_id')->label(__('support.field.office'))->searchable()->options(fn (): array => self::officeOptions()),
                Select::make('locale')->label(__('admin.revisions.field.language'))
                    ->options(collect(Locales::all())->mapWithKeys(fn (string $l): array => [$l => Locales::nativeName($l)])->all())
                    ->default(Locales::default())->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['office.translations', 'assignee']))
            ->columns([
                TextColumn::make('reference')->label(__('support.reference'))->fontFamily('mono')->searchable()->copyable(),
                TextColumn::make('subject')->label(__('support.field.subject'))->limit(60)->wrap(),
                TextColumn::make('status')->label(__('support.field.status'))->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                    ->color(fn (TicketStatus $state): string => $state->color()),
                TextColumn::make('priority')->label(__('support.field.priority'))->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->label())
                    ->color(fn (TicketPriority $state): string => $state->color()),
                TextColumn::make('sla')->label(__('support.field.sla'))->badge()
                    ->state(fn (Ticket $record): string => $record->slaState()->label())
                    ->color(fn (Ticket $record): string => $record->slaState()->color()),
                TextColumn::make('category')->label(__('support.field.category'))
                    ->formatStateUsing(fn (TicketCategory $state): string => $state->label())->toggleable(),
                TextColumn::make('office')->label(__('support.field.office'))->state(fn (Ticket $record): ?string => $record->office?->name())->placeholder('—')->toggleable(),
                TextColumn::make('assignee.name')->label(__('support.field.assignee'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('support.field.created_at'))->formatStateUsing(CalendarDate::format(withTime: true))->sortable(),
                TextColumn::make('resolve_due_at')->label(__('support.field.resolve_due_at'))->formatStateUsing(CalendarDate::format(withTime: true))->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('open')->label(__('support.admin.filter.open'))->default()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])),
                Filter::make('mine')->label(__('support.admin.filter.mine'))
                    ->query(fn (Builder $query): Builder => $query->where('assignee_id', CurrentUser::get()->id)),
                Filter::make('breached')->label(__('support.admin.filter.breached'))
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])
                        ->where('resolve_due_at', '<', now())),
                SelectFilter::make('status')->label(__('support.field.status'))->options(TicketStatus::options()),
                SelectFilter::make('priority')->label(__('support.field.priority'))->options(TicketPriority::options()),
                SelectFilter::make('category')->label(__('support.field.category'))->options(TicketCategory::options()),
                SelectFilter::make('office_id')->label(__('support.field.office'))->options(fn (): array => self::officeOptions()),
            ])
            ->recordActions([ViewAction::make()])
            ->recordUrl(fn (Ticket $record): string => self::getUrl('view', ['record' => $record]))
            ->poll('60s')
            ->emptyStateHeading(__('support.admin.empty'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(fn (Ticket $record): string => $record->subject)->schema([
                    TextEntry::make('description')->hiddenLabel()->extraAttributes(['class' => 'whitespace-pre-line']),
                    ViewEntry::make('thread')->hiddenLabel()->view('filament.support.thread'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('support.admin.handling'))->schema([
                    TextEntry::make('reference')->label(__('support.reference'))->fontFamily('mono')->copyable(),
                    TextEntry::make('status')->label(__('support.field.status'))->badge()
                        ->formatStateUsing(fn (TicketStatus $state): string => $state->label())->color(fn (TicketStatus $state): string => $state->color()),
                    TextEntry::make('priority')->label(__('support.field.priority'))->badge()
                        ->formatStateUsing(fn (TicketPriority $state): string => $state->label())->color(fn (TicketPriority $state): string => $state->color()),
                    TextEntry::make('category')->label(__('support.field.category'))->formatStateUsing(fn (TicketCategory $state): string => $state->label()),
                    TextEntry::make('office_name')->label(__('support.field.office'))->state(fn (Ticket $record): ?string => $record->office?->name())->placeholder('—'),
                    TextEntry::make('assignee.name')->label(__('support.field.assignee'))->placeholder('—'),
                    TextEntry::make('response')->label(__('support.field.response_due_at'))
                        ->state(fn (Ticket $record): string => self::target($record->response_due_at, $record->responseState()))
                        ->color(fn (Ticket $record): string => $record->responseState()->color()),
                    TextEntry::make('resolve')->label(__('support.field.resolve_due_at'))
                        ->state(fn (Ticket $record): string => self::target($record->resolve_due_at, $record->slaState()))
                        ->color(fn (Ticket $record): string => $record->slaState()->color()),
                    TextEntry::make('satisfaction')->label(__('support.field.satisfaction'))
                        ->state(fn (Ticket $record): ?string => $record->satisfaction !== null ? $record->satisfaction.' / 5' : null)
                        ->helperText(fn (Ticket $record): ?string => $record->satisfaction_note)
                        ->placeholder('—'),
                ]),
                Section::make(__('support.admin.requester'))->schema([
                    TextEntry::make('requester_type')->label(__('support.field.requester_type'))->formatStateUsing(fn (RequesterType $state): string => $state->label()),
                    TextEntry::make('requester_office')->label(__('support.field.requester_office'))->placeholder('—'),
                    TextEntry::make('name')->label(__('support.field.name'))->placeholder('—'),
                    TextEntry::make('email')->label(__('support.field.email'))->placeholder('—')->copyable(),
                    TextEntry::make('phone')->label(__('support.field.phone'))->placeholder('—')->copyable(),
                    TextEntry::make('channel')->label(__('support.field.channel'))->formatStateUsing(fn (TicketChannel $state): string => $state->label()),
                    TextEntry::make('locale')->label(__('admin.revisions.field.language'))->formatStateUsing(fn (string $state): string => Locales::nativeName($state)),
                    TextEntry::make('created_at')->label(__('support.field.created_at'))->formatStateUsing(CalendarDate::format(withTime: true)),
                ]),
            ]),
        ]);
    }

    /** "16 Nov 2026 14:30 · Due soon" in the admin's calendar. */
    public static function target(mixed $due, SlaState $state): string
    {
        if ($due === null) {
            return $state->label();
        }

        return CalendarDate::format(withTime: true)($due).' · '.$state->label();
    }

    /**
     * @return array<string, string>
     */
    public static function officeOptions(): array
    {
        return Office::query()->with('translations')->orderBy('type')->orderBy('sort')->get()
            ->mapWithKeys(fn (Office $office): array => [$office->id => $office->name()])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'board' => TicketBoard::route('/board'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}
