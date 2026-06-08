<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AstrologerPricingController extends Controller
{
    public function index()
    {
        $defaults = [
            'default_chat_rate_per_minute' => Setting::get('default_chat_rate_per_minute', 15.00),
            'default_call_rate_per_minute' => Setting::get('default_call_rate_per_minute', 15.00),
            'default_video_call_rate_per_minute' => Setting::get('default_video_call_rate_per_minute', 15.00),
            'default_po_at_5_rate_per_minute' => Setting::get('default_po_at_5_rate_per_minute', 5.00),
        ];

        return view('admin.astrologers.pricing', compact('defaults'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_chat_rate_per_minute' => 'required|numeric|min:0',
            'default_call_rate_per_minute' => 'required|numeric|min:0',
            'default_video_call_rate_per_minute' => 'required|numeric|min:0',
            'default_po_at_5_rate_per_minute' => 'required|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, 'decimal', 'astrologer_pricing');
        }

        return redirect()->route('admin.astrologers.pricing')
            ->with('success', 'Default pricing updated successfully.');
    }
}