<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JwtToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'token',
        'device_name',
        'ip_address',
        'user_agent',
        'is_revoked',
        'expires_at'
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
                    ->where('expires_at', '>', now());
    }

    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    public function revoke()
    {
        $this->update(['is_revoked' => true]);
    }
}
