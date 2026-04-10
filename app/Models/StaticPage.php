<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    protected $fillable = [
        'type',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPE_FAQ = 'faq';
    public const TYPE_PRIVACY_POLICY = 'privacy_policy';
    public const TYPE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    public const TYPE_PAYMENT_POLICY = 'payment_policy';
    public const TYPE_ABOUT_US = 'about_us';
    public const TYPE_CUSTOMER_SUPPORT = 'customer_support';

    public static function getTypes()
    {
        return [
            self::TYPE_FAQ => 'FAQs',
            self::TYPE_PRIVACY_POLICY => 'Privacy Policy',
            self::TYPE_TERMS_AND_CONDITIONS => 'Terms & Conditions',
            self::TYPE_PAYMENT_POLICY => 'Payment Policy',
            self::TYPE_ABOUT_US => 'About Us',
            self::TYPE_CUSTOMER_SUPPORT => 'Customer Support',
        ];
    }
}
