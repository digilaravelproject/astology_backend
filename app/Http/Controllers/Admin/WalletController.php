<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    /**
     * Display the wallet overview page.
     */
    public function index(Request $request)
    {
        $query = Wallet::with('user')
            ->withSum(['transactions as total_added' => function ($q) {
                $q->where('transaction_type', 'credit')->where('status', 'completed');
            }], 'amount')
            ->withSum(['transactions as total_spent' => function ($q) {
                $q->where('transaction_type', 'debit')->where('status', 'completed');
            }], 'amount');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        $wallets = $query->orderByDesc('balance')->paginate(15)->withQueryString();

        $today = now()->startOfDay();
        $platformBalance = Wallet::sum('balance');
        $totalAddedToday = WalletTransaction::where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount');
        $totalSpentToday = WalletTransaction::where('transaction_type', 'debit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $usersForTopup = User::orderBy('name')->limit(200)->get();

        return view('admin.users.wallet', compact(
            'wallets',
            'platformBalance',
            'totalAddedToday',
            'totalSpentToday',
            'usersForTopup'
        ));
    }

    /**
     * Add credit to a user's wallet (admin action).
     */
    public function topup(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::findOrFail($request->input('user_id'));

        $wallet = Wallet::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'balance' => 0,
        ]);

        DB::transaction(function () use ($wallet, $request) {
            $wallet->transactions()->create([
                'transaction_type' => 'credit',
                'amount' => $request->input('amount'),
                'status' => 'completed',
                'description' => trim($request->input('note')) ?: 'Admin wallet credit',
                'meta' => [
                    'admin_id' => auth()->id(),
                ],
            ]);

            $wallet->balance = $wallet->balance + $request->input('amount');
            $wallet->save();
        });

        return redirect()->route('admin.users.wallet')->with('success', 'Wallet credited successfully.');
    }

    /**
     * Return wallet transaction history for a user (JSON).
     */
    public function transactions(User $user): Response
    {
        $wallet = Wallet::firstOrCreate([
            'user_id' => $user->id,
        ], ['balance' => 0]);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Export wallet overview as CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'wallet_report_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['User ID', 'Name', 'Email', 'Balance', 'Total Added', 'Total Spent']);

            Wallet::with('user')
                ->withSum(['transactions as total_added' => function ($q) {
                    $q->where('transaction_type', 'credit')->where('status', 'completed');
                }], 'amount')
                ->withSum(['transactions as total_spent' => function ($q) {
                    $q->where('transaction_type', 'debit')->where('status', 'completed');
                }], 'amount')
                ->chunk(200, function ($wallets) use ($handle) {
                    foreach ($wallets as $wallet) {
                        fputcsv($handle, [
                            $wallet->user_id,
                            $wallet->user->name ?? '-',
                            $wallet->user->email ?? '-',
                            $wallet->balance,
                            $wallet->total_added ?? 0,
                            $wallet->total_spent ?? 0,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
