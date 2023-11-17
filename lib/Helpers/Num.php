<?php

namespace PHPNomad\Utils\Helpers;

class Num
{
    public static function toPercentageFloat(int $percent): float
    {
        return $percent / 100;
    }

    public static function calculatePercentage(int $amount, int $percent): float
    {
        return $amount * static::toPercentageFloat($percent);
    }
}