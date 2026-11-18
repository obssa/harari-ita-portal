<?php

namespace App\Filament\Resources\Certificates;

use App\Domain\Academy\Actions\RevokeCertificate;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Support\CohortScope;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\SentenceCaseLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Issued certificates (ADR-0030): find one by its code, open its verification page, or revoke it with a reason.
 * They are issued from the cohort's learners and never edited.
 */
class CertificateResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('academy.admin.certificate');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy.admin.certificates');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.academy');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course.translations', 'cohort']))
            ->columns([
                TextColumn::make('code')->label(__('academy.field.code'))->searchable()->copyable()->fontFamily('mono'),
                TextColumn::make('holder_initials')->label(__('academy.field.initials'))->placeholder('—'),
                TextColumn::make('course')->label(__('academy.admin.course'))->state(fn (Certificate $record): string => $record->course->title())->wrap(),
                TextColumn::make('cohort.name')->label(__('academy.field.cohort_name')),
                TextColumn::make('issued_at')->label(__('academy.field.issued_at'))->formatStateUsing(CalendarDate::format())->sortable(),
                TextColumn::make('revoked_at')->label(__('academy.field.revoked_at'))->formatStateUsing(CalendarDate::format())->placeholder('—')
                    ->color('danger')->description(fn (Certificate $record): ?string => $record->revoke_reason),
            ])
            ->filters([
                TernaryFilter::make('revoked')->label(__('academy.field.revoked_at'))
                    ->queries(true: fn (Builder $q) => $q->whereNotNull('revoked_at'), false: fn (Builder $q) => $q->whereNull('revoked_at')),
            ])
            ->recordActions([
                Action::make('verify')->label(__('academy.admin.open_verification'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Certificate $record): string => $record->verifyUrl(), shouldOpenInNewTab: true),
                Action::make('revoke')->label(__('academy.admin.revoke'))->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (Certificate $record): bool => CurrentUser::get()->can('revoke', $record))
                    ->schema([Textarea::make('reason')->label(__('academy.field.revoke_reason'))->required()->maxLength(300)])
                    ->modalDescription(__('academy.admin.revoke_help'))
                    ->action(function (Certificate $record, array $data): void {
                        app(RevokeCertificate::class)->handle($record, (string) $data['reason'], CurrentUser::get());
                        Notification::make()->success()->title(__('academy.admin.revoked'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificates::route('/')];
    }

    /**
     * @return Builder<Certificate>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Certificate> */
        return CohortScope::apply(parent::getEloquentQuery(), CurrentUser::get(), 'cohort');
    }
}
