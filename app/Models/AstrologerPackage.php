<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AstrologerPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'astrologer_id',
        'amount',
        'duration',
        'commission_percentage',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'duration' => 'integer',
        'commission_percentage' => 'decimal:2',
    ];

    public function astrologer()
    {
        return $this->belongsTo(User::class, 'astrologer_id');
    }
}
