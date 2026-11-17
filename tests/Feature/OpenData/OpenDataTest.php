<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Api\Actions\IssueApiKey;
use App\Domain\Media\Actions\UploadMedia;
use App\Domain\Media\Models\Media;
use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Actions\SaveDistribution;
use App\Domain\OpenData\Enums\UpdateFrequency;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\OpenData\Models\DatasetDownload;
use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\Datasets\RelationManagers\DistributionsRelationManager;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\Support\TestFiles;

use function Pest\Livewire\livewire;

function csvMedia(string $content, string $name = 'sample.csv'): Media
{
    return app(UploadMedia::class)->handle(TestFiles::text($content, 'csv'), $name);
}

/**
 * @param  array<string, mixed>  $data
 */
function sampleDataset(string $title = 'Sample internet access', array $data = []): Dataset
{
    return app(SaveDataset::class)->handle(new Dataset, [
        'translations' => ['en' => ['title' => $title, 'summary' => 'Sample summary of the data.', 'coverage' => 'Sample region', 'published' => true]],
        'licence' => 'cc-by-4.0',
        'frequency' => 'annual',
        'keywords' => ['sample', 'internet', 'Sample'],
        ...$data,
    ], staff(SystemRole::DataSteward));
}

it('saves a dataset with its licence, frequency, period and keywords, and works out when the next update is due', function () {
    $dataset = sampleDataset(data: ['temporal_from' => '2024-01-01', 'temporal_to' => '2024-12-31', 'data_updated_on' => '2025-01-15', 'contact_email' => 'data@example.org']);

    expect($dataset->frequency)->toBe(UpdateFrequency::Annual)
        ->and($dataset->keywords)->toBe(['sample', 'internet'])
        ->and($dataset->nextUpdateDue()?->toDateString())->toBe('2026-01-15')
        ->and($dataset->isOverdue(CarbonImmutable::parse('2026-02-01')))->toBeTrue()
        ->and($dataset->isOverdue(CarbonImmutable::parse('2025-12-01')))->toBeFalse();

    expect(fn () => sampleDataset('Sample', ['licence' => 'all-rights-reserved']))->toThrow(ValidationException::class)
        ->and(fn () => sampleDataset('Sample', ['temporal_from' => '2025-01-01', 'temporal_to' => '2024-01-01']))->toThrow(ValidationException::class)
        ->and(fn () => sampleDataset('Sample', ['contact_email' => 'not an address']))->toThrow(ValidationException::class)
        ->and(fn () => sampleDataset('Sample', ['data_updated_on' => CarbonImmutable::tomorrow()->toDateString()]))->toThrow(ValidationException::class);

    // Only a Data Steward with the publish permission publishes a language.
    $draft = app(SaveDataset::class)->handle(new Dataset, ['translations' => ['en' => ['title' => 'Sample draft data', 'published' => true]]], staff(SystemRole::ProjectManager));
    expect($draft->translation('en')?->isLive())->toBeFalse();
});

it('reads a CSV file into its facts and preview, flags columns that look like personal data, and refuses a file of the wrong kind', function () {
    $steward = staff(SystemRole::DataSteward);
    $dataset = sampleDataset();
    $csv = "woreda,schools,connected,contact phone\nSample A,12,9,0911000000\nSample B,8,3,0911000001\n\nSample C,5,5,0911000002\n";
    $file = app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => csvMedia($csv)->id, 'titles' => ['en' => 'Sample 2025 figures']], $steward);

    expect($file->row_count)->toBe(3)
        ->and($file->columns)->toBe(['woreda', 'schools', 'connected', 'contact phone'])
        ->and($file->preview[0])->toBe(['Sample A', '12', '9', '0911000000'])
        ->and($file->personal_columns)->toBe(['contact phone'])
        ->and($file->checksum)->toBe(hash('sha256', $csv))
        ->and($file->title())->toBe('Sample 2025 figures')
        ->and($dataset->refresh()->data_updated_on?->toDateString())->toBe(CarbonImmutable::today()->toDateString());

    $json = app(UploadMedia::class)->handle(TestFiles::text('[{"year":2024,"value":3},{"year":2025,"value":5}]', 'json'), 'sample.json');
    $records = app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'json', 'media_id' => $json->id], $steward);
    expect($records->row_count)->toBe(2)->and($records->columns)->toBe(['year', 'value']);

    $pdf = app(UploadMedia::class)->handle(TestFiles::pdf(), 'sample.pdf');
    expect(fn () => app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => $pdf->id], $steward))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'json', 'media_id' => csvMedia('not json at all')->id], $steward))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'api', 'url' => 'http://example.org/api'], $steward))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'api', 'url' => 'https://example.org/api'], staff(SystemRole::Author)))->toThrow(AuthorizationException::class);
});

it('lists, filters and shows a dataset with its files, preview, licence and JSON-LD, and counts downloads without personal data', function () {
    $dataset = sampleDataset();
    app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => csvMedia("year,users\n2024,1200\n2025,1800\n")->id], staff(SystemRole::DataSteward));
    sampleDataset('Sample other data', ['licence' => 'cc0-1.0', 'keywords' => ['other']]);
    $file = DatasetDistribution::query()->firstOrFail();

    $this->get('/en/data')->assertOk()->assertSee('Sample internet access')->assertSee('Sample other data')->assertSee('2 datasets');
    $this->get('/en/data?format=csv')->assertOk()->assertSee('Sample internet access')->assertDontSee('Sample other data');
    $this->get('/en/data?licence=cc0-1.0')->assertOk()->assertDontSee('Sample internet access')->assertSee('Sample other data');
    $this->get('/en/data?q=internet')->assertOk()->assertSee('Sample internet access')->assertDontSee('Sample other data');

    $this->get('/en/data/sample-internet-access')->assertOk()
        ->assertSee('Download CSV')->assertSee('1800')->assertSee('CC BY 4.0')
        ->assertSee('"@type":"Dataset"', false)->assertSee('https://creativecommons.org/licenses/by/4.0/', false);

    $agent = ['User-Agent' => 'Mozilla/5.0 (Sample browser)'];
    $this->get("/en/data/sample-internet-access/download/{$file->id}", $agent)->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=sample-internet-access.csv')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get("/en/data/sample-internet-access/download/{$file->id}", $agent)->assertOk();
    $this->get("/en/data/sample-internet-access/download/{$file->id}", ['User-Agent' => 'Googlebot'])->assertOk();

    expect($file->refresh()->download_count)->toBe(1)
        ->and($dataset->refresh()->download_count)->toBe(1)
        ->and(DatasetDownload::query()->sole()->count)->toBe(1)
        ->and(array_keys(DatasetDownload::query()->sole()->getAttributes()))->toBe(['id', 'dataset_distribution_id', 'day', 'count']);

    $this->get('/en/data/sample-internet-access/download/999999')->assertNotFound();
});

it('publishes the catalogue in DCAT-AP and the datasets in the API, with the files open without a key', function () {
    $dataset = sampleDataset();
    app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => csvMedia("year,users\n2025,1800\n")->id], staff(SystemRole::DataSteward));

    $this->get('/en/data/catalog.jsonld')->assertOk()
        ->assertHeader('Content-Type', 'application/ld+json')
        ->assertJsonPath('@type', 'dcat:Catalog')
        ->assertJsonPath('dcat:dataset.0.@type', 'dcat:Dataset')
        ->assertJsonPath('dcat:dataset.0.dct:accrualPeriodicity.@id', 'http://publications.europa.eu/resource/authority/frequency/ANNUAL')
        ->assertJsonPath('dcat:dataset.0.dcat:distribution.0.dct:format.@id', 'http://publications.europa.eu/resource/authority/file-type/CSV');

    [, $token] = app(IssueApiKey::class)->handle(['name' => 'Sample data app', 'scopes' => ['datasets']], staff(SystemRole::SuperAdmin));
    $this->getJson('/api/v1/datasets', ['X-Api-Key' => $token])->assertOk()
        ->assertJsonPath('data.0.title', 'Sample internet access')
        ->assertJsonPath('data.0.licence.spdx', 'CC-BY-4.0')
        ->assertJsonPath('data.0.distributions.0.rows', 1);
    $this->getJson("/api/v1/datasets/{$dataset->id}", ['X-Api-Key' => $token])->assertOk()
        ->assertJsonPath('data.files.0.columns', ['year', 'users']);
});

it('manages datasets and their files in the admin, and keeps them from staff without the permission', function () {
    $this->actingAs(staff(SystemRole::Author))->get('/admin/datasets')->assertForbidden();
    $this->actingAs(staff(SystemRole::DataSteward))->get('/admin/datasets')->assertOk();

    livewire(CreateDataset::class)
        ->fillForm(['licence' => 'cc-by-4.0', 'frequency' => 'monthly', 'translations' => ['en' => ['title' => 'Sample admin data', 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();
    $dataset = Dataset::query()->sole();

    livewire(DistributionsRelationManager::class, ['ownerRecord' => $dataset, 'pageClass' => EditDataset::class])
        ->assertOk()
        ->callTableAction('create', data: ['format' => 'api', 'url' => 'https://example.org/sample-api'])
        ->assertHasNoTableActionErrors();

    expect($dataset->distributions()->sole()->url)->toBe('https://example.org/sample-api');
});
