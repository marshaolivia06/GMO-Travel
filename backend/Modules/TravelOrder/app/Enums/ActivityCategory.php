<?php

namespace Modules\TravelOrder\Enums;

enum ActivityCategory: string
{
    case Training = 'TRN';
    case Audit = 'AUD';
    case StockOpname = 'SO';
    case Meeting = 'MTG';
    case SiteVisit = 'SV';
    case Other = 'OTH';

    public function label(): string
    {
        return match ($this) {
            self::Training => 'Training',
            self::Audit => 'Audit',
            self::StockOpname => 'Stock Opname',
            self::Meeting => 'Meeting',
            self::SiteVisit => 'Site Visit',
            self::Other => 'Other',
        };
    }

    public function isActive(): bool
    {
        return !in_array($this, self::inactive(), true);
    }

    protected static function inactive(): array
    {
        return [];
    }

    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case->isActive()));
    }

    public static function options(bool $onlyActive = true): array
    {
        return array_map(
            fn (self $case) => [
                'code' => $case->value,
                'display_label' => $case->label(),
                'active' => $case->isActive(),
            ],
            $onlyActive ? self::active() : self::cases()
        );
    }
}
