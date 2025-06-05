<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCheckin extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member_checkins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'checkin_time',
        'checkout_time',
        'checkin_method',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
    ];

    /**
     * Get the member that owns this check-in.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Scope a query to only include today's check-ins.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('checkin_time', today());
    }

    /**
     * Scope a query to only include this week's check-ins.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('checkin_time', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to only include this month's check-ins.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('checkin_time', now()->month)
                     ->whereYear('checkin_time', now()->year);
    }

    /**
     * Get the duration of the check-in in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->checkout_time) {
            return null;
        }

        return $this->checkin_time->diffInMinutes($this->checkout_time);
    }

    /**
     * Get the duration of the check-in in human readable format.
     */
    public function getDurationHumanAttribute(): ?string
    {
        if (!$this->checkout_time) {
            return null;
        }

        return $this->checkin_time->diffForHumans($this->checkout_time, true);
    }

    /**
     * Check if the member is currently checked in.
     */
    public function isActive(): bool
    {
        return $this->checkout_time === null;
    }

    /**
     * Check out the member.
     */
    public function checkout(): void
    {
        $this->update(['checkout_time' => now()]);
    }
}