@extends('admin.layouts.app')

@section('content')
<div x-data="{ invoiceModal: false, selectedSub: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Subscription Lifecycle</h1>
            <p class="text-sm text-gray font-medium">Monitoring recurring revenue and user commitment levels.</p>
        </div>
        <div class="flex gap-3">
            <button class="bg-white border border-gray-lighter text-dark px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-file-export mr-2"></i> Export Ledger
            </button>
            <a href="{{ route('admin.plans.create') }}" class="bg-primary text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="fas fa-plus"></i> Configure New Tier
            </a>
        </div>
    </div>

    <!-- Tier Analytics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Live Subscriptions</div>
            <div class="text-3xl font-black text-dark">1,284</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-arrow-up"></i> 14% Retention
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Est. MRR</div>
            <div class="text-3xl font-black text-dark">₹ 4.2L</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-chart-line"></i> +₹ 45K Delta
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-info/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Churn Probability</div>
            <div class="text-3xl font-black text-dark">2.4%</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-shield-alt"></i> Low Risk
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Top Tier Adopt</div>
            <div class="text-3xl font-black text-dark">42%</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-gem"></i> Platinum Heavy
            </div>
        </div>
    </div>

    <!-- Subscription Console -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Universal Search</label>
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input type="text" placeholder="Search by Subscription ID, User Name..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </div>
        </div>
        <div class="w-full sm:w-40">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Plan Level</label>
            <select class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                <option>All Tiers</option>
                <option>Silver (Lite)</option>
                <option>Gold (Pro)</option>
                <option>Platinum (Elite)</option>
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Lifecycle</label>
            <select class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                <option>Active Only</option>
                <option>Expiring Soon</option>
                <option>Lapsed</option>
                <option>Cancelled</option>
            </select>
        </div>
        <button class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[52px]">Filter Ecosystem</button>
    </div>

    <!-- Subscription Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Subscriber Identity</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Plan Configuration</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-center">Settlement</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Lifecycle Span</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Auto-Renew</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Invoicing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @php
                        $subs = [
                            ['id' => 'SUB-9941', 'user' => 'Rajesh Khanna', 'plan' => 'Platinum Elite', 'amount' => '₹ 2,499', 'start' => '12 Oct 2024', 'end' => '12 Nov 2024', 'status' => 'Active', 'renew' => 'Yes', 'method' => 'UPI'],
                            ['id' => 'SUB-9942', 'user' => 'Priya Sharma', 'plan' => 'Gold Pro', 'amount' => '₹ 999', 'start' => '10 Oct 2024', 'end' => '10 Nov 2024', 'status' => 'Active', 'renew' => 'No', 'method' => 'Card'],
                            ['id' => 'SUB-9943', 'user' => 'Amitabh V.', 'plan' => 'Silver Lite', 'amount' => '₹ 499', 'start' => '05 Oct 2024', 'end' => '05 Nov 2024', 'status' => 'Expiring', 'renew' => 'Yes', 'method' => 'Wallet'],
                            ['id' => 'SUB-9944', 'user' => 'Deepika P.', 'plan' => 'Platinum Elite', 'amount' => '₹ 2,499', 'start' => '01 Oct 2024', 'end' => '01 Nov 2024', 'status' => 'Active', 'renew' => 'Yes', 'method' => 'UPI'],
                            ['id' => 'SUB-9945', 'user' => 'Ranveer Singh', 'plan' => 'Gold Pro', 'amount' => '₹ 999', 'start' => '25 Sep 2024', 'end' => '25 Oct 2024', 'status' => 'Lapsed', 'renew' => 'No', 'method' => 'Card'],
                            ['id' => 'SUB-9946', 'user' => 'Salman Khan', 'plan' => 'Platinum Elite', 'amount' => '₹ 2,499', 'start' => '20 Sep 2024', 'end' => '20 Oct 2024', 'status' => 'Active', 'renew' => 'Yes', 'method' => 'Net Banking'],
                            ['id' => 'SUB-9947', 'user' => 'Shahrukh R.', 'plan' => 'Silver Lite', 'amount' => '₹ 499', 'start' => '15 Sep 2024', 'end' => '15 Oct 2024', 'status' => 'Cancelled', 'renew' => 'No', 'method' => 'Wallet'],
                            ['id' => 'SUB-9948', 'user' => 'Kareena K.', 'plan' => 'Gold Pro', 'amount' => '₹ 999', 'start' => '10 Sep 2024', 'end' => '10 Oct 2024', 'status' => 'Active', 'renew' => 'Yes', 'method' => 'UPI'],
                        ];
                    @endphp

                    @foreach($subs as $sub)
                    <tr class="hover:bg-light/30 transition-all group">
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-dark text-white flex items-center justify-center font-black text-[10px]">{{ substr($sub['user'], 0, 1) }}</div>
                                <div>
                                    <div class="text-sm font-black text-dark group-hover:text-primary transition-colors">{{ $sub['user'] }}</div>
                                    <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-0.5">{{ $sub['id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-xs font-black text-dark">{{ $sub['plan'] }}</div>
                            <div class="text-[9px] font-bold text-gray italic">Recurring Every 30 Days</div>
                        </td>
                    @php
                        $expiryClasses = $sub['status'] == 'Expiring' 
                            ? 'bg-red-50 text-red-900 border-red-200' 
                            : 'bg-white text-slate-800 border-slate-100';
                        $renewDotClass = $sub['renew'] == 'Yes' 
                            ? 'bg-emerald-600' 
                            : 'bg-slate-200';
                    @endphp
                    <tr>
                        <td class="px-6 py-5 text-center">
                            <div class="text-sm font-black text-dark">{{ $sub['amount'] }}</div>
                            <div class="text-[8px] font-black text-gray uppercase tracking-widest mt-1">{{ $sub['method'] }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="text-center bg-light/50 border border-gray-lighter p-2 rounded-xl">
                                    <div class="text-[8px] font-black text-gray uppercase leading-none mb-1">Start</div>
                                    <div class="text-[10px] font-black text-dark leading-none">{{ $sub['start'] }}</div>
                                </div>
                                <i class="fas fa-arrow-right text-[10px] text-gray-lighter"></i>
                                <div class="text-center border p-2 rounded-xl {{ $expiryClasses }}">
                                    <div class="text-[8px] font-black text-slate-500 uppercase leading-none mb-1">Expiry</div>
                                    <div class="text-[10px] font-black leading-none">{{ $sub['end'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full {{ $renewDotClass }}"></div>
                                <span class="text-[10px] font-black text-dark uppercase tracking-widest">{{ $sub['renew'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button @click="selectedSub = {{ json_encode($sub) }}; invoiceModal = true" class="px-4 py-2.5 bg-white border border-gray-lighter text-dark text-[10px] font-black uppercase rounded-xl hover:bg-dark hover:text-white hover:border-dark transition-all shadow-sm">
                                <i class="fas fa-file-invoice mr-2"></i> Fetch Invoice
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Analyzing 8 of 1,284 Contracts</div>
            <div class="flex items-center gap-1.5">
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-dark text-white font-black text-xs">1</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-dark font-black text-xs hover:bg-dark hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div x-show="invoiceModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-[0_40px_120px_rgba(0,0,0,0.5)] overflow-hidden" @click.away="invoiceModal = false">
            <div class="bg-dark p-10 text-white relative h-48 flex items-center justify-between border-b-8 border-primary">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <i class="fas fa-receipt text-9xl"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-black uppercase tracking-[0.4em] text-primary mb-2">Tax Invoice</h3>
                    <h2 class="text-4xl font-black tracking-tighter uppercase" x-text="selectedSub.id"></h2>
                </div>
                <button @click="invoiceModal = false" class="w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-2xl flex items-center justify-center backdrop-blur-md transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-10">
                <div class="grid grid-cols-2 gap-10 mb-10">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Contract Party</div>
                        <div class="text-lg font-black text-dark mb-1" x-text="selectedSub.user"></div>
                        <div class="text-[11px] font-bold text-gray-light leading-relaxed">
                            C-124, Green Valley Estate,<br>
                            Andheri East, Mumbai 400069<br>
                            +91 98XXX XXXX1
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Origin Entity</div>
                        <div class="text-lg font-black text-dark mb-1 underline decoration-primary/30 underline-offset-4 tracking-tighter uppercase">ASTOLOGY ADMIN</div>
                        <div class="text-[11px] font-bold text-gray-light leading-relaxed">
                            GSTIN: 27AAAAA0000A1Z5<br>
                            Digital HQ, Sector 62<br>
                            Noida, Uttar Pradesh
                        </div>
                    </div>
                </div>

                <div class="bg-light/30 rounded-3xl border border-gray-lighter overflow-hidden mb-10">
                    <table class="w-full text-left">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-6 py-4 text-[9px] font-black text-gray uppercase tracking-widest">Service Description</th>
                                <th class="px-6 py-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Settlement Amt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            <tr>
                                <td class="px-6 py-6">
                                    <div class="text-xs font-black text-dark" x-text="selectedSub.plan + ' Subscription Package'"></div>
                                    <div class="text-[9px] font-bold text-gray-light mt-1 uppercase" x-text="'Term: ' + selectedSub.start + ' to ' + selectedSub.end"></div>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <div class="text-sm font-black text-dark" x-text="selectedSub.amount"></div>
                                </td>
                            </tr>
                            <tr class="bg-light/50">
                                <td class="px-6 py-4 text-[10px] font-black text-gray uppercase tracking-widest">Subtotal Value</td>
                                <td class="px-6 py-4 text-right text-xs font-black text-dark" x-text="selectedSub.amount"></td>
                            </tr>
                            <tr class="bg-light/50 border-t border-gray-lighter">
                                <td class="px-6 py-4 text-[10px] font-black text-gray uppercase tracking-widest">Tax Component (18% GST)</td>
                                <td class="px-6 py-4 text-right text-xs font-black text-dark italic">Inclusive</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center text-[10px]">
                                <i class="fas fa-shield-check"></i>
                            </div>
                            <div>
                                <div class="text-[9px] font-black text-dark uppercase tracking-widest">Authenticated Transaction</div>
                                <div class="text-[8px] font-bold text-gray uppercase mt-0.5" x-text="'Ref: ' + Math.random().toString(36).substring(7).toUpperCase()"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all flex items-center gap-3">
                            <i class="fas fa-print"></i> Physical Copy
                        </button>
                        <button class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-xl shadow-dark/20 flex items-center gap-3 group">
                            <i class="fas fa-download group-hover:scale-125 transition-all"></i> Save Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
