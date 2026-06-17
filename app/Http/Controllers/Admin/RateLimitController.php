<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RateLimitController extends Controller
{
    private const LIMITERS = [
        'otp'           => 'OTP Endpoints',
        'auth'          => 'Authentication (Signup)',
        'general'       => 'General Public API',
        'tiered'        => 'Tiered/Mutation Endpoints',
        'live_watch'    => 'Live Watch',
        'api'           => 'General API',
    ];

    public function index()
    {
        $enabled = Setting::get('rate_limit_enabled', true, 'boolean');
        $limits = [];

        foreach (self::LIMITERS as $key => $label) {
            $limits[] = [
                'key'   => $key,
                'label' => $label,
                'value' => Setting::get("rate_limit_{$key}", 60, 'integer'),
            ];
        }

        return view('admin.settings.rate_limits', compact('enabled', 'limits'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'limits'  => 'sometimes|array',
            'limits.*' => 'integer|min:0|max:10000',
        ]);

        if ($request->has('enabled')) {
            Setting::set('rate_limit_enabled', $request->boolean('enabled'), 'boolean', 'rate_limit');
        }

        if ($request->has('limits')) {
            foreach ($request->input('limits') as $key => $value) {
                if (isset(self::LIMITERS[$key])) {
                    Setting::set("rate_limit_{$key}", (int) $value, 'integer', 'rate_limit');
                }
            }
        }

        return back()->with('success', 'Rate limit settings updated successfully.');
    }
}