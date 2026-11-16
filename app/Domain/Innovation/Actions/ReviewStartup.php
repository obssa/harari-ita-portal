<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Mail\StartupReviewedMail;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Innovation\Models\StartupTranslation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * The Agency's review of a listing (ADR-0020): list it (its languages go live), ask the founders for changes,
 * decline or archive it with a note, and verify the startup-label claim after seeing the Ministry's certificate.
 * The founders are told by email when the decision changes.
 */
final class ReviewStartup
{
    public function decide(Startup $startup, ListingStatus $status, ?string $note, User $actor): Startup
    {
        $note = trim((string) $note);
        if (in_array($status, [ListingStatus::ChangesRequested, ListingStatus::Declined], true) && $note === '') {
            throw ValidationException::withMessages(['note' => __('innovation.validation.note')]);
        }
        if ($status === ListingStatus::Listed && $startup->translations()->doesntExist()) {
            throw ValidationException::withMessages(['note' => __('innovation.validation.no_language')]);
        }

        $changed = $status !== $startup->listing_status;

        DB::transaction(function () use ($startup, $status, $note, $actor): void {
            $startup->listing_status = $status;
            $startup->review_note = $note !== '' ? mb_substr($note, 0, 2000) : null;
            if ($status === ListingStatus::Listed) {
                $startup->listed_at ??= CarbonImmutable::now();
            }
            $startup->updated_by = $actor->id;
            $startup->save();

            // Only a listed startup has live languages; any other decision takes them off the site.
            $startup->translations()->get()->each(function (StartupTranslation $translation) use ($status): void {
                $translation->status = $status === ListingStatus::Listed ? ContentStatus::Published : ContentStatus::Draft;
                $translation->save();
            });
        });

        if ($changed && $startup->founder_email !== null && $status !== ListingStatus::Submitted) {
            Mail::to($startup->founder_email)->locale($startup->translations()->value('locale') ?? app()->getLocale())
                ->queue(new StartupReviewedMail($startup));
        }

        return $startup->refresh();
    }

    public function verifyLabel(Startup $startup, bool $verified, User $actor): Startup
    {
        if ($verified) {
            $startup->label_status = LabelStatus::Verified;
            $startup->label_verified_at = CarbonImmutable::now();
            $startup->label_verified_by = $actor->id;
        } else {
            $startup->label_status = $startup->label_status === LabelStatus::Verified ? LabelStatus::SelfDeclared : $startup->label_status;
            $startup->label_verified_at = null;
            $startup->label_verified_by = null;
        }
        $startup->updated_by = $actor->id;
        $startup->save();

        return $startup;
    }
}
