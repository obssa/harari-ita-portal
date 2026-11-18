<?php

namespace App\Domain\Support\Enums;

/**
 * What a support request is about (PLAN.md §5).
 */
enum TicketCategory: string
{
    /** Asking for one of the Agency's services from its page (ADR-0053). */
    case ServiceRequest = 'service_request';
    case Hardware = 'hardware';
    case Network = 'network';
    case Software = 'software';
    case Account = 'account';
    case Website = 'website';
    case Other = 'other';

    public function label(): string
    {
        return __("support.category.{$this->value}");
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
