<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'astrologer_id',
        'total_duration',
        'remaining_duration',
        'purchase_price',
        'commission_percentage',
        'admin_earnings',
        'astrologer_earnings',
        'status',
    ];

    protected $casts = [
        'total_duration' => 'integer',
        'remaining_duration' => 'integer',
        'purchase_price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'admin_earnings' => 'decimal:2',
        'astrologer_earnings' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function astrologer()
    {
        return $this->belongsTo(User::class, 'astrologer_id');
    }

    public function subSessions()
    {
        return $this->hasMany(PackageSubSession::class);
    }
}
