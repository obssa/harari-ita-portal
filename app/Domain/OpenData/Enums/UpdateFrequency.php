<?php

namespace App\Domain\OpenData\Enums;

use Carbon\CarbonImmutable;

/**
 * How often a dataset is updated (ADR-0025), with the matching term of the EU frequency vocabulary that DCAT-AP uses.
 * The next update falls due one period after the last; irregular and never have no due date.
 */
enum UpdateFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Biannual = 'biannual';
    case Annual = 'annual';
    case Irregular = 'irregular';
    case Never = 'never';

    public function label(): string
    {
        return __("opendata.frequency.{$this->value}");
    }

    /** The EU Publications Office frequency term (DCAT-AP `dct:accrualPeriodicity`). */
    public function euUri(): string
    {
        return 'http://publications.europa.eu/resource/authority/frequency/'.match ($this) {
            self::Daily => 'DAILY',
            self::Weekly => 'WEEKLY',
            self::Monthly => 'MONTHLY',
            self::Quarterly => 'QUARTERLY',
            self::Biannual => 'ANNUAL_2',
            self::Annual => 'ANNUAL',
            self::Irregular => 'IRREG',
            self::Never => 'NEVER',
        };
    }

    public function nextDue(CarbonImmutable $last): ?CarbonImmutable
    {
        return match ($this) {
            self::Daily => $last->addDay(),
            self::Weekly => $last->addWeek(),
            self::Monthly => $last->addMonthNoOverflow(),
            self::Quarterly => $last->addMonthsNoOverflow(3),
            self::Biannual => $last->addMonthsNoOverflow(6),
            self::Annual => $last->addYearNoOverflow(),
            self::Irregular, self::Never => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $frequency) {
            $options[$frequency->value] = $frequency->label();
        }

        return $options;
    }
}
