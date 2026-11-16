<?php

use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Translation\Actions\EditUiTranslation;
use App\Domain\Translation\Models\GlossaryTerm;
use App\Domain\Translation\Support\Glossary;
use App\Domain\Translation\Support\TranslationRows;
use App\Filament\Resources\GlossaryTerms\Pages\ManageGlossaryTerms;

use function Pest\Livewire\livewire;

it('finds approved terms as whole words and checks the translation uses them', function () {
    GlossaryTerm::query()->create(['terms' => ['en' => 'Sample Agency', 'am' => 'ናሙና ኤጀንሲ', 'om' => 'Ejansii Fakkeenyaa'], 'notes' => 'Never shortened']);

    $glossary = app(Glossary::class);
    expect($glossary->check('About the Sample Agency office', 'ስለ ናሙና ኤጀንሲ ጽሕፈት ቤት', 'am'))->toBe([
        ['en' => 'Sample Agency', 'term' => 'ናሙና ኤጀንሲ', 'notes' => 'Never shortened', 'followed' => true],
    ])
        ->and($glossary->breaks('About the sample agency office', 'Waa\'ee waajjira ejansii', 'om'))->toBeTrue()
        ->and($glossary->check('Sample Agencying', 'x', 'am'))->toBe([])
        // No Harari term yet: nothing to break.
        ->and($glossary->breaks('Sample Agency', 'anything', 'har'))->toBeFalse();
});

it('flags translated strings that miss a glossary term in the grid', function () {
    $admin = staff(SystemRole::SuperAdmin);
    app(EditUiTranslation::class)->handle('nav.home', 'en', 'Sample Agency home', $admin);
    app(EditUiTranslation::class)->handle('nav.home', 'om', 'Fuula jalqabaa', $admin);
    GlossaryTerm::query()->create(['terms' => ['en' => 'Sample Agency', 'om' => 'Ejansii Fakkeenyaa']]);

    expect(app(TranslationRows::class)->for('om')['nav.home']['status'])->toBe(TranslationRows::GLOSSARY);

    app(EditUiTranslation::class)->handle('nav.home', 'om', 'Fuula Ejansii Fakkeenyaa', $admin);
    expect(app(TranslationRows::class)->for('om')['nav.home']['status'])->toBe(TranslationRows::TRANSLATED);
});

it('lets glossary keepers add terms, and lets translators read them', function () {
    $this->actingAs(staff(SystemRole::Author))->get('/admin/glossary-terms')->assertForbidden();

    $translator = staff(SystemRole::Translator);
    $translator->assignScopedRole(SystemRole::Translator, ScopeType::Language, 'om');
    $this->actingAs($translator->refresh());
    livewire(ManageGlossaryTerms::class)
        ->callAction('create', data: ['terms' => ['en' => 'Sample Medemer', 'am' => 'ናሙና መደመር', 'om' => 'Ida\'amuu fakkeenyaa'], 'notes' => 'Sample note'])
        ->assertHasNoActionErrors();
    expect(GlossaryTerm::query()->firstOrFail()->term('om'))->toBe('Ida\'amuu fakkeenyaa');

    livewire(ManageGlossaryTerms::class)->callAction('create', data: ['terms' => ['am' => 'ብቻ']])->assertHasActionErrors(['terms.en']);
});
