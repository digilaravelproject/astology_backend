<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_number',
        'astrologer_id',
        'user_id',
        'wallet_transaction_id',
        'gross_amount',
        'tds_percent',
        'tds_amount',
        'net_paid_amount',
        'payment_mode',
        'utr_number',
        'bank_account_id',
        'bank_details_snapshot',
        'payment_date',
        'notes',
        'receipt_proof',
        'status',
        'processed_by',
    ];

    protected $casts = [
        'gross_amount'          => 'decimal:2',
        'tds_percent'           => 'decimal:2',
        'tds_amount'            => 'decimal:2',
        'net_paid_amount'       => 'decimal:2',
        'bank_details_snapshot' => 'array',
        'payment_date'          => 'date',
    ];

    protected $appends = ['receipt_proof_url', 'invoice_url'];

    public function getReceiptProofUrlAttribute(): ?string
    {
        return $this->receipt_proof ? \App\Helpers\MediaHelper::getUrl($this->receipt_proof) : null;
    }

    public function getInvoiceUrlAttribute(): string
    {
        return url("/api/v1/astrologer/wallet/payouts/{$this->id}/receipt");
    }

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class, 'astrologer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(AstrologerBankAccount::class, 'bank_account_id');
    }

    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }

    public function processedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
}
