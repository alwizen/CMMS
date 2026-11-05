<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @var array<string, string|null>
     */
    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::$cache[$key] ??= static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return static::decode($value);
    }

    public static function set(string $key, mixed $value): void
    {
        $encoded = static::encode($value);

        static::query()->updateOrCreate(['key' => $key], ['value' => $encoded]);

        static::$cache[$key] = $encoded;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($key, $value);
        }
    }

    public static function forget(string $key): void
    {
        unset(static::$cache[$key]);
    }

    protected static function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    protected static function decode(string $value): mixed
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
