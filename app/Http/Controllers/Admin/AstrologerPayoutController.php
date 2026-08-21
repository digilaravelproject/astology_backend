<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\AstrologerPayout;
use App\Models\Wallet;
use App\Services\AstrologerPayoutService;
use App\Services\TDSCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AstrologerPayoutController extends Controller
{
    protected AstrologerPayoutService $payoutService;
    protected TDSCalculatorService $tdsCalculator;

    public function __construct(AstrologerPayoutService $payoutService, TDSCalculatorService $tdsCalculator)
    {
        $this->payoutService = $payoutService;
        $this->tdsCalculator = $tdsCalculator;
    }

    /**
     * Display the Astrologer Payout & Settlement Console.
     */
    public function index(Request $request)
    {
        // 1. Overview Metrics
        // Total active astrologer wallet balance (platform liability)
        $astrologerUserIds = Astrologer::pluck('user_id');
        $totalWalletLiabilities = (float) Wallet::whereIn('user_id', $astrologerUserIds)->sum('balance');

        $totalDisbursedAllTime = (float) AstrologerPayout::where('status', 'completed')->sum('net_paid_amount');
        $totalDisbursedThisMonth = (float) AstrologerPayout::where('status', 'completed')
            ->where('payment_date', '>=', now()->startOfMonth())
            ->sum('net_paid_amount');

        $totalTdsDeductedAllTime = (float) AstrologerPayout::where('status', 'completed')->sum('tds_amount');

        // 2. Astrologers with Balances Query
        $astrologersQuery = Astrologer::with(['user.wallet', 'bankAccounts' => function ($q) {
            $q->where('is_active', true);
        }])->where('status', 'approved');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $astrologersQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $astrologers = $astrologersQuery->paginate(15, ['*'], 'astrologers_page')->withQueryString();

        // 3. Settlement History Query
        $historyQuery = AstrologerPayout::with(['astrologer.user', 'bankAccount', 'processedByAdmin'])->latest('id');

        if ($request->filled('history_search')) {
            $hSearch = $request->input('history_search');
            $historyQuery->where(function ($q) use ($hSearch) {
                $q->where('payout_number', 'like', "%{$hSearch}%")
                  ->orWhere('utr_number', 'like', "%{$hSearch}%")
                  ->orWhereHas('user', function ($userQ) use ($hSearch) {
                      $userQ->where('name', 'like', "%{$hSearch}%")
                            ->orWhere('phone', 'like', "%{$hSearch}%");
                  });
            });
        }

        if ($request->filled('payment_mode')) {
            $historyQuery->where('payment_mode', $request->input('payment_mode'));
        }

        $payouts = $historyQuery->paginate(15, ['*'], 'history_page')->withQueryString();
        $tdsConfig = $this->tdsCalculator->getTdsSettings();

        return view('admin.astrologer_payouts.index', compact(
            'totalWalletLiabilities',
            'totalDisbursedAllTime',
            'totalDisbursedThisMonth',
            'totalTdsDeductedAllTime',
            'astrologers',
            'payouts',
            'tdsConfig'
        ));
    }

    /**
     * Fetch context and real-time TDS preview for an astrologer.
     */
    public function getContext(int $astrologerId): JsonResponse
    {
        try {
            $context = $this->payoutService->getAstrologerPayoutContext($astrologerId);
            return response()->json([
                'status' => 'success',
                'data'   => $context,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Preview TDS & Net calculation for any given gross amount.
     */
    public function previewTds(Request $request): JsonResponse
    {
        $gross = (float) $request->input('gross_amount', 0);
        $preview = $this->tdsCalculator->calculatePayoutTDS($gross);

        return response()->json([
            'status' => 'success',
            'data'   => $preview,
        ], 200);
    }

    /**
     * Store and process a manual payout transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'astrologer_id'         => 'required|integer|exists:astrologers,id',
            'gross_amount'          => 'required|numeric|min:1',
            'bank_account_id'       => 'nullable|integer',
            'payment_mode'          => 'required|string|max:50',
            'utr_number'            => 'nullable|string|max:100',
            'payment_date'          => 'required|date',
            'notes'                 => 'nullable|string|max:1000',
            'receipt_proof'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'custom_bank_name'      => 'nullable|string|max:100',
            'custom_account_number' => 'nullable|string|max:50',
            'custom_ifsc'           => 'nullable|string|max:20',
        ]);

        try {
            $adminId = Auth::id() ?? 1;

            if ($request->hasFile('receipt_proof')) {
                $path = $request->file('receipt_proof')->store('payout_receipts', 'public');
                $validated['receipt_proof'] = $path;
            }

            $payout = $this->payoutService->processManualPayout(
                $validated['astrologer_id'],
                (float) $validated['gross_amount'],
                $validated,
                $adminId
            );

            return redirect()->route('admin.astrologer-payouts.index')
                ->with('success', "Payout #{$payout->payout_number} for ₹" . number_format($payout->net_paid_amount, 2) . " processed successfully.");
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Payout failed: ' . $e->getMessage());
        }
    }

    /**
     * Download the official TDS Payout Settlement Advice PDF.
     */
    public function downloadSlip(int $id)
    {
        $payout = AstrologerPayout::findOrFail($id);
        return $this->payoutService->generateSettlementSlipPdf($payout);
    }
}
