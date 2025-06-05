<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'birthday',
        'gender',
        'emergency_contact',
        'health_notes',
        'join_date',
        'status',
        'avatar',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birthday' => 'date',
        'join_date' => 'date',
    ];

    /**
     * Get the memberships of the member.
     */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Membership::class, 'member_memberships')
            ->withPivot('start_date', 'end_date', 'status', 'payment_status', 'payment_amount')
            ->withTimestamps();
    }

    /**
     * Get the schedules of the member.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the trainers associated with the member.
     */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'schedules')
            ->withPivot('date', 'start_time', 'end_time', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the orders of the member.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the check-ins of the member.
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(MemberCheckin::class);
    }

    /**
     * Filter members by status
     */
    public function scopeWithStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        
        return $query;
    }

    /**
     * Filter members by membership status (active/expired)
     */
    public function scopeWithMembershipStatus($query, $status)
    {
        if ($status === 'active') {
            return $query->whereHas('memberships', function($q) {
                $q->where('end_date', '>=', now())
                  ->where('member_memberships.status', 'active');
            });
        } elseif ($status === 'expired') {
            return $query->whereHas('memberships', function($q) {
                $q->where('end_date', '<', now())
                  ->orWhere('member_memberships.status', '!=', 'active');
            });
        }
        
        return $query;
    }
} 