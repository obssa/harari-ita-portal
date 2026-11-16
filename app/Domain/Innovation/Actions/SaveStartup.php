<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Content\Models\Category;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\StartupStage;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a startup's listing with its languages (ADR-0020), from the admin. A startup entered by staff starts as
 * submitted like any other; publishing it is the review's job (ReviewStartup), not the edit form's. A "verified"
 * label is only set through ReviewStartup::verifyLabel.
 */
final class SaveStartup
{
    public const TEXT_FIELDS = ['summary', 'products'];

    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Startup $startup, array $data, User $actor): Startup
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations);

        $errors = [];
        $stage = StartupStage::tryFrom((string) ($data['stage'] ?? ''));
        if ($stage === null) {
            $errors['data.stage'] = __('innovation.validation.stage');
        }
        $year = filled($data['founded_year'] ?? null) ? (int) $data['founded_year'] : null;
        if ($year !== null && ($year < 1950 || $year > (int) now()->format('Y'))) {
            $errors['data.founded_year'] = __('innovation.validation.year');
        }
        $website = filled($data['website'] ?? null) ? trim((string) $data['website']) : null;
        if ($website !== null && (! str_starts_with($website, 'https://') && ! str_starts_with($website, 'http://') || filter_var($website, FILTER_VALIDATE_URL) === false)) {
            $errors['data.website'] = __('innovation.validation.website');
        }
        $email = mb_strtolower(trim((string) ($data['founder_email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['data.founder_email'] = __('support.validation.email');
        }
        $phoneTyped = trim((string) ($data['founder_phone'] ?? ''));
        $phone = $phoneTyped !== '' ? PhoneNumber::normalise($phoneTyped) : null;
        if ($phoneTyped !== '' && $phone === null) {
            $errors['data.founder_phone'] = __('support.validation.phone');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $sectorId = filled($data['sector_id'] ?? null) ? (string) $data['sector_id'] : null;
        if ($sectorId !== null && Category::query()->whereKey($sectorId)->where('type', Category::TYPE_SECTOR)->doesntExist()) {
            $sectorId = null;
        }

        // A label claim can be made or withdrawn here; "verified" is kept only if it was verified before.
        $label = LabelStatus::tryFrom((string) ($data['label_status'] ?? '')) ?? LabelStatus::None;
        if ($label === LabelStatus::Verified && $startup->label_status !== LabelStatus::Verified) {
            $label = LabelStatus::SelfDeclared;
        }

        return DB::transaction(function () use ($startup, $data, $translations, $actor, $stage, $year, $website, $email, $phone, $sectorId, $label): Startup {
            $startup->fill([
                'sector_id' => $sectorId,
                'stage' => $stage,
                'founded_year' => $year,
                'team_size' => filled($data['team_size'] ?? null) ? max(1, min(10000, (int) $data['team_size'])) : null,
                'area' => filled($data['area'] ?? null) ? mb_substr(trim((string) $data['area']), 0, 120) : null,
                'website' => $website,
                'logo_id' => filled($data['logo_id'] ?? null) ? (int) $data['logo_id'] : null,
                'label_status' => $label,
                'founder_name' => filled($data['founder_name'] ?? null) ? mb_substr(trim((string) $data['founder_name']), 0, 120) : $startup->founder_name,
                'founder_email' => $email !== '' ? $email : $startup->founder_email,
                'founder_phone' => $phone ?? $startup->founder_phone,
            ]);
            if ($label !== LabelStatus::Verified) {
                $startup->label_verified_at = null;
                $startup->label_verified_by = null;
            }
            $startup->email_hash = $startup->founder_email !== null ? EventRegistration::hash($startup->founder_email) : null;
            $startup->phone_hash = $startup->founder_phone !== null ? EventRegistration::hash($startup->founder_phone) : null;
            if (! $startup->exists) {
                $startup->reference = Startup::newReference();
                $startup->created_by = $actor->id;
            }
            $startup->updated_by = $actor->id;
            $startup->save();

            // Languages are published by the review, so the edit form never publishes them itself.
            $this->translations->handle($startup, $translations, self::TEXT_FIELDS, false);
            $this->usage->handle($startup, 'logo', [$startup->logo_id]);

            return $startup->refresh();
        });
    }
}
