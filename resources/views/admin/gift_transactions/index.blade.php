@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Gift Transactions</h1>
            <p class="text-sm text-gray font-medium">Track user gifts sent to astrologers with view and delete actions.</p>
        </div>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter p-6 mb-6">
        <form method="GET" action="{{ route('admin.gift_transactions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search</label>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Gift, sender, astrologer" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
            </div>
            <div>
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
                <select name="status" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
                    <option value="">All</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">From Date</label>
                <input name="date_from" value="{{ request('date_from') }}" type="date" class="w-full bg-light/50 border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-dark">
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all">Filter</button>
                <a href="{{ route('admin.gift_transactions.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Clear</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Transaction</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Gift</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Sender</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Astrologer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-light/30 transition-all">
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">#{{ $transaction->id }}</div>
                                <div class="text-[10px] text-gray mt-1">{{ $transaction->created_at?->format('d M, Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $transaction->gift->title ?? '-' }}</div>
                                <div class="text-[10px] text-gray">{{ $transaction->payment_provider ?? 'wallet' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $transaction->sender->name ?? '-' }}</div>
                                <div class="text-[10px] text-gray">{{ $transaction->sender->email ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ optional($transaction->astrologer->user)->name ?? '-' }}</div>
                                <div class="text-[10px] text-gray">Astrologer ID: {{ $transaction->astrologer_id }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">₹{{ number_format($transaction->amount, 2) }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 {{ $transaction->status === 'completed' ? 'bg-success/10 text-success border border-success/20' : 'bg-gray/10 text-gray border border-gray/20' }} text-[9px] font-black uppercase rounded-full">{{ ucfirst($transaction->status) }}</span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.gift_transactions.show', $transaction->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.gift_transactions.destroy', $transaction->id) }}" method="POST" onsubmit="return confirm('Delete this gift transaction?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray">No gift transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions</div>
            <div>{{ $transactions->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
