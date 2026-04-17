@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Bank Account Management</h1>
            <p class="text-sm text-gray font-medium">Verify and manage astrologer bank accounts for payouts.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.astrologer-bank-accounts.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Account
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Accounts</div>
            <div class="text-3xl font-black text-dark">{{ \App\Models\AstrologerBankAccount::count() }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Verified</div>
            <div class="text-3xl font-black text-success">{{ \App\Models\AstrologerBankAccount::where('is_active', true)->count() }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Pending Verification</div>
            <div class="text-3xl font-black text-warning">{{ \App\Models\AstrologerBankAccount::where('is_active', false)->count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <input type="text" name="search" placeholder="Search by astrologer name or bank name..." value="{{ request('search') }}" class="flex-1 px-4 py-3 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary">
            
            <select name="status" class="px-4 py-3 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary">
                <option value="">All Status</option>
                <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>Unverified</option>
            </select>
            
            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all">Search</button>
            <a href="{{ route('admin.astrologer-bank-accounts.index') }}" class="px-6 py-3 bg-light text-dark rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all">Reset</a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Astrologer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Account Holder</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Bank</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Account Number</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($bankAccounts as $account)
                        <tr class="hover:bg-light/30 transition-all">
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $account->astrologer->user->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray mt-1">ID: #{{ $account->astrologer->id ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $account->account_holder_name }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $account->bank_name }}</div>
                                <div class="text-[10px] text-gray mt-1">IFSC: {{ $account->ifsc_code }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-mono font-bold text-dark">••••{{ substr($account->account_number, -4) }}</div>
                            </td>
                            <td class="px-6 py-5">
                                @if($account->is_active)
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Verified</span>
                                @else
                                    <span class="px-3 py-1 bg-warning/10 text-warning text-[9px] font-black uppercase rounded-full border border-warning/20">Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.astrologer-bank-accounts.show', $account->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm" title="View">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.astrologer-bank-accounts.edit', $account->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm" title="Edit">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.astrologer-bank-accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Delete this account?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm" title="Delete">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray">No bank accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $bankAccounts->firstItem() ?? 0 }} to {{ $bankAccounts->lastItem() ?? 0 }} of {{ $bankAccounts->total() }} accounts</div>
            <div>{{ $bankAccounts->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
