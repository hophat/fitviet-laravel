<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'trainer_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'location',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the member associated with the schedule.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the trainer associated with the schedule.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    /**
     * Get the user who created the schedule.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Filter schedules by date
     */
    public function scopeOnDate($query, $date)
    {
        if ($date) {
            return $query->whereDate('date', $date);
        }
        
        return $query;
    }

    /**
     * Filter schedules by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereDate('date', '>=', $startDate)
                         ->whereDate('date', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Filter upcoming schedules
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                     ->orderBy('date')
                     ->orderBy('start_time');
    }

    /**
     * Filter by status
     */
    public function scopeWithStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        
        return $query;
    }
} 