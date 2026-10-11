<?php
if (!defined('ABSPATH')) exit;

/**
 * Utility class for financial calculations in Sportedia.
 */
class Sportedia_Finance {
    public const VAT_RATE = 0.05; // 5% VAT in UAE

    public static function calculate_vat($amount) {
        $base = floatval($amount);
        $vat = round($base * self::VAT_RATE, 2);
        $total = round($base + $vat, 2);

        return array(
            'base'  => $base,
            'vat'   => $vat,
            'total' => $total
        );
    }

    public static function format_price($amount) {
        return 'AED ' . number_format(floatval($amount), 2);
    }
}
