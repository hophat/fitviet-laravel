<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'duration', // Thời hạn tính bằng tháng
        'price',
        'features',
        'status',
        'max_freeze_days',
        'guest_passes',
        'pt_sessions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'price' => 'decimal:0',
        'features' => 'array',
        'max_freeze_days' => 'integer',
        'guest_passes' => 'integer',
        'pt_sessions' => 'integer',
    ];

    /**
     * Get the members with this membership.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_memberships')
            ->withPivot('start_date', 'end_date', 'status', 'payment_status', 'payment_amount')
            ->withTimestamps();
    }

    /**
     * Filter memberships by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter memberships by duration
     */
    public function scopeWithDuration($query, $duration)
    {
        if ($duration) {
            return $query->where('duration', $duration);
        }
        
        return $query;
    }
} 