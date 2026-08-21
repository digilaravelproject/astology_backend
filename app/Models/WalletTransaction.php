<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\WalletTransaction
 *
 * @property int $id
 * @property int $wallet_id
 * @property int|null $plan_id
 * @property string $transaction_type 'credit' | 'debit'
 * @property float $amount
 * @property float|null $base_amount
 * @property float|null $gst_percent
 * @property float|null $gst_amount
 * @property float|null $total_amount
 * @property string|null $invoice_number
 * @property string $status 'pending' | 'completed' | 'failed' | 'cancelled'
 * @property string|null $payment_provider
 * @property string|null $provider_order_id
 * @property string|null $provider_payment_id
 * @property string|null $description
 * @property array|null $meta
 * @property float|null $balance_before
 * @property float|null $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Wallet $wallet
 * @property-read \App\Models\Plan|null $plan
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $reference
 */
class WalletTransaction extends Model
{
    use HasFactory;

    // =========================================================================
    // ENUMS & CONSTANTS
    // =========================================================================

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT  = 'debit';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // =========================================================================
    // MASS ASSIGNMENT & CASTING
    // =========================================================================

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Foreign Relations
        'wallet_id',
        'plan_id',
        'reference_type',
        'reference_id',

        // Transaction Core
        'transaction_type',
        'amount',
        'status',
        'description',

        // Tax & Invoicing Details
        'base_amount',
        'gst_percent',
        'gst_amount',
        'total_amount',
        'invoice_number',

        // Payment Gateway Integration
        'payment_provider',
        'provider_order_id',
        'provider_payment_id',

        // Audit Trail & Metadata
        'balance_before',
        'balance_after',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount'         => 'decimal:2',
        'base_amount'    => 'decimal:2',
        'gst_percent'    => 'decimal:2',
        'gst_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'meta'           => 'array',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // =========================================================================
    // ELOQUENT RELATIONSHIPS
    // =========================================================================

    /**
     * Get the associated wallet instance.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the associated plan subscription if applicable.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the polymorphic reference entity (e.g. ChatSession, CallSession, Order).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    /**
     * Scope query to only credit transactions.
     */
    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('transaction_type', self::TYPE_CREDIT);
    }

    /**
     * Scope query to only debit transactions.
     */
    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('transaction_type', self::TYPE_DEBIT);
    }

    /**
     * Scope query to only completed transactions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope query to only pending transactions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // =========================================================================
    // STATUS & TYPE HELPERS
    // =========================================================================

    /**
     * Determine if transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Determine if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Determine if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->transaction_type === self::TYPE_CREDIT;
    }

    /**
     * Determine if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->transaction_type === self::TYPE_DEBIT;
    }
}
