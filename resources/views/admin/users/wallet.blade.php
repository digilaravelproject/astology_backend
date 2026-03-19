@extends('admin.layouts.app')

@section('content')

<div x-data="{
        historyModal: false,
        addCreditModal: false,
        selectedUser: {},
        transactions: [],
        loadingTransactions: false,
        topup: {
            user_id: '{{ optional($usersForTopup->first())->id ?? '' }}',
            amount: '',
            note: '',
        },
        openHistory(user) {
            this.selectedUser = user;
            this.historyModal = true;
            this.transactions = [];
            this.loadingTransactions = true;

            fetch(`{{ url('admin/users-wallet') }}/${user.id}/transactions`)
                .then(res => res.json())
                .then(data => {
                    this.transactions = data.transactions || [];
                })
                .catch(() => {
                    this.transactions = [];
                })
                .finally(() => {
                    this.loadingTransactions = false;
                });
        },
    }">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Wallets</h1>
            <p class="text-sm text-gray font-medium">Monitor user balances and platform revenue flow.</p>
            @if(session('success'))
                <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-success/10 text-success rounded-xl text-sm font-semibold">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.wallet.export') }}" class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2">
                <i class="fas fa-download"></i> Export CSV
            </a>
            <button @click="addCreditModal = true" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Add Credit
            </button>
        </div>
    </div>

    <!-- Wallet Hub -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-primary hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Platform Balance</div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($platformBalance, 2) }}</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-caret-up"></i> Updated just now
            </div>
        </div>
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-success hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Added Today</div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($totalAddedToday, 2) }}</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-users mr-1"></i> Updated just now
            </div>
        </div>
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-info hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Spent Today</div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($totalSpentToday, 2) }}</div>
            <div class="mt-2 text-[10px] font-bold text-info flex items-center gap-1">
                <i class="fas fa-comments mr-1"></i> Updated just now
            </div>
        </div>
    </div>

    <!-- Wallet Table -->
    <div class="bg-white rounded-[24px] shadow-md border border-gray-lighter overflow-hidden">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.users.wallet') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray uppercase mb-1 tracking-widest">Search by name / email / phone</label>
                    <input name="search" type="text" value="{{ request('search') }}" placeholder="Search users..." class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-dark text-white py-3 rounded-xl font-bold hover:bg-black transition-all">Search</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-light/50 border-b border-gray-lighter">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">User Details</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Current Balance</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-center">Total Added</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-center">Total Spent</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter">
                        @forelse($wallets as $wallet)
                            <tr class="hover:bg-light/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $wallet->user->name ?? '—' }}</div>
                                    <div class="text-[10px] font-black text-gray uppercase tracking-tighter">ID: #USR-{{ $wallet->user->id ?? $wallet->user_id }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-base font-black text-primary">₹{{ number_format($wallet->balance, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 text-center text-xs font-bold text-success">₹{{ number_format($wallet->total_added ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-center text-xs font-bold text-danger">₹{{ number_format($wallet->total_spent ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        x-data
                                        data-user='@json([
                                            "id" => $wallet->user->id ?? $wallet->user_id,
                                            "name" => $wallet->user->name ?? ""
                                        ])'
                                        @click="openHistory(JSON.parse($el.dataset.user))"
                                        class="px-5 py-2.5 bg-dark text-white text-[10px] font-black uppercase rounded-xl"
                                    >
                                        View History
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray">No wallet records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
                <div class="text-xs font-bold text-gray uppercase tracking-widest">
                    Showing {{ $wallets->firstItem() ?? 0 }} to {{ $wallets->lastItem() ?? 0 }} of {{ $wallets->total() }} wallets
                </div>
                <div>
                    {{ $wallets->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Add Credit Modal -->
    <div x-show="addCreditModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/70 shadow-2xl backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">

        <div class="bg-white w-full max-w-xl rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden" @click.away="addCreditModal = false">
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div>
                    <h3 class="text-2xl font-black text-dark leading-tight">Add Credit to Wallet</h3>
                    <p class="text-sm text-gray mt-1">Credit amount will be added immediately to the user wallet.</p>
                </div>
                <button @click="addCreditModal = false" class="w-12 h-12 bg-light hover:bg-gray-lighter text-gray-light hover:text-dark rounded-2xl flex items-center justify-center transition-all transform hover:rotate-90">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form action="{{ route('admin.users.wallet.topup') }}" method="POST" class="p-8 space-y-6">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray uppercase tracking-widest mb-2">Select User</label>
                    <select name="user_id" x-model="topup.user_id" required class="w-full px-4 py-3 border border-gray-lighter rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white">
                        @foreach($usersForTopup as $u)
                            <option value="{{ $u->id }}" :selected="topup.user_id == '{{ $u->id }}'">{{ $u->name }} ({{ $u->email ?? '–' }})</option>
                        @endforeach
                    </select>
                    @error('user_id') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray uppercase tracking-widest mb-2">Amount</label>
                    <input name="amount" x-model="topup.amount" type="number" min="1" step="0.01" required class="w-full px-4 py-3 border border-gray-lighter rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white" placeholder="Enter amount in ₹">
                    @error('amount') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray uppercase tracking-widest mb-2">Note (optional)</label>
                    <input name="note" x-model="topup.note" type="text" class="w-full px-4 py-3 border border-gray-lighter rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white" placeholder="e.g., refund, manual credit">
                    @error('note') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="addCreditModal = false" class="px-8 py-3.5 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all">Cancel</button>
                    <button type="submit" class="px-8 py-3.5 bg-primary text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary-dark transition-all">Add Credit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction History Modal -->
    <div x-show="historyModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/70 shadow-2xl backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">

        <div class="bg-white w-full max-w-2xl rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden" @click.away="historyModal = false">
            <div class="p-8 border-b border-gray-lighter flex justify-between items-center bg-light/30">
                <div>
                    <h3 class="text-2xl font-black text-dark leading-tight" x-text="'Transactions: ' + selectedUser.name"></h3>
                    <p class="text-[11px] font-black text-gray uppercase tracking-widest mt-1" x-text="'ID: #USR-' + selectedUser.id"></p>
                </div>
                <button @click="historyModal = false" class="w-12 h-12 bg-light hover:bg-gray-lighter text-gray-light hover:text-dark rounded-2xl flex items-center justify-center transition-all transform hover:rotate-90">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8 max-h-[500px] overflow-y-auto space-y-4 custom-scrollbar">
                <template x-if="loadingTransactions">
                    <div class="text-center py-10 text-gray">Loading transactions...</div>
                </template>

                <template x-if="!loadingTransactions && transactions.length === 0">
                    <div class="text-center py-10 text-gray">No transactions available for this wallet.</div>
                </template>

                <template x-for="tx in transactions" :key="tx.id">
                    <div class="flex items-center justify-between p-5 bg-light/50 rounded-3xl border border-gray-lighter hover:border-primary/20 transition-all group">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform" :class="tx.transaction_type === 'credit' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                                <i :class="tx.transaction_type === 'credit' ? 'fas fa-plus' : 'fas fa-minus'"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-dark" x-text="tx.description || (tx.transaction_type === 'credit' ? 'Credit' : 'Debit')"></div>
                                <div class="text-[10px] font-black text-gray-light uppercase tracking-widest mt-0.5" x-text="new Date(tx.created_at).toLocaleString()"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-black" :class="tx.transaction_type === 'credit' ? 'text-success' : 'text-danger'" x-text="(tx.transaction_type === 'credit' ? '+' : '-') + '₹' + parseFloat(tx.amount).toFixed(2)"></div>
                            <div class="text-[10px] font-black text-gray-light uppercase tracking-widest" x-text="tx.status"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-3">
                <button @click="historyModal = false" class="px-10 py-3.5 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
