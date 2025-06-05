<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMembership extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member_memberships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'membership_id',
        'start_date',
        'end_date',
        'price',
        'payment_method',
        'payment_reference',
        'status',
        'notes',
        'freeze_start_date',
        'freeze_end_date',
        'freeze_reason',
        'cancelled_at',
        'cancellation_reason',
        'refund_amount',
        'refund_method',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'freeze_start_date' => 'date',
        'freeze_end_date' => 'date',
        'cancelled_at' => 'datetime',
        'price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    /**
     * Get the member that owns this membership.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the membership plan.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * Scope a query to only include active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include expired memberships.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                     ->where('end_date', '<', now());
    }

    /**
     * Scope a query to only include frozen memberships.
     */
    public function scopeFrozen($query)
    {
        return $query->where('status', 'frozen');
    }

    /**
     * Scope a query to only include cancelled memberships.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    /**
     * Check if membership is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'active' && $this->end_date < now();
    }

    /**
     * Check if membership is frozen.
     */
    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    /**
     * Get days remaining.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * Get freeze days used.
     */
    public function getFreezeDaysUsedAttribute(): int
    {
        if (!$this->freeze_start_date || !$this->freeze_end_date) {
            return 0;
        }

        return $this->freeze_start_date->diffInDays($this->freeze_end_date);
    }
}