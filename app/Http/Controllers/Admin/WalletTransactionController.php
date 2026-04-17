<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletTransactionController extends Controller
{
    /**
     * Display a listing of wallet transactions.
     */
    public function index(Request $request)
    {
        $query = WalletTransaction::with(['wallet.user'])->latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('wallet.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $type = $request->input('type');
            if (in_array($type, ['credit', 'debit'])) {
                $query->where('transaction_type', $type);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, ['completed', 'pending', 'failed', 'cancelled'])) {
                $query->where('status', $status);
            }
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $transactions = $query->paginate(25)->appends($request->all());

        // Calculate summary
        $totalCredit = WalletTransaction::where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDebit = WalletTransaction::where('transaction_type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        return view('admin.wallet_transactions.index', compact('transactions', 'totalCredit', 'totalDebit'));
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        $transaction = WalletTransaction::with('wallet.user')->findOrFail($id);

        return view('admin.wallet_transactions.show', compact('transaction'));
    }

    /**
     * Update transaction status.
     */
    public function updateStatus(Request $request, $id)
    {
        $transaction = WalletTransaction::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:completed,pending,failed,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        $oldStatus = $transaction->status;
        $newStatus = $validated['status'];

        DB::transaction(function () use ($transaction, $oldStatus, $newStatus, $validated) {
            // If changing from completed to something else, adjust wallet
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $wallet = $transaction->wallet;
                
                if ($transaction->transaction_type === 'credit') {
                    $wallet->balance -= $transaction->amount;
                } else {
                    $wallet->balance += $transaction->amount;
                }
                
                $wallet->save();
            }
            // If changing from non-completed to completed, adjust wallet
            elseif ($oldStatus !== 'completed' && $newStatus === 'completed') {
                $wallet = $transaction->wallet;
                
                if ($transaction->transaction_type === 'credit') {
                    $wallet->balance += $transaction->amount;
                } else {
                    $wallet->balance -= $transaction->amount;
                }
                
                $wallet->save();
            }

            // Update transaction
            $meta = $transaction->meta ?? [];
            $meta['status_changed_at'] = now()->toDateTimeString();
            $meta['changed_by_admin'] = auth()->id();
            $meta['status_change_note'] = $validated['note'] ?? null;

            $transaction->update([
                'status' => $newStatus,
                'meta' => $meta,
            ]);
        });

        return redirect()->route('admin.wallet-transactions.show', $transaction->id)
            ->with('success', 'Transaction status updated successfully.');
    }

    /**
     * Process refund for a transaction.
     */
    public function refund(Request $request, $id)
    {
        $transaction = WalletTransaction::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($transaction->transaction_type !== 'debit') {
            return redirect()->back()->with('error', 'Only debit transactions can be refunded.');
        }

        DB::transaction(function () use ($transaction, $validated) {
            $wallet = $transaction->wallet;

            // Add refund transaction
            $wallet->transactions()->create([
                'transaction_type' => 'credit',
                'amount' => $transaction->amount,
                'status' => 'completed',
                'description' => 'Refund for transaction #' . $transaction->id,
                'meta' => [
                    'refund_reason' => $validated['reason'],
                    'refunded_transaction_id' => $transaction->id,
                    'admin_id' => auth()->id(),
                ],
            ]);

            // Update wallet
            $wallet->balance += $transaction->amount;
            $wallet->save();

            // Mark original as cancelled
            $transaction->update([
                'status' => 'cancelled',
                'meta' => array_merge($transaction->meta ?? [], [
                    'refunded' => true,
                    'refund_reason' => $validated['reason'],
                    'refund_date' => now()->toDateTimeString(),
                ]),
            ]);
        });

        return redirect()->route('admin.wallet-transactions.show', $transaction->id)
            ->with('success', 'Refund processed successfully.');
    }

    /**
     * Adjust wallet balance manually.
     */
    public function adjust(Request $request, $walletId)
    {
        $wallet = Wallet::findOrFail($walletId);

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:credit,debit',
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($wallet, $validated) {
            $amount = abs($validated['amount']);
            $type = $validated['type'];

            // Create transaction record
            $wallet->transactions()->create([
                'transaction_type' => $type,
                'amount' => $amount,
                'status' => 'completed',
                'description' => 'Admin adjustment: ' . $validated['reason'],
                'meta' => [
                    'admin_id' => auth()->id(),
                    'adjustment_reason' => $validated['reason'],
                ],
            ]);

            // Update wallet balance
            if ($type === 'credit') {
                $wallet->balance += $amount;
            } else {
                $wallet->balance -= $amount;
            }

            $wallet->save();
        });

        return redirect()->route('admin.users.wallet')
            ->with('success', 'Wallet adjusted successfully.');
    }

    /**
     * Export transactions as CSV.
     */
    public function export(Request $request)
    {
        $fileName = 'transactions_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Transaction ID',
                'User Name',
                'User Email',
                'Type',
                'Amount',
                'Status',
                'Description',
                'Created At',
            ]);

            WalletTransaction::with('wallet.user')
                ->orderByDesc('created_at')
                ->chunk(500, function ($transactions) use ($handle) {
                    foreach ($transactions as $transaction) {
                        fputcsv($handle, [
                            $transaction->id,
                            $transaction->wallet->user->name ?? '-',
                            $transaction->wallet->user->email ?? '-',
                            ucfirst($transaction->transaction_type),
                            $transaction->amount,
                            ucfirst($transaction->status),
                            $transaction->description,
                            $transaction->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
