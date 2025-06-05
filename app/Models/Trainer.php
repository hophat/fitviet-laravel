<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
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
        'specialty',
        'experience',
        'certification',
        'bio',
        'join_date',
        'status',
        'rating',
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
        'rating' => 'float',
    ];

    /**
     * Get the schedules of the trainer.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the members associated with the trainer.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'schedules')
            ->withPivot('date', 'start_time', 'end_time', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the reviews of the trainer.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(TrainerReview::class);
    }

    /**
     * Filter trainers by status
     */
    public function scopeWithStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        
        return $query;
    }

    /**
     * Filter trainers by specialty
     */
    public function scopeWithSpecialty($query, $specialty)
    {
        if ($specialty) {
            return $query->where('specialty', 'like', "%{$specialty}%");
        }
        
        return $query;
    }

    /**
     * Sort trainers by rating
     */
    public function scopeOrderByRating($query, $direction = 'desc')
    {
        return $query->orderBy('rating', $direction);
    }
} 