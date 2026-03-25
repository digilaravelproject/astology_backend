<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AstrologerBankAccount extends Model
{
    protected $fillable = [
        'astrologer_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'passbook_document',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }
}
