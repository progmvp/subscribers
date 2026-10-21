<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SubscrWpApiToken extends Model
{
    protected $table = 'subscr_wp_api_tokens';

    protected $fillable = [
        'name',
        'token',
        'base_url',
        'username',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Encrypt token before saving.
     */
    public function setTokenAttribute($value): void
    {
        $this->attributes['token'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt token when reading.
     */
    public function getTokenAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::decryptString($value);
    }

    /**
     * Return the currently active WP API token.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }
}
