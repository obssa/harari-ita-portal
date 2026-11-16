<?php

namespace App\Domain\Innovation\Enums;

/**
 * How far a startup has come (PLAN.md §4: the directory's stage filter).
 */
enum StartupStage: string
{
    case Idea = 'idea';
    case Prototype = 'prototype';
    case Mvp = 'mvp';
    case EarlyRevenue = 'early_revenue';
    case Growth = 'growth';

    public function label(): string
    {
        return __("innovation.stage.{$this->value}");
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
