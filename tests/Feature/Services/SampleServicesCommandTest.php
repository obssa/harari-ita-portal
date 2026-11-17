<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Support\Models\Ticket;
use Illuminate\Support\Facades\Mail;

it('adds labelled sample content without sending anything, once, and removes exactly it again', function () {
    Mail::fake();
    staff(SystemRole::SuperAdmin);

    $this->artisan('portal:sample-services')->assertSuccessful();
    $this->artisan('portal:sample-services')->assertSuccessful();

    expect(ServiceItem::query()->count())->toBe(0)
        ->and(StatusComponent::query()->count())->toBe(2)
        ->and(Incident::query()->count())->toBe(1)
        ->and(Ticket::query()->count())->toBe(1);
    Mail::assertNothingQueued();

    $this->get('/en/status')->assertSee('[Sample] Email is slow');

    $this->artisan('portal:sample-services', ['--remove' => true])->assertSuccessful();
    expect(ServiceItem::query()->withTrashed()->count())->toBe(0)
        ->and(StatusComponent::query()->count())->toBe(0)
        ->and(Incident::query()->count())->toBe(0)
        ->and(Ticket::query()->count())->toBe(0);
});
