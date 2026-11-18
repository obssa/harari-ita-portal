<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ADR-0043: Harari is hidden from visitors until its interface strings are entered (it falls back to Amharic).
        $this->migrator->add('site.hidden_locales', ['har']);
    }
};
