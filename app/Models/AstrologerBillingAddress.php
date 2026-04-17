<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AstrologerBillingAddress extends Model
{
    protected $fillable = [
        'astrologer_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'invoice_name',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }
}
