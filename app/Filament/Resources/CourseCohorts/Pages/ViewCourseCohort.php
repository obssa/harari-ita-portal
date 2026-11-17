<?php

namespace App\Filament\Resources\CourseCohorts\Pages;

use App\Domain\Academy\Models\CourseCohort;
use App\Filament\Resources\CourseCohorts\CourseCohortResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * A cohort for staff who may see it but not change it, such as instructors, who mark attendance from here (ADR-0028).
 */
class ViewCourseCohort extends ViewRecord
{
    protected static string $resource = CourseCohortResource::class;

    protected function getHeaderActions(): array
    {
        return EditCourseCohort::cohortActions();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CourseCohort $cohort */
        $cohort = $this->getRecord();

        return EditCourseCohort::formData($cohort, $data);
    }
}
