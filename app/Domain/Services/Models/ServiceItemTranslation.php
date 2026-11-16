<?php

namespace App\Domain\Services\Models;

use App\Domain\Content\Concerns\PublishedPerLanguage;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Translation;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Search\Concerns\SearchableTranslation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One language of a service. Documents and steps are written one per line; the FAQ is a list of questions and
 * answers.
 *
 * @property string $service_item_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $eligibility
 * @property ?string $documents
 * @property ?string $steps
 * @property ?string $fees
 * @property ?string $duration
 * @property ?list<array<string, mixed>> $faq read through faqList()
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property ?CarbonImmutable $updated_at
 * @property-read ServiceItem $serviceItem
 */
class ServiceItemTranslation extends Translation
{
    use LogsActivity;
    use PublishedPerLanguage;
    use SearchableTranslation;

    protected string $sluggable = 'title';

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'faq' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return list<?string>
     */
    public function searchableParts(): array
    {
        return [$this->title, $this->summary, $this->eligibility, $this->documents, $this->steps, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('services')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'eligibility', 'documents', 'steps', 'fees', 'duration', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<ServiceItem, $this>
     */
    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }

    /**
     * The documents to bring, one per line.
     *
     * @return list<string>
     */
    public function documentList(): array
    {
        return self::lines($this->documents);
    }

    /**
     * The steps in order, one per line.
     *
     * @return list<string>
     */
    public function stepList(): array
    {
        return self::lines($this->steps);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqList(): array
    {
        $items = [];
        foreach ($this->faq ?? [] as $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question !== '' && $answer !== '') {
                $items[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private static function lines(?string $text): array
    {
        return array_values(array_filter(
            // A bullet or a list number ("-", "•", "1.", "2)") at the start is the editor's, not part of the line.
            array_map(fn (string $line): string => trim((string) preg_replace('/^\s*(?:[-*•]|\d{1,2}[.)])\s+/u', '', $line)), preg_split('/\R/u', (string) $text) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }
}
