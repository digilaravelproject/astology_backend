@extends('admin.layouts.app')

@section('content')
<div x-data="{ transactionModal: false, selectedTransaction: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Financial Ledger</h1>
            <p class="text-sm text-gray font-medium">Complete transaction history and wallet management.</p>
        </div>
    </div>

    <!-- Transaction Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Transactions</div>
            <div class="text-3xl font-black text-dark">{{ $transactions->total() ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-chart-line"></i> All Time
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Completed</div>
            <div class="text-3xl font-black text-dark">{{ $completed ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-check-circle"></i> Verified
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Pending</div>
            <div class="text-3xl font-black text-dark">{{ $pending ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-hourglass-half"></i> Awaiting
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-danger/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Failed</div>
            <div class="text-3xl font-black text-dark">{{ $failed ?? 0 }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-danger font-black text-[9px] uppercase">
                <i class="fas fa-exclamation-circle"></i> Issues
            </div>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8">
        <form method="GET" action="{{ route('admin.wallet-transactions.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search</label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by user, email, or description..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
                </div>
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Type</label>
                <select name="type" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>Credit (In)</option>
                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>Debit (Out)</option>
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
                <select name="status" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
            </div>
            <div class="w-full sm:w-40">
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
            </div>
            <button type="submit" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="{{ route('admin.wallet-transactions.index') }}" class="bg-light text-dark px-6 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all h-[52px] flex items-center">
                <i class="fas fa-redo mr-2"></i> Reset
            </a>
        </form>
    </div>

    <!-- Transaction Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">User</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Type</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Description</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Date</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr class="border-b border-gray-lighter hover:bg-light/20 transition-all">
                        <td class="px-6 py-5">
                            <div class="font-bold text-dark">
                                @if($transaction->wallet->user)
                                    {{ $transaction->wallet->user->name ?? 'N/A' }}
                                @elseif($transaction->wallet->astrologer)
                                    {{ $transaction->wallet->astrologer->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="text-[10px] text-gray">{{ $transaction->wallet->user?->email ?? $transaction->wallet->astrologer?->email ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($transaction->transaction_type === 'credit')
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-arrow-down mr-1"></i> Credit
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-full border border-danger/20">
                                    <i class="fas fa-arrow-up mr-1"></i> Debit
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="font-black text-dark text-lg">₹{{ number_format($transaction->amount, 2) }}</div>
                        </td>
                        <td class="px-6 py-5">
                            @if($transaction->status === 'completed')
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-check-circle mr-1"></i> Completed
                                </span>
                            @elseif($transaction->status === 'pending')
                                <span class="inline-block px-3 py-1.5 bg-warning/10 text-warning text-[10px] font-black uppercase rounded-full border border-warning/20">
                                    <i class="fas fa-hourglass-half mr-1"></i> Pending
                                </span>
                            @elseif($transaction->status === 'failed')
                                <span class="inline-block px-3 py-1.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-full border border-danger/20">
                                    <i class="fas fa-times-circle mr-1"></i> Failed
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-gray/10 text-gray text-[10px] font-black uppercase rounded-full border border-gray/20">
                                    <i class="fas fa-ban mr-1"></i> Cancelled
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm text-gray">{{ Str::limit($transaction->description ?? 'N/A', 30) }}</div>
                        </td>
                        <td class="px-6 py-5 text-sm text-gray">
                            {{ $transaction->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.wallet-transactions.show', $transaction->id) }}" class="inline-flex items-center justify-center w-9 h-9 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-all" title="View">
                                    <i class="fas fa-eye text-sm"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-4xl text-gray-lighter mb-2"><i class="fas fa-inbox"></i></div>
                            <p class="text-gray font-bold">No transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="px-6 py-6 border-t border-gray-lighter flex items-center justify-between">
            <div class="text-[10px] font-bold text-gray uppercase tracking-widest">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}
            </div>
            <div class="flex gap-2">
                {{ $transactions->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
