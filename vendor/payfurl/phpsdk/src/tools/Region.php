<?php

namespace payFURL\Sdk;

class Region
{
    const AU = "au";
    const JP = "jp";
    const US = "us";
    const NONE = "none";

    private static array $LabelToRegionMapping = [
        'au' => self::AU,
        'jp' => self::JP,
        'us' => self::US,
        'none' => self::NONE,
    ];

    public static function fromLabel($label): ?string
    {
        return self::$LabelToRegionMapping[$label] ?? null;
    }
}
