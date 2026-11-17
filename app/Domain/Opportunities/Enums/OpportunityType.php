<?php

namespace App\Domain\Opportunities\Enums;

/**
 * The kinds of opportunity (ADR-0032). Vacancies and internships are jobs: they have positions and JobPosting
 * JSON-LD; tenders have a bid opening.
 */
enum OpportunityType: string
{
    case Tender = 'tender';
    case Call = 'call';
    case Vacancy = 'vacancy';
    case Internship = 'internship';

    public function label(?string $locale = null): string
    {
        return __("opportunities.type.{$this->value}", [], $locale);
    }

    public function plural(): string
    {
        return __("opportunities.types.{$this->value}");
    }

    public function isJob(): bool
    {
        return $this === self::Vacancy || $this === self::Internship;
    }

    public function tone(): string
    {
        return match ($this) {
            self::Tender => 'primary',
            self::Call => 'cyan',
            self::Vacancy => 'saffron',
            self::Internship => 'ruby',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
