<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.offers.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.offers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'call_astrologer_share' => 'required|numeric|min:0|max:100',
            'call_admin_share' => 'required|numeric|min:0|max:100',
            'chat_astrologer_share' => 'required|numeric|min:0|max:100',
            'chat_admin_share' => 'required|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Validate splits sum up to 100%
        if ($request->call_astrologer_share + $request->call_admin_share != 100) {
            return back()->withErrors(['call_split' => 'Call astrologer and admin shares must sum up to exactly 100%.'])->withInput();
        }
        if ($request->chat_astrologer_share + $request->chat_admin_share != 100) {
            return back()->withErrors(['chat_split' => 'Chat astrologer and admin shares must sum up to exactly 100%.'])->withInput();
        }

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        if ($request->filled('expires_at')) {
            $data['expires_at'] = Carbon::parse($request->expires_at);
        }

        Offer::create($data);

        return redirect()->route('admin.offers.index')->with('success', 'Offer created successfully.');
    }

    public function edit(Offer $offer)
    {
        return view('admin.offers.edit', compact('offer'));
    }

    public function update(Request $request, Offer $offer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'call_astrologer_share' => 'required|numeric|min:0|max:100',
            'call_admin_share' => 'required|numeric|min:0|max:100',
            'chat_astrologer_share' => 'required|numeric|min:0|max:100',
            'chat_admin_share' => 'required|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Validate splits sum up to 100%
        if ($request->call_astrologer_share + $request->call_admin_share != 100) {
            return back()->withErrors(['call_split' => 'Call astrologer and admin shares must sum up to exactly 100%.'])->withInput();
        }
        if ($request->chat_astrologer_share + $request->chat_admin_share != 100) {
            return back()->withErrors(['chat_split' => 'Chat astrologer and admin shares must sum up to exactly 100%.'])->withInput();
        }

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['expires_at'] = $request->filled('expires_at') ? Carbon::parse($request->expires_at) : null;

        $offer->update($data);

        return redirect()->route('admin.offers.index')->with('success', 'Offer updated successfully.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();
        return redirect()->route('admin.offers.index')->with('success', 'Offer deleted successfully.');
    }

    public function toggleStatus(Offer $offer)
    {
        $offer->is_active = !$offer->is_active;
        $offer->save();

        return response()->json([
            'success' => true,
            'is_active' => $offer->is_active,
            'message' => 'Offer status updated successfully.'
        ]);
    }
}
