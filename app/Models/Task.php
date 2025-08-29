<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Task
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property \Illuminate\Support\Carbon|null $deadline
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereUserId($value)
 * @mixin \Eloquent
 */
class Task extends Model
{
    protected $fillable = [
        'user_id',
        'title', 
        'description',
        'status',
        'deadline'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'pending'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes Search
    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    // Status Filter Scope
    public function scopeStatus(Builder $query, $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }
        return $query->where('status', $status);
    }

    // Scopes
    public function scopeOwned($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

     // Date Range Scopes
    public function scopeDueBefore(Builder $query, $date)
    {
        return $query->whereDate('deadline', '<=', $date);
    }

    public function scopeDueAfter(Builder $query, $date)
    {
        return $query->whereDate('deadline', '>=', $date);
    }

    public function scopeDueBetween(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('deadline', [$startDate, $endDate]);
    }

    // Overdue Scope
    public function scopeOverdue(Builder $query)
    {
        return $query->where('deadline', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // Upcoming Tasks Scope
    public function scopeUpcoming(Builder $query, $days = 7)
    {
        return $query->whereBetween('deadline', [now(), now()->addDays($days)])
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // Dynamic Sorting Scope
    public function scopeSortBy(Builder $query, $column, $direction = 'asc')
    {
        $allowedColumns = ['title', 'status', 'deadline', 'created_at', 'updated_at'];
        
        if (!in_array($column, $allowedColumns)) {
            $column = 'created_at';
        }
        
        $direction = in_array(strtolower($direction), ['asc', 'desc']) 
                    ? $direction 
                    : 'asc';
        
        return $query->orderBy($column, $direction);
    }

    // Helpers
    public function isOverdue()
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function canTransitionTo($status)
    {
        $transitions = [
            'pending' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled', 'pending'],
            'completed' => [],
            'cancelled' => ['pending']
        ];

        return in_array($status, $transitions[$this->status] ?? []);
    }
}
