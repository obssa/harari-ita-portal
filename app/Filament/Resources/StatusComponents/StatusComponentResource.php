<?php

namespace App\Filament\Resources\StatusComponents;

use App\Domain\Services\Models\ServiceItem;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\StatusComponents\Pages\ManageStatusComponents;
use App\Filament\Support\SentenceCaseLabels;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The systems shown on the status page (ADR-0018), named in each language.
 */
class StatusComponentResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = StatusComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'status-components';

    public static function getModelLabel(): string
    {
        return __('status.admin.component');
    }

    public static function getPluralModelLabel(): string
    {
        return __('status.admin.components');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('status.admin.names'))->columns(2)->schema(array_map(
                fn (string $locale): TextInput => TextInput::make("names.{$locale}")
                    ->label(Locales::nativeName($locale))
                    ->requiredWithoutAll(collect(Locales::all())->reject(fn (string $other): bool => $other === $locale)->map(fn (string $other): string => "names.{$other}")->implode(','))
                    ->maxLength(120)
                    ->extraInputAttributes(['lang' => $locale]),
                Locales::all(),
            )),
            Select::make('service_item_id')->label(__('status.admin.service'))->searchable()
                ->options(fn (): array => ServiceItem::query()->with('translations')->get()
                    ->mapWithKeys(fn (ServiceItem $service): array => [$service->id => $service->title()])->all()),
            Toggle::make('visible')->label(__('status.admin.visible'))->default(true),
            TextInput::make('sort')->label(__('admin.categories.field.sort'))->numeric()->integer()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label(__('status.admin.name'))->state(fn (StatusComponent $record): string => $record->name()),
                IconColumn::make('visible')->label(__('status.admin.visible'))->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStatusComponents::route('/')];
    }
}
