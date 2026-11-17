<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Projects\Actions\SaveRisk;
use App\Domain\Projects\Enums\RiskLevel;
use App\Domain\Projects\Enums\RiskStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectRisk;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A project's risk register (ADR-0024), for staff only: likelihood and impact, the owner, the mitigation and the next
 * review date. Nothing here reaches a public page.
 */
class RisksRelationManager extends RelationManager
{
    protected static string $relationship = 'risks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.admin.risks');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Project) {
            return null;
        }
        $open = $ownerRecord->risks()->where('status', RiskStatus::Open->value)->count();

        return $open > 0 ? (string) $open : null;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();

        return $schema->columns(1)->components([
            TextInput::make('title')->label(__('projects.admin.field.risk_title'))->required()->maxLength(200),
            Textarea::make('description')->label(__('projects.admin.field.risk_description'))->rows(3)->maxLength(3000),
            Grid::make(3)->schema([
                Select::make('likelihood')->label(__('projects.admin.field.risk_likelihood'))->options(RiskLevel::options())->required(),
                Select::make('impact')->label(__('projects.admin.field.risk_impact'))->options(RiskLevel::options())->required(),
                Select::make('status')->label(__('projects.admin.field.status'))->options(RiskStatus::options())->default(RiskStatus::Open->value)->required(),
            ]),
            Textarea::make('mitigation')->label(__('projects.admin.field.risk_mitigation'))->rows(3)->maxLength(3000),
            Grid::make(2)->schema([
                Select::make('owner_id')->label(__('projects.admin.field.risk_owner'))->searchable()
                    ->options(fn (): array => User::permission(['projects.view', 'projects.manage'])->orderBy('name')->limit(300)->get()
                        ->filter(fn (User $user): bool => $user->can('view', $project))
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])->all()),
                DatePicker::make('review_on')->label(__('projects.admin.field.risk_review_on')),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('owner'))
            ->defaultSort('status')
            ->columns([
                TextColumn::make('title')->label(__('projects.admin.field.risk_title'))->wrap(),
                TextColumn::make('score')->label(__('projects.admin.field.risk_score'))
                    ->state(fn (ProjectRisk $record): int => $record->score())
                    ->badge()->color(fn (int $state): string => match (true) {
                        $state >= 6 => 'danger',
                        $state >= 3 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')->label(__('projects.admin.field.status'))->badge()
                    ->formatStateUsing(fn (RiskStatus $state): string => $state->label())
                    ->color(fn (RiskStatus $state): string => $state->color()),
                TextColumn::make('owner.name')->label(__('projects.admin.field.risk_owner'))->placeholder('—'),
                TextColumn::make('review_on')->label(__('projects.admin.field.risk_review_on'))
                    ->formatStateUsing(CalendarDate::format())->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('projects.admin.field.status'))->options(RiskStatus::options()),
            ])
            ->headerActions([
                CreateAction::make()->label(__('projects.admin.add_risk'))
                    ->using(fn (array $data): ProjectRisk => app(SaveRisk::class)->handle($project, new ProjectRisk, $data, CurrentUser::get())),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (ProjectRisk $record, array $data): ProjectRisk => app(SaveRisk::class)->handle($project, $record, $data, CurrentUser::get())),
                DeleteAction::make(),
            ]);
    }
}
