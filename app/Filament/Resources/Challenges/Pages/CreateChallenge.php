<?php

namespace App\Filament\Resources\Challenges\Pages;

use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Models\Challenge;
use App\Filament\Resources\Challenges\ChallengeResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateChallenge extends CreateRecord
{
    protected static string $resource = ChallengeResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveChallenge::class)->handle(new Challenge, $data, CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
