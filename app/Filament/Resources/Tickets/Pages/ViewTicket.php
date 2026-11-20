<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Support\Actions\ReplyToTicket;
use App\Domain\Support\Actions\StoreAttachments;
use App\Domain\Support\Actions\UpdateTicket;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Support\CurrentUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One ticket (ADR-0017): the request, the conversation, and the actions to answer, note, resolve and hand over.
 */
class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        $ticket = $this->ticket();
        $actor = CurrentUser::get();

        $files = [];
        foreach ($this->attachments() as $key => $file) {
            $files[] = Action::make("file_{$key}")
                ->label($file['name'])
                ->icon(Heroicon::OutlinedPaperClip)
                ->action(fn (): StreamedResponse => $this->download($key));
        }

        return [
            Action::make('reply')
                ->label(__('support.admin.reply'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => $actor->can('reply', $ticket) && $ticket->status->isOpen())
                ->modalDescription(__('support.admin.reply_help'))
                ->schema([
                    Textarea::make('body')->label(__('support.admin.reply_text'))->required()->rows(8)->maxLength(5000),
                    Toggle::make('wait')->label(__('support.admin.reply_wait')),
                ])
                ->action(function (array $data) use ($ticket): void {
                    app(ReplyToTicket::class)->handle($ticket, CurrentUser::get(), (string) $data['body'], waitForRequester: (bool) ($data['wait'] ?? false));
                    Notification::make()->success()->title(__('support.admin.replied'))->send();
                    $this->reloadTicket();
                }),
            Action::make('note')
                ->label(__('support.admin.note'))
                ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->color('gray')
                ->visible(fn (): bool => $actor->can('reply', $ticket))
                ->schema([Textarea::make('body')->label(__('support.admin.note_text'))->helperText(__('support.admin.note_help'))->required()->rows(5)->maxLength(5000)])
                ->action(function (array $data) use ($ticket): void {
                    app(ReplyToTicket::class)->handle($ticket, CurrentUser::get(), (string) $data['body'], internal: true);
                    Notification::make()->success()->title(__('support.admin.noted'))->send();
                    $this->reloadTicket();
                }),
            Action::make('resolve')
                ->label(__('support.admin.resolve'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $actor->can('reply', $ticket) && $ticket->status->isOpen())
                ->requiresConfirmation()
                ->modalDescription(__('support.admin.resolve_help'))
                ->action(function () use ($ticket): void {
                    app(UpdateTicket::class)->handle($ticket, ['status' => TicketStatus::Resolved->value], CurrentUser::get());
                    Notification::make()->success()->title(__('support.admin.resolved'))->send();
                    $this->reloadTicket();
                }),
            Action::make('handle')
                ->label(__('support.admin.handle'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('gray')
                ->visible(fn (): bool => $actor->can('reply', $ticket))
                ->fillForm(fn (): array => [
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'category' => $ticket->category->value,
                    'office_id' => $ticket->office_id,
                    'assignee_id' => $ticket->assignee_id,
                ])
                ->schema([
                    // ADR-0054: only the moves the current status allows (and the current one, which changes nothing).
                    Select::make('status')->label(__('support.field.status'))->required()
                        ->options(fn (): array => collect([$ticket->status, ...$ticket->status->nextStatuses()])
                            ->mapWithKeys(fn (TicketStatus $status): array => [$status->value => $status->label()])->all()),
                    Select::make('priority')->label(__('support.field.priority'))->options(TicketPriority::options())->required(),
                    Select::make('category')->label(__('support.field.category'))->options(TicketCategory::options())->required(),
                    Select::make('office_id')->label(__('support.field.office'))->searchable()->options(fn (): array => TicketResource::officeOptions())
                        ->disabled(fn (): bool => ! $actor->can('assign', $ticket)),
                    Select::make('assignee_id')->label(__('support.field.assignee'))->searchable()
                        ->options(fn (): array => User::permission('tickets.reply')->orderBy('name')->get()
                            ->filter(fn (User $user): bool => OfficeScope::covers($user, $ticket->office_id, 'tickets.reply'))
                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])->all()),
                ])
                ->action(function (array $data) use ($ticket, $actor): void {
                    if (! $actor->can('assign', $ticket)) {
                        unset($data['office_id']);
                    }
                    app(UpdateTicket::class)->handle($ticket, $data, CurrentUser::get());
                    Notification::make()->success()->title(__('support.admin.updated'))->send();
                    $this->reloadTicket();
                }),
            ...($files === [] ? [] : [ActionGroup::make($files)->label(__('support.admin.attachments'))->icon(Heroicon::OutlinedPaperClip)->button()->color('gray')]),
        ];
    }

    private function reloadTicket(): void
    {
        $this->record = $this->ticket()->refresh();
    }

    private function ticket(): Ticket
    {
        /** @var Ticket $ticket */
        $ticket = $this->getRecord();

        return $ticket;
    }

    /**
     * Every file on the ticket and its messages, keyed "t0" (the request) or "m{id}-{n}".
     *
     * @return array<string, array{path: string, name: string, size: int, mime: string}>
     */
    private function attachments(): array
    {
        $ticket = $this->ticket();
        $files = [];
        foreach ($ticket->files ?? [] as $index => $file) {
            $files["t{$index}"] = $file;
        }
        foreach ($ticket->messages as $message) {
            foreach ($message->files ?? [] as $index => $file) {
                $files["m{$message->id}-{$index}"] = $file;
            }
        }

        return $files;
    }

    private function download(string $key): StreamedResponse
    {
        $ticket = $this->ticket();
        abort_unless(CurrentUser::get()->can('view', $ticket), 403);
        $file = $this->attachments()[$key] ?? null;
        abort_if($file === null || ! Storage::disk(StoreAttachments::DISK)->exists($file['path']), 404);
        activity('support')->performedOn($ticket)->causedBy(CurrentUser::get())->event('downloaded')->log('Attachment downloaded');

        return Storage::disk(StoreAttachments::DISK)->download($file['path'], $file['name'], [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
