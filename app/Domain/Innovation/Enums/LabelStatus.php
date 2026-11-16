<?php

namespace App\Domain\Innovation\Enums;

/**
 * The startup-label claim under the Startup Proclamation (ADR-0020): none, claimed by the founders,
 * or verified by the Agency against the Ministry of Innovation and Technology's certificate.
 */
enum LabelStatus: string
{
    case None = 'none';
    case SelfDeclared = 'self_declared';
    case Verified = 'verified';

    public function label(): string
    {
        return __("innovation.label.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
