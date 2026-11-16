<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Content\Models\Category;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Enums\StartupStage;
use App\Domain\Innovation\Mail\StartupReceivedMail;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Innovation\Notifications\NewStartupNotice;
use App\Domain\Notifications\Support\Recipients;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Founders ask to be listed (ADR-0020): one language, the one they write in; the listing waits for the Agency's
 * review and is not public until it is listed. The founders get a reference to follow the review.
 */
final class SubmitStartup
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, string $locale): Startup
    {
        $name = trim((string) ($data['name'] ?? ''));
        $summary = trim((string) ($data['summary'] ?? ''));
        $products = trim((string) ($data['products'] ?? ''));
        $stage = StartupStage::tryFrom((string) ($data['stage'] ?? ''));
        $founder = trim((string) ($data['founder_name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['founder_email'] ?? '')));
        $phoneTyped = trim((string) ($data['founder_phone'] ?? ''));
        $phone = $phoneTyped !== '' ? PhoneNumber::normalise($phoneTyped) : null;
        $website = trim((string) ($data['website'] ?? ''));
        $year = filled($data['founded_year'] ?? null) ? (int) $data['founded_year'] : null;

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 200) {
            $errors['name'] = __('innovation.validation.name');
        }
        if (mb_strlen($summary) < 20 || mb_strlen($summary) > 400) {
            $errors['summary'] = __('innovation.validation.summary');
        }
        if ($stage === null) {
            $errors['stage'] = __('innovation.validation.stage');
        }
        if ($year !== null && ($year < 1950 || $year > (int) now()->format('Y'))) {
            $errors['founded_year'] = __('innovation.validation.year');
        }
        if ($website !== '' && ((! str_starts_with($website, 'https://') && ! str_starts_with($website, 'http://')) || filter_var($website, FILTER_VALIDATE_URL) === false)) {
            $errors['website'] = __('innovation.validation.website');
        }
        if (mb_strlen($founder) < 2 || mb_strlen($founder) > 120) {
            $errors['founder_name'] = __('support.validation.name');
        }
        if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['founder_email'] = __('support.validation.email');
        }
        if ($phoneTyped !== '' && $phone === null) {
            $errors['founder_phone'] = __('support.validation.phone');
        }
        if (! filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['consent'] = __('support.validation.consent');
        }
        if ($errors !== [] || $stage === null) {
            throw ValidationException::withMessages($errors);
        }

        $sectorId = filled($data['sector_id'] ?? null) && Category::query()->whereKey((string) $data['sector_id'])->where('type', Category::TYPE_SECTOR)->exists()
            ? (string) $data['sector_id'] : null;

        $startup = DB::transaction(function () use ($data, $locale, $name, $summary, $products, $stage, $founder, $email, $phone, $website, $year, $sectorId): Startup {
            $startup = new Startup([
                'reference' => Startup::newReference(),
                'sector_id' => $sectorId,
                'stage' => $stage,
                'founded_year' => $year,
                'team_size' => filled($data['team_size'] ?? null) ? max(1, min(10000, (int) $data['team_size'])) : null,
                'area' => filled($data['area'] ?? null) ? mb_substr(trim((string) $data['area']), 0, 120) : null,
                'website' => $website !== '' ? $website : null,
                'label_status' => filter_var($data['label_claimed'] ?? false, FILTER_VALIDATE_BOOLEAN) ? LabelStatus::SelfDeclared : LabelStatus::None,
                'listing_status' => ListingStatus::Submitted,
                'founder_name' => $founder,
                'founder_email' => $email,
                'founder_phone' => $phone,
                'email_hash' => EventRegistration::hash($email),
                'phone_hash' => $phone !== null ? EventRegistration::hash($phone) : null,
                'consented_at' => now(),
            ]);
            $startup->save();
            $startup->putTranslation($locale, [
                'name' => $name,
                'summary' => $summary,
                'products' => $products !== '' ? mb_substr($products, 0, 2000) : null,
                'status' => 'draft',
            ]);

            return $startup;
        });

        Mail::to($email)->locale($locale)->queue(new StartupReceivedMail($startup));
        Recipients::notify(Recipients::withPermission('startups.manage'), NewStartupNotice::for($startup));

        return $startup;
    }
}
