<?php

namespace App\Domain\Forms\Support;

use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Services\Models\ServiceItem;
use Illuminate\Database\Eloquent\Model;

/**
 * What a form submission is about (ADR-0053): the service someone asked for or the opportunity they applied to.
 * The page passes a token ("service.<id>", "opportunity.<id>") and the server keeps it only when that item is
 * public, really uses this form and, for an opportunity, is still taking applications. A form attached to an
 * opportunity takes nothing without one, so its address cannot be used after the deadline.
 */
final class SubmissionAbout
{
    public const SERVICE = 'service';

    public const OPPORTUNITY = 'opportunity';

    public static function token(string $type, string $id): string
    {
        return "{$type}.{$id}";
    }

    /**
     * The item a token names, when this form may take a message about it in this language.
     *
     * @return ?array{type: string, id: string, title: string, url: string}
     */
    public static function accept(?string $token, Form $form, string $locale): ?array
    {
        [$type, $id] = array_pad(explode('.', (string) $token, 2), 2, '');
        if (preg_match('/^[0-9a-z]{26}$/i', $id) !== 1) {
            return null;
        }

        return match ($type) {
            self::SERVICE => self::service($id, $form, $locale),
            self::OPPORTUNITY => self::opportunity($id, $form, $locale),
            default => null,
        };
    }

    /** Whether this form is an opportunity's application form, so it only takes applications from that page. */
    public static function requiresOpportunity(Form $form): bool
    {
        return Opportunity::query()->where('apply_form_id', $form->id)->where('type', '!=', OpportunityType::Tender->value)->exists();
    }

    /**
     * For the inbox: what the submission is about.
     *
     * @return ?array{kind: string, title: string, record: ServiceItem|Opportunity}
     */
    public static function describe(FormSubmission $submission): ?array
    {
        if ($submission->about_id === null) {
            return null;
        }
        /** @var ServiceItem|Opportunity|null $record */
        $record = match ($submission->about_type) {
            self::SERVICE => ServiceItem::withTrashed()->with('translations')->find($submission->about_id),
            self::OPPORTUNITY => Opportunity::withTrashed()->with('translations')->find($submission->about_id),
            default => null,
        };
        if ($record === null) {
            return null;
        }

        return [
            'kind' => __("forms.about.{$submission->about_type}"),
            'title' => (string) $record->translationOrAny()?->getAttribute('title'),
            'record' => $record,
        ];
    }

    /**
     * @return array<string, string> the kinds a submission can be about, for the inbox filter
     */
    public static function kinds(): array
    {
        return [self::SERVICE => __('forms.about.service'), self::OPPORTUNITY => __('forms.about.opportunity')];
    }

    /**
     * @return ?array{type: string, id: string, title: string, url: string}
     */
    private static function service(string $id, Form $form, string $locale): ?array
    {
        /** @var ServiceItem|null $service */
        $service = PublicDirectory::visible(ServiceItem::query(), $locale)->whereKey($id)->first();
        $shown = $service !== null ? PublicDirectory::shown($service, $locale) : null;
        if ($service === null || $shown === null || ! $service->accepts_requests || $service->request_form_id !== $form->id) {
            return null;
        }

        return self::found(self::SERVICE, $service, $shown->getAttribute('title'), route('services.show', ['locale' => $shown->locale, 'slug' => $shown->getAttribute('slug')]));
    }

    /**
     * @return ?array{type: string, id: string, title: string, url: string}
     */
    private static function opportunity(string $id, Form $form, string $locale): ?array
    {
        /** @var Opportunity|null $opportunity */
        $opportunity = PublicDirectory::visible(Opportunity::query(), $locale)->whereKey($id)->first();
        $shown = $opportunity !== null ? PublicDirectory::shown($opportunity, $locale) : null;
        if ($opportunity === null || $shown === null || $opportunity->apply_form_id !== $form->id
            || $opportunity->type === OpportunityType::Tender || ! $opportunity->isOpen()) {
            return null;
        }

        return self::found(self::OPPORTUNITY, $opportunity, $shown->getAttribute('title'), route('opportunities.show', ['locale' => $shown->locale, 'slug' => $shown->getAttribute('slug')]));
    }

    /**
     * @return array{type: string, id: string, title: string, url: string}
     */
    private static function found(string $type, Model $record, mixed $title, string $url): array
    {
        return ['type' => $type, 'id' => (string) $record->getKey(), 'title' => (string) $title, 'url' => $url];
    }
}
