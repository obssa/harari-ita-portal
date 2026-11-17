<?php

namespace App\Services\Cdn;

use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseSession;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Content\Models\Category;
use App\Domain\Content\Models\CategoryTranslation;
use App\Domain\Content\Models\Menu;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\PageTranslation;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Content\Models\Redirect;
use App\Domain\Content\Models\Tag;
use App\Domain\Content\Models\TagTranslation;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentTranslation;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventTranslation;
use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormTranslation;
use App\Domain\Homepage\Models\HeroSlide;
use App\Domain\Homepage\Models\HomepageSection;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTranslation;
use App\Domain\Innovation\Models\Cohort;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\ProgrammeTranslation;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Innovation\Models\StartupTranslation;
use App\Domain\Media\Models\Gallery;
use App\Domain\Media\Models\GalleryItem;
use App\Domain\Media\Models\GalleryTranslation;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\Video;
use App\Domain\Media\Models\VideoTranslation;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetTranslation;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\LeaderTranslation;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\OfficeTranslation;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Organisation\Models\PillarTranslation;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectTranslation;
use App\Domain\Projects\Models\Statistic;
use App\Domain\Projects\Models\StatisticTranslation;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Services\Models\ServiceItemTranslation;
use App\Domain\Social\Models\SocialPost;
use App\Domain\Social\Models\SocialSource;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\IncidentUpdate;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Translation\Models\UiTranslation;
use App\Settings\CarouselSettings;
use App\Settings\PressKitSettings;
use App\Settings\SiteSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

/**
 * What the public sees changed (ADR-0039): a save or delete of anything shown
 * on the public site, or of the site and press settings, asks for a purge of
 * the edge cache. Registrations, tickets and staff records are not public.
 */
final class PublicContentChanges
{
    /** @var list<class-string> */
    public const MODELS = [
        Category::class, CategoryTranslation::class, Menu::class, Page::class, PageTranslation::class,
        Post::class, PostTranslation::class, Redirect::class, Tag::class, TagTranslation::class,
        Document::class, DocumentTranslation::class, DocumentVersion::class, Event::class, EventTranslation::class,
        Form::class, FormTranslation::class, HeroSlide::class, HomepageSection::class, Gallery::class, GalleryItem::class,
        GalleryTranslation::class, Media::class, Video::class, VideoTranslation::class, Leader::class,
        LeaderTranslation::class, Pillar::class, PillarTranslation::class, Office::class, OfficeTranslation::class, ServiceItem::class, ServiceItemTranslation::class, StatusComponent::class, Incident::class, IncidentUpdate::class, Startup::class, StartupTranslation::class, Programme::class, ProgrammeTranslation::class, Cohort::class, Challenge::class, ChallengeTranslation::class,
        Project::class, ProjectTranslation::class, Statistic::class, StatisticTranslation::class, Dataset::class, DatasetTranslation::class,
        Course::class, CourseTranslation::class, CourseCohort::class, CourseSession::class, Instructor::class,
        Opportunity::class, OpportunityTranslation::class, OpportunityAddendum::class,
        SocialPost::class, SocialSource::class, UiTranslation::class,
    ];

    /** @var list<class-string> */
    public const SETTINGS = [SiteSettings::class, PressKitSettings::class, CarouselSettings::class];

    /**
     * @param  array<int, mixed>  $payload
     */
    public function model(string $event, array $payload): void
    {
        $model = $payload[0] ?? null;
        if (is_object($model) && in_array($model::class, self::MODELS, true)) {
            PurgeEdgeCache::soon();
        }
    }

    public function settings(SettingsSaved $event): void
    {
        if (in_array($event->settings::class, self::SETTINGS, true)) {
            PurgeEdgeCache::soon();
        }
    }
}
