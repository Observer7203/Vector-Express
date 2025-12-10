<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    const COMMISSION_RATE = 0.05; // 5%
    const PAYMENT_DAYS = 15;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'user_id',
        'subtotal',
        'commission',
        'total',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'commission' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            if (empty($invoice->due_date)) {
                $invoice->due_date = now()->addDays(self::PAYMENT_DAYS);
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -6)) + 1 : 1;

        return sprintf('INV-%s-%06d', $year, $sequence);
    }

    public static function calculateFromOrder(Order $order): array
    {
        $subtotal = $order->total_amount;
        $commission = $subtotal * self::COMMISSION_RATE;
        $total = $subtotal + $commission;

        return [
            'subtotal' => $subtotal,
            'commission' => $commission,
            'total' => $total,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE ||
            ($this->isPending() && $this->due_date->isPast());
    }

    public function markAsPaid(string $paymentMethod = null): void
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        $this->payment_method = $paymentMethod;
        $this->save();
    }

    public function markAsOverdue(): void
    {
        $this->status = self::STATUS_OVERDUE;
        $this->save();
    }
}
