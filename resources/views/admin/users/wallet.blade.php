@extends('admin.layouts.app')

@section('content')
<div x-data="{ historyModal: false, selectedUser: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Wallets</h1>
            <p class="text-sm text-gray font-medium">Monitor user balances and platform revenue flow.</p>
        </div>
        <div class="flex gap-2">
            <button class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Add Credit
            </button>
        </div>
    </div>

    <!-- Wallet Hub -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-primary hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Platform Balance</div>
            <div class="text-3xl font-black text-dark">₹12,45,670</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-caret-up"></i> +4.2% from last month
            </div>
        </div>
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-success hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Added Today</div>
            <div class="text-3xl font-black text-dark">₹45,300</div>
            <div class="mt-2 text-[10px] font-bold text-success flex items-center gap-1">
                <i class="fas fa-users mr-1"></i> 128 users recharged
            </div>
        </div>
        <div class="bg-white p-7 rounded-[24px] shadow-md border-l-[6px] border-info hover:shadow-xl transition-all">
            <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Total Spent Today</div>
            <div class="text-3xl font-black text-dark">₹28,150</div>
            <div class="mt-2 text-[10px] font-bold text-info flex items-center gap-1">
                <i class="fas fa-comments mr-1"></i> 342 sessions paid
            </div>
        </div>
    </div>

    <!-- Wallet Table -->
    <div class="bg-white rounded-[24px] shadow-md border border-gray-lighter overflow-hidden">
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
                    @php
                        $walletUsers = [
                            ['name' => 'Rahul Sharma', 'id' => '101', 'balance' => '₹1,250', 'added' => '₹5,000', 'spent' => '₹3,750'],
                            ['name' => 'Priya Patel', 'id' => '102', 'balance' => '₹450', 'added' => '₹2,000', 'spent' => '₹1,550'],
                            ['name' => 'Sneha Gupta', 'id' => '104', 'balance' => '₹2,800', 'added' => '₹8,000', 'spent' => '₹5,200'],
                            ['name' => 'Anjali Verma', 'id' => '106', 'balance' => '₹920', 'added' => '₹3,500', 'spent' => '₹2,580'],
                            ['name' => 'Pooja Reddy', 'id' => '108', 'balance' => '₹1,500', 'added' => '₹4,500', 'spent' => '₹3,000'],
                            ['name' => 'Kavita Joshi', 'id' => '110', 'balance' => '₹2,100', 'added' => '₹6,000', 'spent' => '₹3,900'],
                            ['name' => 'Meera Bai', 'id' => '112', 'balance' => '₹780', 'added' => '₹2,500', 'spent' => '₹1,720'],
                            ['name' => 'Shweta Tiwari', 'id' => '114', 'balance' => '₹3,400', 'added' => '₹10,000', 'spent' => '₹6,600'],
                            ['name' => 'Amit Kumar', 'id' => '103', 'balance' => '₹0', 'added' => '₹1,000', 'spent' => '₹1,000'],
                            ['name' => 'Vikram Singh', 'id' => '105', 'balance' => '₹150', 'added' => '₹500', 'spent' => '₹350'],
                            ['name' => 'Raj Malhotra', 'id' => '107', 'balance' => '₹55', 'added' => '₹3,000', 'spent' => '₹2,945'],
                            ['name' => 'Arjun Nair', 'id' => '109', 'balance' => '₹300', 'added' => '₹1,500', 'spent' => '₹1,200'],
                            ['name' => 'Sanjay Dutt', 'id' => '111', 'balance' => '₹0', 'added' => '₹0', 'spent' => '₹0'],
                            ['name' => 'Aman Gupta', 'id' => '115', 'balance' => '₹500', 'added' => '₹2,500', 'spent' => '₹2,000'],
                            ['name' => 'Deepak Chopra', 'id' => '113', 'balance' => '₹120', 'added' => '₹1,200', 'spent' => '₹1,080'],
                        ];
                    @endphp
                    @foreach($walletUsers as $user)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $user['name'] }}</div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-tighter">ID: #USR-{{ $user['id'] }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-base font-black text-primary">{{ $user['balance'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-center text-xs font-bold text-success">{{ $user['added'] }}</td>
                        <td class="px-6 py-4 text-center text-xs font-bold text-danger">{{ $user['spent'] }}</td>
                        <td class="px-6 py-4 text-right">
                            <button @click="selectedUser = {{ json_encode($user) }}; historyModal = true" class="px-5 py-2.5 bg-dark text-white text-[10px] font-black uppercase rounded-xl hover:bg-black transition-all hover:scale-105 active:scale-95 transform">View History</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing 15 of 1,200 wallets</div>
            <div class="flex items-center gap-2">
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center hover:bg-primary hover:text-white transition-all transform active:scale-90"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl border border-gray-lighter flex items-center justify-center hover:bg-primary hover:text-white transition-all transform active:scale-90"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
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
                @foreach(['Today, 4:30 PM' => ['Added via UPI', '+₹500', 'Credit', 'bg-success/10 text-success'], 'Yesterday, 11:20 AM' => ['Chat Session #ORD-4402', '-₹120', 'Debit', 'bg-danger/10 text-danger'], '11 Mar, 8:15 PM' => ['Call Session #ORD-4389', '-₹450', 'Debit', 'bg-danger/10 text-danger'], '10 Mar, 2:10 PM' => ['Referral Bonus', '+₹50', 'Credit', 'bg-success/10 text-success'], '09 Mar, 10:00 AM' => ['Added via Card', '+₹1,000', 'Credit', 'bg-success/10 text-success']] as $date => $tx)
                <div class="flex items-center justify-between p-5 bg-light/50 rounded-3xl border border-gray-lighter hover:border-primary/20 transition-all group">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform {{ $tx[3] }}">
                            @if($tx[2] == 'Credit') <i class="fas fa-plus"></i> @else <i class="fas fa-minus"></i> @endif
                        </div>
                        <div>
                            <div class="text-sm font-black text-dark">{{ $tx[0] }}</div>
                            <div class="text-[10px] font-black text-gray-light uppercase tracking-widest mt-0.5">{{ $date }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-base font-black {{ $tx[2] == 'Credit' ? 'text-success' : 'text-danger' }}">{{ $tx[1] }}</div>
                        <div class="text-[10px] font-black text-gray-light uppercase tracking-widest">{{ $tx[2] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-3">
                <button class="px-6 py-3.5 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all">Download PDF</button>
                <button @click="historyModal = false" class="px-10 py-3.5 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
