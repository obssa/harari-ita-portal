<?php

namespace App\Domain\OpenData\Enums;

/**
 * The open licences a dataset may carry (ADR-0025), each with its official address and SPDX identifier. The Agency
 * chooses per dataset; CC BY 4.0 is offered first.
 */
enum Licence: string
{
    case CcBy = 'cc-by-4.0';
    case CcBySa = 'cc-by-sa-4.0';
    case Cc0 = 'cc0-1.0';
    case OdcBy = 'odc-by-1.0';
    case Odbl = 'odbl-1.0';

    public function label(): string
    {
        return __("opendata.licence.{$this->value}");
    }

    public function url(): string
    {
        return match ($this) {
            self::CcBy => 'https://creativecommons.org/licenses/by/4.0/',
            self::CcBySa => 'https://creativecommons.org/licenses/by-sa/4.0/',
            self::Cc0 => 'https://creativecommons.org/publicdomain/zero/1.0/',
            self::OdcBy => 'https://opendatacommons.org/licenses/by/1-0/',
            self::Odbl => 'https://opendatacommons.org/licenses/odbl/1-0/',
        };
    }

    public function spdx(): string
    {
        return match ($this) {
            self::CcBy => 'CC-BY-4.0',
            self::CcBySa => 'CC-BY-SA-4.0',
            self::Cc0 => 'CC0-1.0',
            self::OdcBy => 'ODC-By-1.0',
            self::Odbl => 'ODbL-1.0',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $licence) {
            $options[$licence->value] = $licence->label();
        }

        return $options;
    }
}
