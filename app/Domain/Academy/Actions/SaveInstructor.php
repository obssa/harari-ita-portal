<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Models\Instructor;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Saves an instructor (ADR-0028). Showing them publicly needs their consent, recorded with its date; withdrawing
 * consent hides them again.
 */
final class SaveInstructor
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Instructor $instructor, array $data, User $actor): Instructor
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) ($data['name'] ?? '')));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['data.name' => __('academy.validation.instructor_name')]);
        }

        $consented = (bool) ($data['consented'] ?? false);
        $public = (bool) ($data['public'] ?? false);
        if ($public && ! $consented) {
            throw ValidationException::withMessages(['data.public' => __('academy.validation.instructor_consent')]);
        }

        $userId = filled($data['user_id'] ?? null) && User::query()->whereKey($data['user_id'])->exists() ? (int) $data['user_id'] : null;

        $instructor->fill([
            'user_id' => $userId,
            'name' => $name,
            'titles' => LocalisedText::clean($data['titles'] ?? [], 120) ?: null,
            'public' => $public,
            'consented_at' => $consented ? ($instructor->consented_at ?? now()) : null,
        ]);
        $instructor->save();

        return $instructor;
    }
}
