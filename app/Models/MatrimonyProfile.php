<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrimonyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_for',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'height',
        'marital_status',
        'location',
        'education',
        'job_title',
        'annual_income',
        'about',
        'profile_photo',
        'pan_card_number',
        'driving_licence_number',
        'aadhar_card_number',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
