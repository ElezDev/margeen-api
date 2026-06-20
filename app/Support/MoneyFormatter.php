<?php

namespace App\Support;

class MoneyFormatter
{
    public static function cop(float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '$ 0';
        }

        return '$ '.number_format((float) $amount, 0, ',', '.');
    }
}
