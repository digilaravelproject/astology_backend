<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AstrologerBankAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AstrologerBankAccountController extends Controller
{
    /**
     * Display a listing of astrologer bank accounts.
     */
    public function index(Request $request)
    {
        $query = AstrologerBankAccount::with('astrologer.user')
            ->latest();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('astrologer.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('account_holder_name', 'like', "%{$search}%")
              ->orWhere('bank_name', 'like', "%{$search}%");
        }

        // Filter by verification status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'verified') {
                $query->where('is_active', true);
            } elseif ($status === 'unverified') {
                $query->where('is_active', false);
            }
        }

        // Get statistics
        $allAccounts = AstrologerBankAccount::all();
        $total = $allAccounts->count();
        $verified = $allAccounts->filter(fn($account) => $account->is_active)->count();
        $unverified = $allAccounts->filter(fn($account) => !$account->is_active)->count();

        $bankAccounts = $query->paginate(20)->appends($request->all());

        return view('admin.astrologer_bank_accounts.index', compact('bankAccounts', 'total', 'verified', 'unverified'));
    }

    /**
     * Show the form for creating a new bank account.
     */
    public function create()
    {
        $astrologers = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.astrologer_bank_accounts.form', compact('astrologers'));
    }

    /**
     * Store a newly created bank account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'astrologer_id' => 'required|exists:astrologers,id',
            'account_holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|size:11',
            'passbook_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $passbookPath = null;
        if ($request->hasFile('passbook_document')) {
            $file = $request->file('passbook_document');
            $filename = time() . '_passbook_.' . $file->getClientOriginalExtension();
            $passbookPath = Storage::disk('public')->putFileAs('bank_accounts', $file, $filename);
        }

        AstrologerBankAccount::create([
            'astrologer_id' => $validated['astrologer_id'],
            'account_holder_name' => $validated['account_holder_name'],
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'ifsc_code' => strtoupper($validated['ifsc_code']),
            'passbook_document' => $passbookPath,
            'is_default' => false,
            'is_active' => true,
        ]);

        return redirect()->route('admin.astrologer-bank-accounts.index')
            ->with('success', 'Bank account added successfully.');
    }

    /**
     * Display the specified bank account.
     */
    public function show($id)
    {
        $account = AstrologerBankAccount::with('astrologer.user')->findOrFail($id);

        return view('admin.astrologer_bank_accounts.show', compact('account'));
    }

    /**
     * Show the form for editing the specified bank account.
     */
    public function edit($id)
    {
        $account = AstrologerBankAccount::findOrFail($id);
        $astrologers = User::where('user_type', 'astrologer')
            ->with('astrologer')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.astrologer_bank_accounts.form', compact('account', 'astrologers'));
    }

    /**
     * Update the specified bank account.
     */
    public function update(Request $request, $id)
    {
        $account = AstrologerBankAccount::findOrFail($id);

        $validated = $request->validate([
            'astrologer_id' => 'required|exists:astrologers,id',
            'account_holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|size:11',
            'passbook_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($request->hasFile('passbook_document')) {
            if ($account->passbook_document) {
                Storage::disk('public')->delete($account->passbook_document);
            }
            $file = $request->file('passbook_document');
            $filename = time() . '_passbook_.' . $file->getClientOriginalExtension();
            $validated['passbook_document'] = Storage::disk('public')->putFileAs('bank_accounts', $file, $filename);
        }

        $validated['ifsc_code'] = strtoupper($validated['ifsc_code']);
        $account->update($validated);

        return redirect()->route('admin.astrologer-bank-accounts.show', $account->id)
            ->with('success', 'Bank account updated successfully.');
    }

    /**
     * Toggle bank account verification status.
     */
    public function toggleVerification($id)
    {
        $account = AstrologerBankAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'verified' : 'unverified';

        return redirect()->route('admin.astrologer-bank-accounts.show', $account->id)
            ->with('success', "Bank account marked as {$status}.");
    }

    /**
     * Delete the specified bank account.
     */
    public function destroy($id)
    {
        $account = AstrologerBankAccount::findOrFail($id);

        if ($account->passbook_document) {
            Storage::disk('public')->delete($account->passbook_document);
        }

        $account->delete();

        return redirect()->route('admin.astrologer-bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}
