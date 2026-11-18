<?php

namespace App\Domain\Forms\Support;

use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Services\Models\ServiceItem;

/**
 * What a form submission is about (ADR-0053): the service someone asked for. The page passes a token
 * ("service.<id>") and the server only keeps it when that item is public and really uses this form, so a
 * request can never be filed against something the form does not serve.
 */
final class SubmissionAbout
{
    public const SERVICE = 'service';

    public static function token(string $type, string $id): string
    {
        return "{$type}.{$id}";
    }

    /**
     * The item a token names, when this form may take requests about it in this language.
     *
     * @return ?array{type: string, id: string, title: string, url: string}
     */
    public static function accept(?string $token, Form $form, string $locale): ?array
    {
        [$type, $id] = array_pad(explode('.', (string) $token, 2), 2, '');
        if ($type !== self::SERVICE || preg_match('/^[0-9a-z]{26}$/i', $id) !== 1) {
            return null;
        }

        /** @var ServiceItem|null $service */
        $service = PublicDirectory::visible(ServiceItem::query(), $locale)->whereKey($id)->first();
        $shown = $service !== null ? PublicDirectory::shown($service, $locale) : null;
        if ($service === null || $shown === null || ! $service->accepts_requests || $service->request_form_id !== $form->id) {
            return null;
        }

        return [
            'type' => self::SERVICE,
            'id' => $service->id,
            'title' => (string) $shown->getAttribute('title'),
            'url' => route('services.show', ['locale' => $shown->locale, 'slug' => $shown->getAttribute('slug')]),
        ];
    }

    /**
     * For the inbox: what the submission is about.
     *
     * @return ?array{kind: string, title: string, record: ServiceItem}
     */
    public static function describe(FormSubmission $submission): ?array
    {
        if ($submission->about_type !== self::SERVICE || $submission->about_id === null) {
            return null;
        }
        $service = ServiceItem::withTrashed()->with('translations')->find($submission->about_id);
        if ($service === null) {
            return null;
        }

        return [
            'kind' => __('forms.about.service'),
            'title' => (string) $service->translationOrAny()?->getAttribute('title'),
            'record' => $service,
        ];
    }

    /**
     * @return array<string, string> the kinds a submission can be about, for the inbox filter
     */
    public static function kinds(): array
    {
        return [self::SERVICE => __('forms.about.service')];
    }
}
