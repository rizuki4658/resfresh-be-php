<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LoginAttempt
 *
 * @property int $id
 * @property string|null $email
 * @property string $ip_address
 * @property string|null $user_agent
 * @property bool $successful
 * @property string|null $failure_reason
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt failed()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt recentByEmail($email, $minutes = 60)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt recentByIp($ip, $minutes = 60)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereUserAgent($value)
 * @mixin \Eloquent
 */
class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'metadata'
    ];
    
    protected $casts = [
        'successful' => 'boolean',
        'metadata' => 'array',
    ];
    
    // Scope untuk query failed attempts
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }
    
    // Scope untuk query by IP dalam time window
    public function scopeRecentByIp($query, $ip, $minutes = 60)
    {
        return $query->where('ip_address', $ip)
                     ->where('created_at', '>=', now()->subMinutes($minutes));
    }
    
    // Scope untuk query by email dalam time window
    public function scopeRecentByEmail($query, $email, $minutes = 60)
    {
        return $query->where('email', $email)
                     ->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
