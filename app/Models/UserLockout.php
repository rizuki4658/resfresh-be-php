<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserLockout
 *
 * @property int $id
 * @property string $identifier
 * @property string $type
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereLockedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserLockout whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserLockout extends Model
{
    protected $fillable = [
        'identifier',
        'type',
        'attempts',
        'locked_until'
    ];
    
    protected $casts = [
        'locked_until' => 'datetime',
        'attempts' => 'integer',
    ];
    
    // Check if currently locked
    public function isLocked()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
    
    // Get remaining lockout time in seconds
    public function getRemainingLockoutTime()
    {
        if (!$this->isLocked()) {
            return 0;
        }
        
        return $this->locked_until->diffInSeconds(now());
    }
    
    // Lock for specified minutes
    public function lockFor($minutes)
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes)
        ]);
    }
    
    // Increment attempts and potentially lock
    public function incrementAttempts($maxAttempts = 5, $lockoutMinutes = 15)
    {
        $this->increment('attempts');
        
        if ($this->attempts >= $maxAttempts) {
            $this->lockFor($lockoutMinutes);
        }
    }
    
    // Reset attempts on successful login
    public function resetAttempts()
    {
        $this->update([
            'attempts' => 0,
            'locked_until' => null
        ]);
    }
}
