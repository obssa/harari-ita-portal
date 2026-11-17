<?php

namespace App\Filament\Resources\CourseCohorts\Pages;

use App\Domain\Academy\Actions\CancelCohort;
use App\Domain\Academy\Actions\IssueCohortCertificates;
use App\Domain\Academy\Actions\SaveCohort;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseSession;
use App\Filament\Resources\CourseCohorts\CourseCohortResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditCourseCohort extends EditRecord
{
    protected static string $resource = CourseCohortResource::class;

    protected function getHeaderActions(): array
    {
        return [...self::cohortActions(), DeleteAction::make()];
    }

    /**
     * Issue the certificates of those who met the attendance rule, print the cohort's certificates, cancel the
     * cohort. Shared with the read-only page.
     *
     * @return list<Action>
     */
    public static function cohortActions(): array
    {
        return [
            Action::make('issueCertificates')->label(__('academy.admin.issue_all'))->icon(Heroicon::OutlinedCheckBadge)
                ->visible(fn (CourseCohort $record): bool => CurrentUser::get()->can('issueCertificates', $record) && ! $record->isCancelled() && $record->ends_on <= today())
                ->requiresConfirmation()->modalDescription(fn (CourseCohort $record): string => __('academy.admin.issue_all_help', ['percent' => $record->loadMissing('course')->course->attendance_required]))
                ->action(function (CourseCohort $record): void {
                    try {
                        $count = app(IssueCohortCertificates::class)->handle($record, CurrentUser::get());
                        Notification::make()->success()->title(trans_choice('academy.admin.issued', $count, ['count' => $count]))->send();
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                    }
                }),
            Action::make('printCertificates')->label(__('academy.admin.print_all'))->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->visible(fn (CourseCohort $record): bool => CurrentUser::get()->can('issueCertificates', $record) && $record->certificates()->whereNull('revoked_at')->exists())
                ->url(fn (CourseCohort $record): string => route('filament.admin.academy.certificates.print', ['cohort' => $record->id]), shouldOpenInNewTab: true),
            Action::make('cancelCohort')->label(__('academy.admin.cancel_cohort'))->icon(Heroicon::OutlinedXCircle)->color('danger')
                ->visible(fn (CourseCohort $record): bool => CurrentUser::get()->can('update', $record) && ! $record->isCancelled() && ! $record->hasEnded())
                ->requiresConfirmation()->modalDescription(__('academy.admin.cancel_cohort_help'))
                ->action(function (CourseCohort $record): void {
                    try {
                        $count = app(CancelCohort::class)->handle($record);
                        Notification::make()->success()->title(trans_choice('academy.admin.cohort_cancelled', $count, ['count' => $count]))->send();
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CourseCohort $cohort */
        $cohort = $this->getRecord();

        return self::formData($cohort, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function formData(CourseCohort $cohort, array $data): array
    {
        $data['instructors'] = $cohort->instructors()->pluck('instructors.id')->map(fn (mixed $id): string => (string) $id)->all();
        $data['sessions'] = $cohort->sessions()->get()->map(fn (CourseSession $s): array => [
            'id' => $s->id,
            'starts_at' => $s->starts_at->format('Y-m-d H:i:s'),
            'ends_at' => $s->ends_at->format('Y-m-d H:i:s'),
            'titles' => $s->titles,
        ])->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var CourseCohort $record */
        return app(SaveCohort::class)->handle($record, $data, CurrentUser::get());
    }
}
