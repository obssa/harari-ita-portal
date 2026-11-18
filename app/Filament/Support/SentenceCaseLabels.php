<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

/**
 * ADR-0052: the admin writes its labels in sentence case, as the public site does ("Incidents and maintenance", not
 * "Incidents And Maintenance"), in the menu, page titles and buttons. Only the first letter is raised, so Amharic
 * and Afaan Oromo labels pass through as written.
 */
trait SentenceCaseLabels
{
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
