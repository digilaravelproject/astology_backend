<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AstrologerPhoneNumber;
use Illuminate\Http\Request;

class AstrologerPhoneNumberController extends Controller
{
    /**
     * Display a listing of astrologer phone numbers.
     */
    public function index(Request $request)
    {
        $query = AstrologerPhoneNumber::with('astrologer.user')->latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('astrologer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }

        // Filter by verification status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'verified') {
                $query->where('otp_verified_at', '!=', null);
            } elseif ($status === 'unverified') {
                $query->where('otp_verified_at', null);
            }
        }

        // Get statistics
        $allPhones = AstrologerPhoneNumber::all();
        $total = $allPhones->count();
        $verified = $allPhones->filter(fn($phone) => $phone->otp_verified_at !== null)->count();
        $unverified = $allPhones->filter(fn($phone) => $phone->otp_verified_at === null)->count();
        $default = $allPhones->filter(fn($phone) => $phone->is_default)->count();

        $phones = $query->paginate(20)->appends($request->all());

        return view('admin.astrologer_phone_numbers.index', compact('phones', 'total', 'verified', 'unverified', 'default'));
    }

    /**
     * Display the specified phone number.
     */
    public function show($id)
    {
        $phone = AstrologerPhoneNumber::with('astrologer')->findOrFail($id);

        return view('admin.astrologer_phone_numbers.show', compact('phone'));
    }

    /**
     * Toggle verification status of a phone number.
     */
    public function toggleVerification($id)
    {
        $phone = AstrologerPhoneNumber::findOrFail($id);
        $isVerified = $phone->otp_verified_at !== null;
        $phone->update([
            'otp_verified_at' => $isVerified ? null : now(),
        ]);

        $status = !$isVerified ? 'verified' : 'unverified';

        return redirect()->route('admin.astrologer-phone-numbers.show', $phone->id)
            ->with('success', "Phone number marked as {$status}.");
    }

    /**
     * Set a phone number as default for an astrologer.
     */
    public function setDefault($id)
    {
        $phone = AstrologerPhoneNumber::findOrFail($id);

        // Unset all other defaults for this astrologer
        AstrologerPhoneNumber::where('astrologer_id', $phone->astrologer_id)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        $phone->update(['is_default' => true]);

        return redirect()->route('admin.astrologer-phone-numbers.show', $phone->id)
            ->with('success', 'Phone number set as default.');
    }

    /**
     * Delete the specified phone number.
     */
    public function destroy($id)
    {
        $phone = AstrologerPhoneNumber::findOrFail($id);
        $phone->delete();

        return redirect()->route('admin.astrologer-phone-numbers.index')
            ->with('success', 'Phone number deleted successfully.');
    }
}
