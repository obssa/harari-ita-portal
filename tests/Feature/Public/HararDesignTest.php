<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\LoadContentPack;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Workflow\ContentWorkflow;
use App\Domain\Content\Workflow\Transition;
use App\Domain\Organisation\Models\Pillar;
use App\Support\HararPhotos;
use Illuminate\Support\Facades\File;

it('ships every photo of Harar in each size and format, with its credit', function () {
    foreach (HararPhotos::names() as $name) {
        $photo = HararPhotos::get($name);
        foreach ($photo['widths'] as $width) {
            expect(public_path("images/harar/{$name}-{$width}.avif"))->toBeFile()
                ->and(public_path("images/harar/{$name}-{$width}.webp"))->toBeFile();
        }
        expect(public_path(ltrim(HararPhotos::fallback($name), '/')))->toBeFile()
            ->and(HararPhotos::credit($name))->toContain($photo['author'], $photo['licence'])
            ->and($photo['source'])->toStartWith('https://commons.wikimedia.org/wiki/File:')
            ->and(__("harar.photo.{$name}"))->not->toBe("harar.photo.{$name}");
    }
});

it('opens the homepage on Harar Gate and shows Harar with credited photos', function () {
    $html = (string) $this->get('/en')->assertOk()->getContent();

    expect($html)->toContain('/images/harar/gate-1600.avif', 'fetchpriority="high"', HararPhotos::get('gate')['source'])
        ->toContain('/images/harar/interior-1024.avif', __('content.photo_credit', ['credit' => HararPhotos::credit('interior')]))
        ->toContain(__('homepage.heritage.fact_mosques'));
});

it('gives a story without a photo the party placeholder and keeps every card the same shape', function () {
    $publisher = staff(SystemRole::Publisher, mfa: false);
    $post = app(SavePost::class)->handle(new Post, ['type' => 'speech', 'translations' => ['en' => ['title' => 'Sample speech without a photo']]], $publisher);
    app(ContentWorkflow::class)->apply($post, 'en', Transition::Publish, $publisher);

    $html = (string) $this->get('/en/news')->assertOk()->getContent();

    expect($html)->toContain('Sample speech without a photo', 'from-magenta-600', 'line-clamp-3', 'aspect-[16/10]');
});

it('keeps a colour an editor chose when the pack adds a wing profile later', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $folder = storage_path('framework/testing/pack-'.uniqid());
    File::ensureDirectoryExists($folder);
    $pillar = ['key' => 'pillar.sample', 'translations' => ['en' => ['name' => 'Sample Wing', 'summary' => 'Sample summary.']]];
    file_put_contents("{$folder}/pack.json", json_encode(['pillars' => [$pillar]]));
    app(LoadContentPack::class)->handle("{$folder}/pack.json");

    Pillar::query()->firstOrFail()->forceFill(['accent' => 'night'])->save();
    $body = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Sample work of the wing.']]]]];
    file_put_contents("{$folder}/pack.json", json_encode(['pillars' => [$pillar], 'updates' => [
        ['key' => 'pillar.sample#profile', 'target' => 'pillar.sample', 'accent' => 'magenta', 'body' => ['en' => $body]],
    ]]));

    expect(app(LoadContentPack::class)->handle("{$folder}/pack.json"))->toBe(['updates' => 1]);
    $saved = Pillar::query()->firstOrFail();
    expect($saved->accent)->toBe('night')
        ->and($saved->translation('en')?->getAttribute('body'))->toEqual($body)
        ->and(app(LoadContentPack::class)->handle("{$folder}/pack.json"))->toBe([]);
});
