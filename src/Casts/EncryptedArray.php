<?php

namespace Gpos\FilamentJibble\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EncryptedArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (blank($value)) {
            return [];
        }

        return json_decode(Crypt::decryptString($value), true) ?? [];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value));
    }
}
