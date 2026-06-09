<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceIncreaseRequest;
use App\Services\PriceIncreaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceIncreaseRequestController extends Controller
{
    public function __construct(
        private readonly PriceIncreaseService $priceIncreaseService
    ) {}

    public function index(Request $request)
    {
        try {
            $query = PriceIncreaseRequest::with(['astrologer.user', 'level'])->latest();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('astrologer.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('price_type')) {
                $query->where('price_type', $request->input('price_type'));
            }

            $requests = $query->paginate(20)->appends($request->all());

            return view('admin.price_increase_requests.index', compact('requests'));
        } catch (\Exception $e) {
            Log::error('PriceIncreaseRequestController::index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to load price increase requests.');
        }
    }

    public function show($id)
    {
        try {
            $request = PriceIncreaseRequest::with(['astrologer.user', 'level'])->findOrFail($id);

            return view('admin.price_increase_requests.show', compact('request'));
        } catch (\Exception $e) {
            Log::error('PriceIncreaseRequestController::show error: ' . $e->getMessage(), [
                'request_id' => $id,
            ]);
            return redirect()->route('admin.price-increase-requests.index')
                ->with('error', 'Price increase request not found.');
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'admin_remark' => 'nullable|string|max:1000',
            ]);

            $priceRequest = PriceIncreaseRequest::findOrFail($id);

            $this->priceIncreaseService->approveRequest($priceRequest, $validated['admin_remark'] ?? null);

            return redirect()->route('admin.price-increase-requests.show', $id)
                ->with('success', 'Price increase request approved successfully.');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseRequestController::approve error: ' . $e->getMessage(), [
                'request_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'admin_remark' => 'nullable|string|max:1000',
            ]);

            $priceRequest = PriceIncreaseRequest::findOrFail($id);

            $this->priceIncreaseService->rejectRequest($priceRequest, $validated['admin_remark'] ?? null);

            return redirect()->route('admin.price-increase-requests.show', $id)
                ->with('success', 'Price increase request rejected successfully.');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseRequestController::reject error: ' . $e->getMessage(), [
                'request_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
