<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PICKUP_SCHEDULED = 'pickup_scheduled';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_CUSTOMS = 'customs';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'quote_id',
        'user_id',
        'carrier_id',
        'status',
        'contact_name',
        'contact_phone',
        'contact_email',
        'pickup_contact_name',
        'pickup_contact_phone',
        'pickup_address',
        'pickup_date',
        'pickup_time_from',
        'pickup_time_to',
        'delivery_contact_name',
        'delivery_contact_phone',
        'delivery_address',
        'tracking_number',
        'carrier_tracking_number',
        'total_amount',
        'commission_amount',
        'currency',
        'notes',
        'confirmed_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->tracking_number)) {
                $order->tracking_number = $order->order_number;
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? intval(substr($lastOrder->order_number, -6)) + 1 : 1;

        return sprintf('VE-%s-%06d', $year, $sequence);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_time', 'desc');
    }

    public function chat(): HasOne
    {
        return $this->hasOne(Chat::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PICKUP_SCHEDULED,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();
    }

    public function confirm(): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();
        $this->save();
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;

        if ($status === self::STATUS_PICKED_UP) {
            $this->picked_up_at = now();
        } elseif ($status === self::STATUS_DELIVERED) {
            $this->delivered_at = now();
        }

        $this->save();
    }
}
