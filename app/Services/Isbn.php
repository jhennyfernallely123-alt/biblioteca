<?php

namespace App\Services;

class Isbn
{
    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/[^0-9Xx]/', '', $value));
    }

    public static function isValid(string $value): bool
    {
        $isbn = self::normalize($value);

        return match (strlen($isbn)) {
            10 => self::isValid10($isbn),
            13 => self::isValid13($isbn),
            default => false,
        };
    }

    public static function to13(string $value): ?string
    {
        $isbn = self::normalize($value);

        if (strlen($isbn) === 13) {
            return self::isValid13($isbn) ? $isbn : null;
        }

        if (strlen($isbn) !== 10 || ! self::isValid10($isbn)) {
            return null;
        }

        $base = '978'.substr($isbn, 0, 9);
        $sum = 0;

        foreach (str_split($base) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }

    private static function isValid10(string $isbn): bool
    {
        if (! preg_match('/^\d{9}[\dX]$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $value = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $sum += $value * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private static function isValid13(string $isbn): bool
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10 === (int) $isbn[12];
    }
}
