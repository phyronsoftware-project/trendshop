<?php

namespace App\Support;

final class CambodiaProvince
{
    /** @var array<int, string> */
    private const NAMES = [
        'Banteay Meanchey',
        'Battambang',
        'Kampong Cham',
        'Kampong Chhnang',
        'Kampong Speu',
        'Kampong Thom',
        'Kampot',
        'Kandal',
        'Kep',
        'Koh Kong',
        'Kratie',
        'Mondulkiri',
        'Oddar Meanchey',
        'Pailin',
        'Phnom Penh',
        'Preah Sihanouk',
        'Preah Vihear',
        'Prey Veng',
        'Pursat',
        'Ratanakiri',
        'Siem Reap',
        'Stung Treng',
        'Svay Rieng',
        'Takeo',
        'Tboung Khmum',
    ];

    /** @return array<int, string> */
    public static function names(): array
    {
        return self::NAMES;
    }

    /** @return array<string, float> */
    public static function defaultFees(): array
    {
        $fees = array_fill_keys(self::NAMES, 2.0);
        $fees['Phnom Penh'] = 1.0;

        return $fees;
    }

    /** @return array<string, float> */
    public static function feesFromJson(?string $value): array
    {
        $fees = self::defaultFees();
        $storedFees = json_decode($value ?? '', true);

        if (! is_array($storedFees)) {
            return $fees;
        }

        foreach ($fees as $province => $defaultFee) {
            if (isset($storedFees[$province]) && is_numeric($storedFees[$province])) {
                $fees[$province] = max(0, (float) $storedFees[$province]);
            }
        }

        return $fees;
    }
}
