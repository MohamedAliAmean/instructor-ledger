<?php

namespace App\Support;

class Money
{
    public static function format(int $amountCents, string $currency = 'EGP'): string
    {
        $amount = number_format($amountCents / 100, 2);

        return "{$amount} {$currency}";
    }
}
