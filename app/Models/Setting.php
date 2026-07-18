<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);

        return $row ? json_decode($row->value, true) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value)],
        );
    }

    /**
     * Resolve a setting that stores a `public`-disk file path (e.g. a
     * signature image) to its public URL, matching how Student::photo_url
     * turns photo_path into a URL.
     */
    public static function fileUrl(string $key): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
