<?php

namespace App\Support;

class Money
{
    /**
     * Định dạng số tiền theo chuẩn Việt Nam: 1.234.567đ
     */
    public static function vnd($amount, bool $withSymbol = true): string
    {
        $formatted = number_format((float) $amount, 0, ',', '.');

        return $withSymbol ? $formatted . 'đ' : $formatted;
    }
}
