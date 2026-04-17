@extends('admin.layouts.app')

@section('content')
<div x-data="{ showRefund: false, showAdjust: false, refundReason: '', adjustAmount: '', adjustType: 'credit' }">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.wallet-transactions.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Transactions
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Transaction Details</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Transaction #{{ $transaction->id }}</p>
        </div>
        <div class="flex gap-3">
            @if($transaction->transaction_type === 'debit' && $transaction->status !== 'cancelled')
            <button @click="showRefund = !showRefund" class="px-8 py-4 bg-danger/10 border-2 border-danger text-danger text-[11px] font-black uppercase rounded-2xl hover:bg-danger/20 transition-all">
                <i class="fas fa-undo mr-2"></i> Process Refund
            </button>
            @endif
            <button @click="showAdjust = !showAdjust" class="px-8 py-4 bg-warning/10 border-2 border-warning text-warning text-[11px] font-black uppercase rounded-2xl hover:bg-warning/20 transition-all">
                <i class="fas fa-adjust mr-2"></i> Adjust Balance
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Left: Transaction Details -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Transaction Header -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Transaction Summary
                </h2>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Transaction Type</div>
                            @if($transaction->transaction_type === 'credit')
                                <div class="inline-block px-4 py-2.5 bg-success/10 text-success text-sm font-black uppercase rounded-2xl border border-success/20">
                                    <i class="fas fa-arrow-down mr-2"></i> Credit (Incoming)
                                </div>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-danger/10 text-danger text-sm font-black uppercase rounded-2xl border border-danger/20">
                                    <i class="fas fa-arrow-up mr-2"></i> Debit (Outgoing)
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Status</div>
                            @if($transaction->status === 'completed')
                                <div class="inline-block px-4 py-2.5 bg-success/10 text-success text-sm font-black uppercase rounded-2xl border border-success/20">
                                    <i class="fas fa-check-circle mr-2"></i> Completed
                                </div>
                            @elseif($transaction->status === 'pending')
                                <div class="inline-block px-4 py-2.5 bg-warning/10 text-warning text-sm font-black uppercase rounded-2xl border border-warning/20">
                                    <i class="fas fa-hourglass-half mr-2"></i> Pending
                                </div>
                            @elseif($transaction->status === 'failed')
                                <div class="inline-block px-4 py-2.5 bg-danger/10 text-danger text-sm font-black uppercase rounded-2xl border border-danger/20">
                                    <i class="fas fa-times-circle mr-2"></i> Failed
                                </div>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-gray/10 text-gray text-sm font-black uppercase rounded-2xl border border-gray/20">
                                    <i class="fas fa-ban mr-2"></i> Cancelled
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Amount</div>
                        <div class="text-5xl font-black text-dark">₹{{ number_format($transaction->amount, 2) }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Description</div>
                            <p class="text-dark font-medium">{{ $transaction->description ?? 'No description provided' }}</p>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Transaction Date</div>
                            <p class="text-dark font-bold">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> User Information
                </h2>
                
                <div class="space-y-6">
                    @if($transaction->wallet->user)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">User Name</div>
                                <p class="text-lg font-black text-dark">{{ $transaction->wallet->user->name }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Email</div>
                                <p class="text-sm font-medium text-dark">{{ $transaction->wallet->user->email }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Phone</div>
                                <p class="text-sm font-medium text-dark">{{ $transaction->wallet->user->phone ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Wallet Balance</div>
                                <p class="text-lg font-black text-success">₹{{ number_format($transaction->wallet->balance, 2) }}</p>
                            </div>
                        </div>
                    @elseif($transaction->wallet->astrologer)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Astrologer Name</div>
                                <p class="text-lg font-black text-dark">{{ $transaction->wallet->astrologer->name }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Email</div>
                                <p class="text-sm font-medium text-dark">{{ $transaction->wallet->astrologer->email }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Phone</div>
                                <p class="text-sm font-medium text-dark">{{ $transaction->wallet->astrologer->phone ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Wallet Balance</div>
                                <p class="text-lg font-black text-success">₹{{ number_format($transaction->wallet->balance, 2) }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Meta Information -->
            @if($transaction->meta)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Additional Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($transaction->meta as $key => $value)
                        <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                            <p class="text-dark font-medium break-words">{{ is_array($value) ? json_encode($value) : $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Status Update -->
            @if($transaction->status !== 'completed')
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Update Status</h3>
                <form action="{{ route('admin.wallet-transactions.update-status', $transaction->id) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <select name="status" class="w-full bg-light/30 border-2 border-transparent px-4 py-3.5 rounded-2xl text-xs font-black text-dark focus:bg-white focus:border-primary/20 focus:ring-0 transition-all">
                            <option value="pending" {{ $transaction->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ $transaction->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ $transaction->status === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ $transaction->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <button type="submit" class="w-full bg-dark text-white py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10">
                            <i class="fas fa-save mr-2"></i> Update
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.wallet-transactions.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <a href="{{ route('admin.wallet-transactions.index') }}?search={{ $transaction->wallet->user->email ?? $transaction->wallet->astrologer->email }}" class="block w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all text-center">
                    <i class="fas fa-user mr-2"></i> View User
                </a>
            </div>

            <!-- Refund Modal -->
            <div x-show="showRefund" class="bg-white p-8 rounded-[32px] border border-danger/20 shadow-sm">
                <h3 class="text-[10px] font-black text-danger uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-undo"></i> Process Refund
                </h3>
                <form action="{{ route('admin.wallet-transactions.refund', $transaction->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-2">Refund Reason</label>
                        <textarea name="reason" rows="3" placeholder="Explain why this refund is being processed..." class="w-full bg-light/30 border-2 border-transparent px-4 py-3.5 rounded-2xl text-xs font-medium text-dark focus:bg-white focus:border-danger/20 focus:ring-0 transition-all resize-none" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-danger text-white py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all shadow-xl shadow-danger/10">
                        <i class="fas fa-check mr-2"></i> Confirm Refund
                    </button>
                    <button type="button" @click="showRefund = false" class="w-full bg-light text-dark py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all">
                        Cancel
                    </button>
                </form>
            </div>

            <!-- Adjust Balance Modal -->
            <div x-show="showAdjust" class="bg-white p-8 rounded-[32px] border border-warning/20 shadow-sm">
                <h3 class="text-[10px] font-black text-warning uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-adjust"></i> Adjust Wallet
                </h3>
                <form action="{{ route('admin.wallet-transactions.adjust', ['walletId' => $transaction->wallet->id]) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-2">Adjustment Type</label>
                        <select name="type" class="w-full bg-light/30 border-2 border-transparent px-4 py-3.5 rounded-2xl text-xs font-black text-dark focus:bg-white focus:border-warning/20 focus:ring-0 transition-all">
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-2">Amount</label>
                        <input type="number" name="amount" step="0.01" placeholder="0.00" class="w-full bg-light/30 border-2 border-transparent px-4 py-3.5 rounded-2xl text-xs font-black text-dark focus:bg-white focus:border-warning/20 focus:ring-0 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-2">Reason</label>
                        <textarea name="reason" rows="3" placeholder="Reason for adjustment..." class="w-full bg-light/30 border-2 border-transparent px-4 py-3.5 rounded-2xl text-xs font-medium text-dark focus:bg-white focus:border-warning/20 focus:ring-0 transition-all resize-none" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-warning text-white py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-600 transition-all shadow-xl shadow-warning/10">
                        <i class="fas fa-check mr-2"></i> Apply Adjustment
                    </button>
                    <button type="button" @click="showAdjust = false" class="w-full bg-light text-dark py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-lighter transition-all">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
