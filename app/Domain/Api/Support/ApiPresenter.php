<?php

namespace App\Domain\Api\Support;

use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Content\Models\Category;
use App\Domain\Content\Models\CategoryTranslation;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\PageTranslation;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Content\Models\Tag;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentTranslation;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventTranslation;
use App\Domain\Media\Models\Media;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\OpenData\Models\DatasetTranslation;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\LeaderTranslation;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\OfficeTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectKpiReading;
use App\Domain\Projects\Models\ProjectMilestone;
use App\Domain\Projects\Models\ProjectTranslation;
use App\Domain\Translation\Support\Locales;
use App\Services\Calendar\EthiopianCalendar;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * What the public API (ADR-0056) gives of each kind of record: the fields
 * the public pages show, in the reader's language or the one it falls back
 * to (`lang` says which), with the record's address on the site. Dates are
 * ISO 8601; the main date also comes written in the Ethiopian calendar.
 */
final class ApiPresenter
{
    public function __construct(private readonly EthiopianCalendar $calendar) {}

    /**
     * @return array<string, mixed>
     */
    public function post(Post $post, PostTranslation $t, bool $full = false): array
    {
        $data = [
            'id' => $post->id,
            'type' => $post->type->value,
            'type_label' => $post->type->label(),
            'lang' => $t->locale,
            'slug' => $t->slug,
            'title' => $t->title,
            'kicker' => $t->kicker,
            'excerpt' => $t->excerpt,
            'published_at' => $this->time($t->published_at),
            'published_ethiopian' => $this->ethiopian($t->published_at, $t->locale),
            'updated_at' => $this->time($t->getAttribute('updated_at')),
            'reading_time' => $t->reading_time,
            'featured' => $post->is_featured,
            'sticky' => $post->is_sticky,
            'image' => $this->image($post->featuredImage, 'landscape', $t->locale),
            'categories' => $post->categories->map(fn (Category $category): ?array => $this->term($category, $t->locale))->filter()->values()->all(),
            'url' => route('news.show', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['tags'] = $post->tags->map(fn (Tag $tag): ?array => $this->term($tag, $t->locale))->filter()->values()->all();
            $data['alternates'] = $this->alternates($post, 'news.show');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null null when the page has no address in its language
     */
    public function page(Page $page, PageTranslation $t, bool $full = false): ?array
    {
        $path = $page->path($t->locale);
        if ($path === null) {
            return null;
        }

        $data = [
            'id' => $page->id,
            'parent_id' => $page->parent_id,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'path' => $path,
            'title' => $t->title,
            'excerpt' => $t->excerpt,
            'updated_at' => $this->time($t->getAttribute('updated_at')),
            'image' => $this->image($page->featuredImage, 'landscape', $t->locale),
            'url' => route('page.show', ['locale' => $t->locale, 'path' => $path]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $alternates = [];
            foreach (Locales::all() as $locale) {
                $other = $page->translation($locale, fallback: false);
                $otherPath = $page->path($locale);
                if ($other instanceof PageTranslation && $other->isLive() && $otherPath !== null) {
                    $alternates[$locale] = route('page.show', ['locale' => $locale, 'path' => $otherPath]);
                }
            }
            $data['alternates'] = $alternates;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function event(Event $event, EventTranslation $t, bool $full = false): array
    {
        $data = [
            'id' => $event->id,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'title' => $t->title,
            'summary' => $t->summary,
            'starts_at' => $this->time($event->starts_at),
            'ends_at' => $this->time($event->ends_at),
            'all_day' => $event->all_day,
            'starts_ethiopian' => $this->ethiopian($event->starts_at, $t->locale),
            'venue' => $t->venue,
            'address' => $t->address,
            'online_url' => $event->online_url,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'image' => $this->image($event->cover, 'landscape', $t->locale),
            'office' => $this->office($event->office, $t->locale),
            'url' => route('events.show', ['locale' => $t->locale, 'slug' => $t->slug]),
            'calendar_url' => route('events.ics', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['agenda'] = $t->agenda ?? [];
            $data['registration'] = [
                'state' => $event->registrationState(),
                'places_left' => $event->registration_enabled ? $event->placesLeft() : null,
                'closes_at' => $event->registration_enabled ? $this->time($event->registrationClosesAt()) : null,
                'url' => $event->registration_enabled ? route('events.register', ['locale' => $t->locale, 'slug' => $t->slug]) : null,
            ];
            $data['speakers'] = $event->speakers
                ->map(function (Leader $leader) use ($t): ?array {
                    $shown = PublicDirectory::shown($leader, $t->locale);

                    return $shown instanceof LeaderTranslation ? $this->leader($leader, $shown) : null;
                })
                ->filter()->values()->all();
            $data['alternates'] = $this->alternates($event, 'events.show');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function document(Document $document, DocumentTranslation $t, bool $full = false): array
    {
        $file = $document->fileFor($t->locale);
        $media = $file?->file;

        $data = [
            'id' => $document->id,
            'type' => $document->type->value,
            'type_label' => $document->type->label(),
            'lang' => $t->locale,
            'slug' => $t->slug,
            'title' => $t->title,
            'summary' => $t->summary,
            'year' => $document->year,
            'issued_on' => $document->issued_on?->toDateString(),
            'deadline_at' => $this->time($document->deadline_at),
            'closed' => $document->type->isNotice() ? $document->isClosed() : null,
            'file' => $file !== null && $media !== null ? [
                'lang' => $file->locale,
                'version' => $file->version,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'download_url' => route('documents.download', ['locale' => $t->locale, 'slug' => $t->slug]),
            ] : null,
            'office' => $this->office($document->office, $t->locale),
            'url' => route('documents.show', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['alternates'] = $this->alternates($document, 'documents.show');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function officeRecord(Office $office, OfficeTranslation $t, bool $full = false): array
    {
        $data = [
            'id' => $office->id,
            'type' => $office->type->value,
            'type_label' => $office->type->label(),
            'parent_id' => $office->parent_id,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'name' => $t->name,
            'summary' => $t->summary,
            'address' => $t->address,
            'hours' => $t->hours,
            'phone' => $office->phone,
            'email' => $office->email,
            'latitude' => $office->latitude,
            'longitude' => $office->longitude,
            'image' => $this->image($office->coverImage, 'landscape', $t->locale),
            'url' => route('offices.show', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['head_leader_id'] = $office->head_leader_id;
            $data['alternates'] = $this->alternates($office, 'offices.show');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function leader(Leader $leader, LeaderTranslation $t, bool $full = false): array
    {
        $data = [
            'id' => $leader->id,
            'body' => $leader->body->value,
            'body_label' => $leader->body->label(),
            'office_id' => $leader->office_id,
            'pillar_id' => $leader->pillar_id,
            'rank' => $leader->rank,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'name' => $t->name,
            'position' => $t->position,
            'summary' => $t->summary,
            'term_start' => $leader->term_start?->toDateString(),
            'term_end' => $leader->term_end?->toDateString(),
            'portrait' => $this->image($leader->portrait, 'square', $t->locale),
            'url' => route('leaders.show', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['phone'] = $leader->phone;
            $data['email'] = $leader->email;
            $data['socials'] = $leader->socialLinks();
            $data['alternates'] = $this->alternates($leader, 'leaders.show');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function project(Project $project, ProjectTranslation $t, bool $full = false): array
    {
        $data = [
            'id' => $project->id,
            'status' => $project->status->value,
            'status_label' => $project->status->label(),
            'sector' => $project->sector !== null ? $this->term($project->sector, $t->locale) : null,
            'office_id' => $project->office_id,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'title' => $t->title,
            'summary' => $t->summary,
            'location' => $t->location,
            'starts_on' => $project->starts_on?->toDateString(),
            'ends_on' => $project->ends_on?->toDateString(),
            'progress' => $project->progress,
            'budget' => $project->publicBudget(),
            'spent' => $project->publicSpent(),
            'pillar_id' => $project->pillar_id,
            'rag_status' => $project->publicRag()?->value,
            'featured' => $project->featured,
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'image' => $this->image($project->cover, 'landscape', $t->locale),
            'url' => route('projects.show', ['locale' => $t->locale, 'slug' => $t->slug]),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['alternates'] = $this->alternates($project, 'projects.show');
            $data['milestones'] = $project->milestones->map(fn (ProjectMilestone $m): array => [
                'title' => $m->title($t->locale),
                'due_on' => $m->due_on?->toDateString(),
                'done_on' => $m->done_on?->toDateString(),
                'overdue' => $m->isOverdue(),
            ])->all();
            $data['kpis'] = $project->kpis->map(fn (ProjectKpi $k): array => [
                'name' => $k->name($t->locale),
                'unit' => $k->unit($t->locale) ?: null,
                'baseline' => $k->baseline !== null ? (float) $k->baseline : null,
                'target' => (float) $k->target,
                'higher_is_better' => $k->higher_is_better,
                'progress' => $k->readings->isNotEmpty() ? $k->progress((float) $k->readings->last()->value) : null,
                'readings' => $k->readings->map(fn (ProjectKpiReading $r): array => ['as_of' => $r->as_of->toDateString(), 'value' => (float) $r->value])->all(),
            ])->all();
        }

        return $data;
    }

    /**
     * A dataset with its files (ADR-0026); the full form adds the description, the column names and the preview.
     *
     * @return array<string, mixed>
     */
    public function dataset(Dataset $dataset, DatasetTranslation $t, bool $full = false): array
    {
        $office = $dataset->office !== null ? PublicDirectory::shown($dataset->office, $t->locale) : null;
        $data = [
            'id' => $dataset->id,
            'lang' => $t->locale,
            'slug' => $t->slug,
            'url' => route('datasets.show', ['locale' => $t->locale, 'slug' => $t->slug]),
            'title' => $t->title,
            'summary' => $t->summary,
            'publisher' => $office !== null ? ['id' => (string) $dataset->office_id, 'name' => (string) $office->getAttribute('name')] : null,
            'pillar_id' => $dataset->pillar_id,
            'licence' => ['id' => $dataset->licence->value, 'spdx' => $dataset->licence->spdx(), 'url' => $dataset->licence->url()],
            'frequency' => $dataset->frequency->value,
            'keywords' => $dataset->keywords ?? [],
            'temporal_from' => $dataset->temporal_from?->toDateString(),
            'temporal_to' => $dataset->temporal_to?->toDateString(),
            'data_updated_on' => $dataset->data_updated_on?->toDateString(),
            'next_update_due' => $dataset->nextUpdateDue()?->toDateString(),
            'overdue' => $dataset->isOverdue(),
            'downloads' => $dataset->download_count,
            'distributions' => $dataset->distributions->map(fn (DatasetDistribution $d): array => [
                'id' => $d->id,
                'format' => $d->format->value,
                'title' => $d->title($t->locale),
                'media_type' => $d->format->mediaType(),
                'download_url' => $d->format->isFile() ? route('datasets.download', ['locale' => $t->locale, 'slug' => $t->slug, 'distribution' => $d->id]) : null,
                'access_url' => $d->format->isFile() ? null : $d->url,
                'byte_size' => $d->byte_size,
                'sha256' => $d->checksum,
                'rows' => $d->row_count,
                'downloads' => $d->download_count,
            ])->all(),
        ];

        if ($full) {
            $data['body_html'] = BodyRenderer::toHtml($t->body);
            $data['methodology'] = $t->methodology;
            $data['coverage'] = $t->coverage;
            $data['contact_email'] = $dataset->contact_email;
            $data['files'] = $dataset->distributions->filter(fn (DatasetDistribution $d): bool => $d->columns !== null)
                ->map(fn (DatasetDistribution $d): array => ['id' => $d->id, 'columns' => $d->columns ?? [], 'preview' => $d->preview ?? []])->values()->all();
            $data['alternates'] = $this->alternates($dataset, 'datasets.show');
        }

        return $data;
    }

    /**
     * The largest ready copy of a photo and the other widths, with its alt text.
     *
     * @return array{url: string, alt: string, width: ?int, height: ?int, sizes: array<int, string>}|null
     */
    public function image(?Media $media, string $preset, string $locale): ?array
    {
        if ($media === null || ! $media->isImage()) {
            return null;
        }

        $sizes = [];
        foreach ((array) config("portal.media.presets.{$preset}.widths", []) as $width) {
            if ($media->hasGeneratedConversion("{$preset}-{$width}")) {
                $sizes[(int) $width] = $media->getUrl("{$preset}-{$width}");
            }
        }
        ksort($sizes);

        return [
            'url' => $sizes !== [] ? (string) end($sizes) : $media->getUrl(),
            'alt' => $media->altFor($locale),
            'width' => $media->width,
            'height' => $media->height,
            'sizes' => $sizes,
        ];
    }

    /**
     * @return array{id: string, slug: ?string, name: string}|null
     */
    private function term(Category|Tag $term, string $locale): ?array
    {
        $translation = $term->translation($locale);
        if ($translation === null) {
            return null;
        }

        return [
            'id' => (string) $term->getKey(),
            'slug' => $translation->slug,
            'name' => $translation instanceof CategoryTranslation ? $translation->name : (string) $translation->getAttribute('name'),
        ];
    }

    /**
     * @return array{id: string, name: string, url: string}|null
     */
    private function office(?Office $office, string $locale): ?array
    {
        $shown = $office !== null ? PublicDirectory::shown($office, $locale) : null;
        if ($office === null || ! $shown instanceof OfficeTranslation) {
            return null;
        }

        return [
            'id' => $office->id,
            'name' => $shown->name,
            'url' => route('offices.show', ['locale' => $shown->locale, 'slug' => $shown->slug]),
        ];
    }

    /**
     * The record's address in every language it is live in.
     *
     * @return array<string, string>
     */
    private function alternates(Model&TranslatableModel $record, string $route): array
    {
        $urls = [];
        foreach (Locales::all() as $locale) {
            $translation = $record->translation($locale, fallback: false);
            if ($translation !== null && PublicDirectory::live($translation)) {
                $urls[$locale] = route($route, ['locale' => $locale, 'slug' => $translation->slug]);
            }
        }

        return $urls;
    }

    private function time(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }

    private function ethiopian(?DateTimeInterface $value, string $locale): ?string
    {
        return $value !== null ? $this->calendar->formatEthiopian($value, $locale) : null;
    }
}
