<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Domain\Academy\Actions\SaveCourse;
use App\Domain\Academy\Models\Course;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [...ViewOnSite::header(), DeleteAction::make(), RestoreAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Course $course */
        $course = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($course, ['title', ...SaveCourse::TEXT_FIELDS]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Course $record */
        return app(SaveCourse::class)->handle($record, $data, CurrentUser::get());
    }
}
