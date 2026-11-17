<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Events\Models\EventRegistration;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Status\Actions\SaveIncident;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Support\Enums\RequesterType;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketChannel;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\SlaPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Sla\WorkingHours;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Clearly labelled sample content for the phase 2 gate on staging (docs/phases/02-services-support-status.md):
 * two status systems with one incident, and one support ticket. No email or SMS is sent. It adds no service: the
 * catalogue shows the Proposed services (ADR-0019), which already cover office email. `--remove` deletes what it
 * added, and also a [Sample] service left by its first version. Refuses to run in production.
 */
final class SampleServicesCommand extends Command
{
    protected $signature = 'portal:sample-services {--remove : Delete the sample content again}';

    protected $description = 'Add (or remove) labelled sample status systems, an incident and a ticket';

    private const MARK = '[Sample]';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Not in production.');

            return self::FAILURE;
        }

        return $this->option('remove') ? $this->remove() : $this->add();
    }

    private function add(): int
    {
        if (Ticket::query()->where('email_hash', EventRegistration::hash('sample@example.org'))->exists()) {
            $this->components->warn('Sample content is already there.');

            return self::SUCCESS;
        }

        $actor = User::role(SystemRole::SuperAdmin->value)->firstOrFail();

        StatusComponent::query()->create(['names' => ['en' => self::MARK.' Website', 'am' => self::MARK.' ድረ ገጽ', 'om' => self::MARK.' Marsariitii'], 'sort' => 900]);
        $mail = StatusComponent::query()->create(['names' => ['en' => self::MARK.' Email', 'am' => self::MARK.' ኢሜይል', 'om' => self::MARK.' Imeelii'], 'sort' => 901]);

        app(SaveIncident::class)->handle(new Incident, [
            'titles' => ['en' => self::MARK.' Email is slow', 'am' => self::MARK.' ኢሜይል ቀርፋፋ ነው', 'om' => self::MARK.' Imeeliin suuta deema'],
            'impact' => 'degraded',
            'components' => [$mail->id],
            'bodies' => ['en' => 'Sample incident for testing the status page.', 'am' => 'የሁኔታ ገጹን ለመሞከር የቀረበ ናሙና ችግር።', 'om' => 'Rakkoo fakkeenyaa fuula haalaa qoruuf.'],
        ], $actor);

        // Written directly (not through OpenTicket) so that no email or SMS goes out.
        $targets = SlaPolicy::targets(TicketCategory::Website, TicketPriority::Normal);
        $now = CarbonImmutable::now();
        $ticket = new Ticket([
            'reference' => Ticket::newReference(),
            'locale' => 'en',
            'requester_type' => RequesterType::Resident,
            'name' => self::MARK.' Requester',
            'email' => 'sample@example.org',
            'email_hash' => EventRegistration::hash('sample@example.org'),
            'category' => TicketCategory::Website,
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::New,
            'channel' => TicketChannel::Web,
            'subject' => self::MARK.' The online form does not load',
            'description' => 'Sample ticket for testing the service desk. Not a real request.',
            'response_due_at' => WorkingHours::add($now, $targets['response']),
            'resolve_due_at' => WorkingHours::add($now, $targets['resolve']),
        ]);
        $ticket->save();

        $this->components->info("Sample content added. Ticket {$ticket->reference} (contact: sample@example.org).");

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $services = ServiceItem::query()->withTrashed()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->get();
        $components = StatusComponent::query()->get()->filter(fn (StatusComponent $c): bool => str_starts_with((string) ($c->names['en'] ?? ''), self::MARK));
        $incidents = Incident::query()->get()->filter(fn (Incident $i): bool => str_starts_with((string) ($i->titles['en'] ?? ''), self::MARK));
        $tickets = Ticket::query()->where('email_hash', EventRegistration::hash('sample@example.org'))->get();

        $tickets->each(function (Ticket $ticket): void {
            $ticket->messages()->delete();
            $ticket->delete();
        });
        $incidents->each(fn (Incident $incident) => $incident->delete());
        $components->each(fn (StatusComponent $component) => $component->delete());
        $services->each(fn (ServiceItem $service) => $service->forceDelete());

        $this->components->info(sprintf('Removed %d service(s), %d system(s), %d incident(s), %d ticket(s).', $services->count(), $components->count(), $incidents->count(), $tickets->count()));

        return self::SUCCESS;
    }
}
