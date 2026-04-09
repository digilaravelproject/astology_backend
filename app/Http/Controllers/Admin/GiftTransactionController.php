<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftTransaction;
use Illuminate\Http\Request;

class GiftTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = GiftTransaction::with(['gift', 'sender', 'astrologer.user']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('gift', function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%");
                })
                ->orWhereHas('sender', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('astrologer.user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.gift_transactions.index', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = GiftTransaction::with(['gift', 'sender', 'astrologer.user'])->findOrFail($id);
        return view('admin.gift_transactions.show', compact('transaction'));
    }

    public function destroy($id)
    {
        $transaction = GiftTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->route('admin.gift_transactions.index')->with('success', 'Gift transaction deleted successfully.');
    }
}
