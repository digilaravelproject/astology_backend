<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        // Load all settings grouped by group or individually
        $settings = [
            'app_name' => Setting::get('app_name', 'Astology Premium'),
            'support_email' => Setting::get('support_email', 'ops@astologyapp.com'),
            'seo_meta_description' => Setting::get('seo_meta_description', 'Connect with India\'s top astrologers for personalized readings, daily horoscopes, and spiritual guidance.'),
            'favicon_path' => Setting::get('favicon_path'),
            'logo_path' => Setting::get('logo_path'),
            'social_preview_path' => Setting::get('social_preview_path'),
            
            // Commission
            'global_commission_percentage' => Setting::get('global_commission_percentage', 20.00),
            'global_package_commission_rate' => Setting::get('global_package_commission_rate', 50.00),
            'ecommerce_commission_percentage' => Setting::get('ecommerce_commission_percentage', 15.00),
            'premium_yearly_commission_percentage' => Setting::get('premium_yearly_commission_percentage', 10.00),
            
            // Financial / Wallet Rules
            'min_wallet_recharge' => Setting::get('min_wallet_recharge', 100.00),
            'max_wallet_balance' => Setting::get('max_wallet_balance', 10000.00),
            'min_withdrawal_amount' => Setting::get('min_withdrawal_amount', 500.00),
            
            'razorpay_key' => Setting::get('razorpay_key', ''),
            'razorpay_secret' => Setting::get('razorpay_secret', ''),
            'stripe_key' => Setting::get('stripe_key', ''),
            'stripe_secret' => Setting::get('stripe_secret', ''),
            'payment_gateway_mode' => Setting::get('payment_gateway_mode', 'sandbox'),
            'active_gateways' => Setting::get('active_gateways', ['razorpay']),

            // Astro Governance
            'default_chat_rate_per_minute' => Setting::get('default_chat_rate_per_minute', 15.00),
            'default_call_rate_per_minute' => Setting::get('default_call_rate_per_minute', 15.00),
            'default_video_call_rate_per_minute' => Setting::get('default_video_call_rate_per_minute', 15.00),
            'default_po_at_5_rate_per_minute' => Setting::get('default_po_at_5_rate_per_minute', 5.00),

            // Platform Guard / Security (Rate Limit)
            'rate_limit_enabled' => Setting::get('rate_limit_enabled', true, 'boolean'),
            'rate_limit_otp' => Setting::get('rate_limit_otp', 5),
            'rate_limit_auth' => Setting::get('rate_limit_auth', 60),
            'rate_limit_general' => Setting::get('rate_limit_general', 60),
            'rate_limit_tiered' => Setting::get('rate_limit_tiered', 30),
            'rate_limit_live_watch' => Setting::get('rate_limit_live_watch', 100),
            'rate_limit_api' => Setting::get('rate_limit_api', 120),

            // Chat Assistance
            'chat_assistance_enabled' => Setting::get('chat_assistance_enabled', true, 'boolean'),
            'chat_assistance_daily_limit' => Setting::get('chat_assistance_daily_limit', 5),
        ];

        // Fetch real admins for Team / RBAC management
        $operators = Admin::all();

        return view('admin.settings.index', compact('settings', 'operators'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // General
            'app_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'seo_meta_description' => 'nullable|string',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg|max:1024',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'social_preview' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            
            // Commission
            'global_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'global_package_commission_rate' => 'nullable|numeric|min:0|max:100',
            'ecommerce_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'premium_yearly_commission_percentage' => 'nullable|numeric|min:0|max:100',
            
            // Financial
            'min_wallet_recharge' => 'nullable|numeric|min:0',
            'max_wallet_balance' => 'nullable|numeric|min:0',
            'min_withdrawal_amount' => 'nullable|numeric|min:0',
            
            // Payment Gateway
            'razorpay_key' => 'nullable|string',
            'razorpay_secret' => 'nullable|string',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'payment_gateway_mode' => 'nullable|in:sandbox,live',
            'active_gateways' => 'nullable|array',
            
            // Astro Governance
            'default_chat_rate_per_minute' => 'nullable|numeric|min:0',
            'default_call_rate_per_minute' => 'nullable|numeric|min:0',
            'default_video_call_rate_per_minute' => 'nullable|numeric|min:0',
            'default_po_at_5_rate_per_minute' => 'nullable|numeric|min:0',

            // Security
            'rate_limit_enabled' => 'nullable|boolean',
            'rate_limit_otp' => 'nullable|integer|min:0',
            'rate_limit_auth' => 'nullable|integer|min:0',
            'rate_limit_general' => 'nullable|integer|min:0',
            'rate_limit_tiered' => 'nullable|integer|min:0',
            'rate_limit_live_watch' => 'nullable|integer|min:0',
            'rate_limit_api' => 'nullable|integer|min:0',

            // Chat Assistance
            'chat_assistance_enabled' => 'nullable|boolean',
            'chat_assistance_daily_limit' => 'nullable|integer|min:0',
        ]);

        // Save text settings
        $keys = [
            'app_name' => 'general',
            'support_email' => 'general',
            'seo_meta_description' => 'general',
            'global_commission_percentage' => 'commission',
            'global_package_commission_rate' => 'commission',
            'ecommerce_commission_percentage' => 'commission',
            'premium_yearly_commission_percentage' => 'commission',
            'min_wallet_recharge' => 'wallet',
            'max_wallet_balance' => 'wallet',
            'min_withdrawal_amount' => 'wallet',
            'razorpay_key' => 'payment',
            'razorpay_secret' => 'payment',
            'stripe_key' => 'payment',
            'stripe_secret' => 'payment',
            'payment_gateway_mode' => 'payment',
            'default_chat_rate_per_minute' => 'astrologer_pricing',
            'default_call_rate_per_minute' => 'astrologer_pricing',
            'default_video_call_rate_per_minute' => 'astrologer_pricing',
            'default_po_at_5_rate_per_minute' => 'astrologer_pricing',
            'rate_limit_enabled' => 'rate_limit',
            'rate_limit_otp' => 'rate_limit',
            'rate_limit_auth' => 'rate_limit',
            'rate_limit_general' => 'rate_limit',
            'rate_limit_tiered' => 'rate_limit',
            'rate_limit_live_watch' => 'rate_limit',
            'rate_limit_api' => 'rate_limit',
            'chat_assistance_enabled' => 'chat_assistance',
            'chat_assistance_daily_limit' => 'chat_assistance',
        ];

        foreach ($keys as $key => $group) {
            if ($request->has($key)) {
                $value = $request->input($key);
                $type = 'string';
                if (str_contains($key, 'percentage') || str_contains($key, 'rate') || str_contains($key, 'amount') || str_contains($key, 'recharge') || str_contains($key, 'balance')) {
                    $type = 'decimal';
                } elseif (str_contains($key, 'limit_enabled') || $key === 'chat_assistance_enabled') {
                    $type = 'boolean';
                    $value = $request->boolean($key);
                } elseif (str_contains($key, 'limit_') || $key === 'chat_assistance_daily_limit') {
                    $type = 'integer';
                }
                Setting::set($key, $value, $type, $group);
            }
        }

        // Save array settings
        if ($request->has('active_gateways')) {
            Setting::set('active_gateways', json_encode($request->input('active_gateways')), 'json', 'payment');
        } else {
            Setting::set('active_gateways', json_encode([]), 'json', 'payment');
        }

        // Handle File Uploads
        $fileFields = ['favicon' => 'favicon_path', 'logo' => 'logo_path', 'social_preview' => 'social_preview_path'];
        foreach ($fileFields as $field => $settingKey) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                
                // For favicon, save it directly as favicon.ico inside public folder if extension is ico
                if ($field === 'favicon' && $file->getClientOriginalExtension() === 'ico') {
                    $file->move(public_path(), 'favicon.ico');
                    Setting::set($settingKey, '/favicon.ico', 'string', 'general');
                } else {
                    $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/settings'), $filename);
                    $path = '/uploads/settings/' . $filename;
                    Setting::set($settingKey, $path, 'string', 'general');
                }
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    // Operator (RBAC) Management
    public function storeOperator(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,admin',
            'is_active' => 'required|boolean',
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Operator created successfully.');
    }

    public function updateOperator(Request $request, $id)
    {
        $operator = Admin::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $id,
            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,admin',
            'is_active' => 'required|boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->is_active,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $operator->update($data);

        return redirect()->back()->with('success', 'Operator updated successfully.');
    }

    public function destroyOperator($id)
    {
        $operator = Admin::findOrFail($id);

        // Prevent self deletion
        if (Auth::guard('admin')->id() === $operator->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last super admin
        if ($operator->role === 'super_admin' && Admin::where('role', 'super_admin')->count() <= 1) {
            return redirect()->back()->with('error', 'You cannot delete the last Super Admin.');
        }

        $operator->delete();

        return redirect()->back()->with('success', 'Operator deleted successfully.');
    }
}
