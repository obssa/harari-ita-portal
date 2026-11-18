<?php

namespace App\Filament\Resources\SlaPolicies;

use App\Domain\Sites\Models\Site;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Models\SlaPolicy;
use App\Filament\Resources\SlaPolicies\Pages\ManageSlaPolicies;
use App\Filament\Support\SentenceCaseLabels;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * SLA targets (ADR-0017): first response and resolution in working hours, by priority and optionally by category.
 * Without a row, the defaults in config/portal.php apply.
 */
class SlaPolicyResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = SlaPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'sla-policies';

    public static function getModelLabel(): string
    {
        return __('support.sla_admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('support.sla_admin.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('priority')->label(__('support.field.priority'))->options(TicketPriority::options())->required()
                // One target per priority and category (or per priority for all categories) on this site.
                ->unique(table: 'sla_policies', column: 'priority', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => filled($get('category'))
                    ? $rule->where('site_id', Site::current()->id)->where('category', (string) $get('category'))
                    : $rule->where('site_id', Site::current()->id)->whereNull('category'))
                ->validationMessages(['unique' => __('support.sla_admin.duplicate')]),
            Select::make('category')->label(__('support.field.category'))->options(TicketCategory::options())
                ->placeholder(__('support.sla_admin.all_categories')),
            TextInput::make('response_hours')->label(__('support.sla_admin.response_hours'))->numeric()->integer()->minValue(1)->maxValue(720)->required(),
            TextInput::make('resolve_hours')->label(__('support.sla_admin.resolve_hours'))->numeric()->integer()->minValue(1)->maxValue(2000)->required()
                ->gte('response_hours'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->description(__('support.sla_admin.help'))
            ->columns([
                TextColumn::make('priority')->label(__('support.field.priority'))->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->label())->color(fn (TicketPriority $state): string => $state->color()),
                TextColumn::make('category')->label(__('support.field.category'))
                    ->formatStateUsing(fn (?TicketCategory $state): string => $state?->label() ?? __('support.sla_admin.all_categories'))
                    ->placeholder(__('support.sla_admin.all_categories')),
                TextColumn::make('response_hours')->label(__('support.sla_admin.response_hours')),
                TextColumn::make('resolve_hours')->label(__('support.sla_admin.resolve_hours')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSlaPolicies::route('/')];
    }
}
