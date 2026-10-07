<?php

namespace App\Support;

use App\Models\User;
use Closure;

class PhoneNumber
{
    public static function normalize(?string $phone): ?string
    {
        if (! $phone) return null;
        return str_starts_with($phone, '09') ? '+63'.substr($phone, 1) : $phone;
    }

    public static function uniqueRule(?int $ignoreUserId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreUserId): void {
            if (! is_string($value) || $value === '') return;
            $query = User::where('phone', self::normalize($value));
            if ($ignoreUserId !== null) $query->where('id', '!=', $ignoreUserId);
            if ($query->exists()) $fail('This mobile number is already used by another account.');
        };
    }
}
