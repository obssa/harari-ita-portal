<?php

namespace App\Filament\Support;

use App\Domain\Content\Models\Post;
use App\Domain\Engagement\Actions\DraftCampaign;
use App\Domain\Opportunities\Models\Opportunity;
use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * "Draft an email campaign" on an advisory or an opportunity (ADR-0035): makes a campaign draft from the languages
 * that are live and opens it, to be read, tested and sent as any campaign.
 */
final class DraftCampaignAction
{
    public static function make(): Action
    {
        return Action::make('draftCampaign')
            ->label(__('campaigns.draft.action'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Model $record): bool => DraftCampaign::supports($record) && CurrentUser::get()->can('campaigns.manage'))
            ->requiresConfirmation()
            ->modalHeading(__('campaigns.draft.action'))
            ->modalDescription(fn (Model $record): string => $record instanceof Opportunity ? __('campaigns.draft.help_opportunity') : __('campaigns.draft.help_advisory'))
            ->action(function (Model $record, Action $action): void {
                try {
                    /** @var Post|Opportunity $record */
                    $campaign = app(DraftCampaign::class)->handle($record, CurrentUser::get());
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                    $action->halt();

                    return;
                }
                Notification::make()->success()->title(__('campaigns.draft.done'))->send();
                $action->redirect(CampaignResource::getUrl('edit', ['record' => $campaign]));
            });
    }
}
