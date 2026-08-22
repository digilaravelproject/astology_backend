@extends('admin.layouts.app')

@section('content')
<div x-data="{
    activeTab: '{{ request('active_tab', 'astrologers') }}',
    payoutModal: false,
    loadingAstro: false,
    selectedAstro: null,
    bankAccounts: [],
    walletBalance: 0,
    grossAmount: '',
    tdsRate: {{ $tdsConfig['tds_rate'] ?? 10 }},
    tdsThreshold: {{ $tdsConfig['tds_threshold'] ?? 15000 }},
    tdsEnabled: {{ ($tdsConfig['tds_enabled'] ?? true) ? 'true' : 'false' }},
    
    get isTdsApplicable() {
        let gross = parseFloat(this.grossAmount) || 0;
        return this.tdsEnabled && (gross >= this.tdsThreshold);
    },
    get tdsAmount() {
        let gross = parseFloat(this.grossAmount) || 0;
        if (!this.isTdsApplicable) return 0;
        return (gross * this.tdsRate / 100);
    },
    get netPaid() {
        let gross = parseFloat(this.grossAmount) || 0;
        return Math.max(0, gross - this.tdsAmount);
    },

    openPayoutModal(astroId) {
        this.loadingAstro = true;
        this.payoutModal = true;
        this.selectedAstro = null;
        this.bankAccounts = [];
        this.grossAmount = '';

        fetch(`{{ url('admin/astrologer-payouts') }}/${astroId}/context`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    this.selectedAstro = res.data.astrologer;
                    this.walletBalance = res.data.current_balance;
                    this.bankAccounts = res.data.bank_accounts || [];
                    this.grossAmount = this.walletBalance > 0 ? this.walletBalance : '';
                }
            })
            .catch(() => alert('Failed to load astrologer details.'))
            .finally(() => this.loadingAstro = false);
    }
}">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-lighter pb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-black text-dark tracking-tight uppercase">Astrologer Payouts & TDS Settlements</h1>
            <p class="text-sm text-gray font-medium mt-1">Manage partner settlements, automate scalable TDS deductions, and issue verified payout vouchers.</p>
            @if(session('success'))
                <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-success/10 text-success border border-success/20 rounded-xl text-xs font-bold">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-danger/10 text-danger border border-danger/20 rounded-xl text-xs font-bold">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" class="px-5 py-3 bg-white border-2 border-gray-lighter text-dark rounded-2xl font-black hover:border-primary/40 transition-all flex items-center gap-2 text-xs uppercase tracking-wider shadow-sm">
                <i class="fas fa-shield-halved text-primary"></i> TDS Policy
            </a>
        </div>
    </div>

    <!-- Overview Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Unpaid Wallet Liability -->
        <div class="bg-white p-6 rounded-[28px] shadow-sm border border-gray-lighter hover:shadow-lg hover:border-amber-500/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black uppercase text-gray tracking-wider">Unpaid Balances</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                    <i class="fas fa-wallet text-base"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalWalletLiabilities, 2) }}</div>
            <span class="text-[10px] text-gray font-medium mt-1 block">Accrued astrologer liability</span>
        </div>

        <!-- Disbursed This Month -->
        <div class="bg-white p-6 rounded-[28px] shadow-sm border border-gray-lighter hover:shadow-lg hover:border-primary/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black uppercase text-gray tracking-wider">Paid This Month</span>
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <i class="fas fa-calendar-check text-base"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalDisbursedThisMonth, 2) }}</div>
            <span class="text-[10px] text-gray font-medium mt-1 block">Disbursed in {{ now()->format('M Y') }}</span>
        </div>

        <!-- Disbursed All Time -->
        <div class="bg-white p-6 rounded-[28px] shadow-sm border border-gray-lighter hover:shadow-lg hover:border-success/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black uppercase text-gray tracking-wider">Lifetime Disbursed</span>
                <div class="w-10 h-10 rounded-2xl bg-success/10 flex items-center justify-center text-success">
                    <i class="fas fa-hand-holding-dollar text-base"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalDisbursedAllTime, 2) }}</div>
            <span class="text-[10px] text-gray font-medium mt-1 block">Total settled volume</span>
        </div>

        <!-- Total TDS Deducted -->
        <div class="bg-white p-6 rounded-[28px] shadow-sm border border-gray-lighter hover:shadow-lg hover:border-indigo-600/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black uppercase text-gray tracking-wider">TDS Deducted</span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="fas fa-file-invoice-dollar text-base"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalTdsDeductedAllTime, 2) }}</div>
            <span class="text-[10px] text-gray font-medium mt-1 block">Tax compliance withheld</span>
        </div>
    </div>

    <!-- Active TDS Configuration Banner -->
    <div class="mb-8 p-5 bg-gradient-to-r from-primary/5 via-primary/10 to-transparent border border-primary/20 rounded-[28px] flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-primary/15 flex items-center justify-center text-primary text-xl shadow-xs">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h4 class="text-xs font-black text-dark uppercase tracking-wider">Automated TDS Settlement Protocol</h4>
                <p class="text-xs text-gray mt-0.5">
                    @if($tdsConfig['tds_enabled'] ?? true)
                        TDS Deduction Rate: <strong class="text-primary font-black">{{ $tdsConfig['tds_rate'] ?? 10 }}%</strong> | 
                        Statutory Threshold: <strong class="text-dark font-black">₹{{ number_format($tdsConfig['tds_threshold'] ?? 15000, 2) }}</strong> 
                        (Applicable only when gross payout &ge; threshold).
                    @else
                        <span class="text-danger font-bold">TDS is currently disabled. Payouts are disbursed without statutory tax deductions.</span>
                    @endif
                </p>
            </div>
        </div>
        <span class="px-4 py-1.5 bg-white border border-primary/20 text-[10px] font-black text-primary rounded-xl uppercase tracking-widest shadow-xs">
            {{ ($tdsConfig['tds_enabled'] ?? true) ? 'TDS Active' : 'TDS Inactive' }}
        </span>
    </div>

    <!-- Main Tabs & Table Container -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex border-b border-gray-lighter bg-light/30 px-6 pt-4 gap-2">
            <button @click="activeTab = 'astrologers'" :class="activeTab === 'astrologers' ? 'border-primary text-primary bg-white shadow-xs font-black' : 'border-transparent text-gray hover:text-dark font-bold'" class="px-6 py-3.5 border-b-2 text-xs transition-all rounded-t-2xl flex items-center gap-2 uppercase tracking-wider">
                <i class="fas fa-users-gear"></i> Astrologer Balances & Payout Hub
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'border-primary text-primary bg-white shadow-xs font-black' : 'border-transparent text-gray hover:text-dark font-bold'" class="px-6 py-3.5 border-b-2 text-xs transition-all rounded-t-2xl flex items-center gap-2 uppercase tracking-wider">
                <i class="fas fa-receipt"></i> Settlement History & Slips
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: ASTROLOGER BALANCES & PROCESSING -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'astrologers'" class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <form method="GET" action="{{ route('admin.astrologer-payouts.index') }}" class="w-full md:w-96">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search astrologer by name, phone..." class="w-full pl-10 pr-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-xs">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-lighter bg-light/30 text-gray text-[10px] uppercase font-black tracking-widest">
                            <th class="py-4 px-5">Astrologer</th>
                            <th class="py-4 px-5">Contact Details</th>
                            <th class="py-4 px-5">Verified Bank Account</th>
                            <th class="py-4 px-5">Available Balance</th>
                            <th class="py-4 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter text-sm">
                        @forelse($astrologers as $astro)
                            @php
                                $balance = (float) ($astro->user->wallet->balance ?? 0);
                                $defaultBank = $astro->bankAccounts->where('is_default', true)->first() ?? $astro->bankAccounts->first();
                                
                                // Resolve profile photo with full URL and UI-Avatars fallback
                                $rawPhoto = $astro->profile_photo ?: $astro->user?->profile_photo;
                                $photoUrl = \App\Helpers\MediaHelper::getFullUrl($rawPhoto);
                                $astroName = $astro->user->name ?? 'Astrologer #' . $astro->id;
                                $fallbackAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($astroName) . '&background=F59E0B&color=fff&bold=true&size=128';
                            @endphp
                            <tr class="hover:bg-light/40 transition-all group">
                                <!-- Astrologer Info & Profile Photo with Fallback -->
                                <td class="py-4 px-5 font-bold text-dark flex items-center gap-3.5">
                                    <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-br from-primary/10 to-amber-500/10 border border-gray-lighter flex items-center justify-center font-black text-primary text-sm overflow-hidden shrink-0 shadow-xs">
                                        <img src="{{ $photoUrl ?: $fallbackAvatar }}" 
                                             alt="{{ $astroName }}" 
                                             class="w-full h-full object-cover" 
                                             onerror="this.onerror=null; this.src='{{ $fallbackAvatar }}';">
                                    </div>
                                    <div>
                                        <div class="font-black text-dark text-sm group-hover:text-primary transition-colors">{{ $astroName }}</div>
                                        <div class="text-[10px] font-bold text-gray uppercase tracking-wider">UID: AST-{{ $astro->id }} • {{ $astro->user->gender ? ucfirst($astro->user->gender) : 'Verified Partner' }}</div>
                                    </div>
                                </td>

                                <!-- Contact Info -->
                                <td class="py-4 px-5">
                                    <div class="text-xs text-dark font-black">{{ $astro->user->phone ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-gray mt-0.5">{{ $astro->user->email ?? 'N/A' }}</div>
                                </td>

                                <!-- Bank Account -->
                                <td class="py-4 px-5">
                                    @if($defaultBank)
                                        <div class="text-xs font-black text-dark">{{ $defaultBank->bank_name }}</div>
                                        <div class="text-[11px] font-mono text-gray mt-0.5">A/C: {{ $defaultBank->account_number }} • IFSC: {{ $defaultBank->ifsc_code }}</div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-black uppercase bg-danger/10 text-danger border border-danger/20">No Verified Bank</span>
                                    @endif
                                </td>

                                <!-- Available Balance -->
                                <td class="py-4 px-5">
                                    <span class="text-base font-black {{ $balance > 0 ? 'text-success' : 'text-gray' }}">
                                        ₹{{ number_format($balance, 2) }}
                                    </span>
                                </td>

                                <!-- Action Button -->
                                <td class="py-4 px-5 text-right">
                                    <button @click="openPayoutModal({{ $astro->id }})" class="px-4 py-2.5 bg-primary text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-xs hover:bg-primary-dark hover:shadow-md transition-all inline-flex items-center gap-2">
                                        <i class="fas fa-paper-plane text-xs"></i> Process Payout
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray text-xs italic">No approved astrologers found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $astrologers->links() }}
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: SETTLEMENT HISTORY -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'history'" class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <form method="GET" action="{{ route('admin.astrologer-payouts.index') }}" class="w-full md:w-96">
                    <input type="hidden" name="active_tab" value="history">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray text-xs"></i>
                        <input type="text" name="history_search" value="{{ request('history_search') }}" placeholder="Search by Payout #, UTR, Name..." class="w-full pl-10 pr-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-xs">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-lighter bg-light/30 text-gray text-[10px] uppercase font-black tracking-widest">
                            <th class="py-4 px-5">Payout #</th>
                            <th class="py-4 px-5">Astrologer</th>
                            <th class="py-4 px-5">Gross Amount</th>
                            <th class="py-4 px-5">TDS Deducted</th>
                            <th class="py-4 px-5">Net Paid</th>
                            <th class="py-4 px-5">Payment Mode & UTR</th>
                            <th class="py-4 px-5">Date</th>
                            <th class="py-4 px-5 text-right">Settlement Slip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter text-sm">
                        @forelse($payouts as $payout)
                            @php
                                $historyAstroName = $payout->user->name ?? 'Astrologer #' . $payout->astrologer_id;
                                $historyRawPhoto = $payout->user->profile_photo ?? $payout->astrologer?->profile_photo;
                                $historyPhotoUrl = \App\Helpers\MediaHelper::getFullUrl($historyRawPhoto);
                                $historyFallback = 'https://ui-avatars.com/api/?name=' . urlencode($historyAstroName) . '&background=6366F1&color=fff&bold=true&size=128';
                            @endphp
                            <tr class="hover:bg-light/40 transition-all">
                                <td class="py-4 px-5 font-mono font-black text-primary text-xs">
                                    {{ $payout->payout_number }}
                                </td>
                                <td class="py-4 px-5 font-bold text-dark flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/10 overflow-hidden shrink-0 border border-gray-lighter">
                                        <img src="{{ $historyPhotoUrl ?: $historyFallback }}" alt="{{ $historyAstroName }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='{{ $historyFallback }}';">
                                    </div>
                                    <div>
                                        <div class="font-bold text-dark text-xs">{{ $historyAstroName }}</div>
                                        <div class="text-[10px] text-gray font-normal">AST-{{ $payout->astrologer_id }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-5 font-bold text-dark">
                                    ₹{{ number_format($payout->gross_amount, 2) }}
                                </td>
                                <td class="py-4 px-5 font-bold text-danger">
                                    @if($payout->tds_amount > 0)
                                        - ₹{{ number_format($payout->tds_amount, 2) }}
                                        <span class="text-[10px] text-gray block font-normal">({{ number_format($payout->tds_percent, 1) }}% TDS)</span>
                                    @else
                                        <span class="text-gray text-xs font-normal">₹0.00 (Exempt)</span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 font-black text-success text-base">
                                    ₹{{ number_format($payout->net_paid_amount, 2) }}
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-black uppercase bg-primary/10 text-primary border border-primary/20">
                                        {{ $payout->payment_mode }}
                                    </span>
                                    @if($payout->utr_number)
                                        <div class="text-[10px] font-mono text-gray mt-1">UTR: {{ $payout->utr_number }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-xs text-gray font-medium">
                                    {{ $payout->payment_date ? $payout->payment_date->format('d M Y') : $payout->created_at->format('d M Y') }}
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <a href="{{ route('admin.astrologer-payouts.download-slip', $payout->id) }}" target="_blank" class="px-3 py-1.5 bg-light border border-gray-lighter text-dark rounded-xl text-xs font-black uppercase hover:bg-dark hover:text-white transition-all inline-flex items-center gap-1.5 shadow-xs">
                                        <i class="fas fa-file-pdf text-danger"></i> PDF Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray text-xs italic">No payout settlements recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $payouts->links() }}
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PROCESS PAYOUT MODAL (Alpine.js) -->
    <!-- ========================================================================= -->
    <div x-show="payoutModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="payoutModal = false" class="bg-white rounded-[36px] max-w-xl w-full p-8 shadow-2xl border border-gray-100 transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-lighter pb-5 mb-6">
                <div>
                    <h3 class="text-xl font-black text-dark uppercase tracking-tight">Process Astrologer Payout</h3>
                    <p class="text-xs text-gray font-medium mt-0.5">Disburse earnings with automated statutory TDS deduction.</p>
                </div>
                <button @click="payoutModal = false" class="w-9 h-9 rounded-xl bg-light flex items-center justify-center text-gray hover:text-dark transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <template x-if="loadingAstro">
                <div class="py-16 text-center text-gray">
                    <i class="fas fa-spinner fa-spin text-3xl text-primary"></i>
                    <p class="text-xs font-black uppercase tracking-wider mt-3">Loading financial profile & bank snapshot...</p>
                </div>
            </template>

            <template x-if="!loadingAstro && selectedAstro">
                <form action="{{ route('admin.astrologer-payouts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input type="hidden" name="astrologer_id" :value="selectedAstro.id">

                    <!-- Profile & Balance Card -->
                    <div class="p-5 bg-light/50 rounded-2xl border border-gray-lighter flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center font-black text-primary text-sm">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <div class="text-[10px] text-gray uppercase font-black tracking-wider">Astrologer Partner</div>
                                <div class="text-sm font-black text-dark" x-text="selectedAstro.user ? selectedAstro.user.name : 'Astrologer'"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-gray uppercase font-black tracking-wider">Available Balance</div>
                            <div class="text-xl font-black text-success" x-text="'₹' + Number(walletBalance).toFixed(2)"></div>
                        </div>
                    </div>

                    <!-- Gross Settlement Amount Input -->
                    <div>
                        <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Gross Settlement Amount (₹) *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray text-base">₹</span>
                            <input type="number" step="0.01" name="gross_amount" x-model="grossAmount" min="1" :max="walletBalance" required class="w-full pl-9 pr-4 py-3 border border-gray-lighter rounded-2xl text-base font-black focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                        <span class="text-[10px] text-gray mt-1 block">Max payable: Available wallet balance (₹<span x-text="Number(walletBalance).toFixed(2)"></span>)</span>
                    </div>

                    <!-- Real-Time Tax & Net Breakdown Calculator -->
                    <div class="p-5 bg-gradient-to-br from-primary/5 to-indigo-50/50 rounded-2xl border border-primary/15 space-y-2.5">
                        <div class="flex justify-between text-xs text-gray font-bold">
                            <span>Gross Settlement:</span>
                            <span class="text-dark font-black" x-text="'₹' + (parseFloat(grossAmount) || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-danger font-bold">
                            <span>TDS Deduction (<span x-text="isTdsApplicable ? tdsRate + '%' : '0% (Exempt)'"></span>):</span>
                            <span class="font-black" x-text="'- ₹' + tdsAmount.toFixed(2)"></span>
                        </div>
                        <div class="border-t border-primary/15 pt-2.5 flex justify-between text-sm font-black text-dark">
                            <span>Net Disbursed to Bank:</span>
                            <span class="text-success text-lg font-black" x-text="'₹' + netPaid.toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Bank Account Selection -->
                    <div>
                        <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Disbursement Bank Account *</label>
                        <template x-if="bankAccounts.length > 0">
                            <select name="bank_account_id" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <template x-for="bank in bankAccounts" :key="bank.id">
                                    <option :value="bank.id" x-text="bank.bank_name + ' - A/C: ' + bank.account_number + ' (IFSC: ' + bank.ifsc_code + ')' + (bank.is_default ? ' [Default]' : '')"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="bankAccounts.length === 0">
                            <div class="space-y-2.5 p-4 bg-danger/5 border border-danger/10 rounded-2xl">
                                <span class="text-[11px] text-danger font-bold block">No pre-verified bank account found. Enter transfer details:</span>
                                <input type="text" name="custom_bank_name" placeholder="Bank Name" class="w-full px-3 py-2 border border-gray-lighter rounded-xl text-xs font-bold">
                                <input type="text" name="custom_account_number" placeholder="Account Number" class="w-full px-3 py-2 border border-gray-lighter rounded-xl text-xs font-mono">
                                <input type="text" name="custom_ifsc" placeholder="IFSC Code" class="w-full px-3 py-2 border border-gray-lighter rounded-xl text-xs font-mono">
                            </div>
                        </template>
                    </div>

                    <!-- Payment Mode & UTR -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Payment Mode *</label>
                            <select name="payment_mode" required class="w-full px-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold">
                                <option value="IMPS">IMPS (Instant)</option>
                                <option value="NEFT" selected>NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">UTR / Reference No.</label>
                            <input type="text" name="utr_number" placeholder="e.g. UTR123456789" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl text-xs font-mono font-bold">
                        </div>
                    </div>

                    <!-- Payment Date & Receipt Attachment -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Payment Date *</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full px-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Payment Proof (Optional)</label>
                            <input type="file" name="receipt_proof" accept="application/pdf,image/*" class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-light file:text-dark hover:file:bg-gray-200">
                        </div>
                    </div>

                    <!-- Remarks / Notes -->
                    <div>
                        <label class="block text-[11px] font-black text-gray uppercase tracking-wider mb-2">Settlement Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="e.g. Monthly settlement for current billing cycle" class="w-full px-4 py-3 border border-gray-lighter rounded-2xl text-xs font-bold">
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-lighter">
                        <button type="button" @click="payoutModal = false" class="px-5 py-2.5 border border-gray-lighter rounded-xl text-xs font-black uppercase text-gray hover:bg-light">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md hover:bg-primary-dark transition-all">Disburse & Deduct Wallet</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
