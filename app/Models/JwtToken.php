<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\JwtToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_id
 * @property string $token
 * @property string|null $device_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $is_revoked
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken active()
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken revoked()
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereIsRevoked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JwtToken whereUserId($value)
 * @mixin \Eloquent
 */
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
