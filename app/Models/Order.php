<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'member_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'payment_status',
        'order_status',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:0',
        'discount' => 'decimal:0',
        'tax' => 'decimal:0',
        'total' => 'decimal:0',
    ];

    /**
     * Get the member that owns the order.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the products for the order.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price', 'discount', 'total')
            ->withTimestamps();
    }

    /**
     * Get the order items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user who created the order.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Filter orders by payment status
     */
    public function scopeWithPaymentStatus($query, $status)
    {
        if ($status) {
            return $query->where('payment_status', $status);
        }
        
        return $query;
    }

    /**
     * Filter orders by order status
     */
    public function scopeWithOrderStatus($query, $status)
    {
        if ($status) {
            return $query->where('order_status', $status);
        }
        
        return $query;
    }

    /**
     * Filter orders by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereDate('created_at', '>=', $startDate)
                         ->whereDate('created_at', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Filter today's orders
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }
} 