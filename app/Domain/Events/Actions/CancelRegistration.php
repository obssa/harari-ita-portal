<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Enums\RegistrationStatus;
use App\Domain\Events\Models\EventRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gives a place back, by the person from their ticket or by staff. A freed
 * place goes to the first person on the waiting list (ADR-0025). A person already checked in at the door
 * has used the place, so the registration stays (ADR-0054); decided on the locked row.
 */
final class CancelRegistration
{
    public function __construct(
        private readonly SendRegistrationMessage $messages,
        private readonly FillFromWaitlist $waitlist,
    ) {}

    public function handle(EventRegistration $registration): void
    {
        $freesPlace = DB::transaction(function () use ($registration): ?bool {
            $locked = EventRegistration::query()->whereKey($registration->getKey())->lockForUpdate()->firstOrFail();
            $registration->setRawAttributes($locked->getAttributes(), true);
            if ($registration->status === RegistrationStatus::Cancelled) {
                return null;
            }
            if ($registration->checked_in_at !== null) {
                throw ValidationException::withMessages(['registration' => __('events.registration.checked_in')]);
            }

            $frees = $registration->status === RegistrationStatus::Registered;
            $registration->status = RegistrationStatus::Cancelled;
            $registration->cancelled_at = CarbonImmutable::now();
            $registration->save();

            return $frees;
        });
        if ($freesPlace === null) {
            return;
        }

        $this->messages->handle($registration, SendRegistrationMessage::CANCELLED);
        if ($freesPlace) {
            $this->waitlist->handle($registration->event);
        }
    }
}
