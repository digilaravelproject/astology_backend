@extends('admin.layouts.app')

@section('content')
<div x-data="{
    activeTab: 'astrologers',
    payoutModal: false,
    loadingAstro: false,
    selectedAstro: null,
    bankAccounts: [],
    walletBalance: 0,
    grossAmount: '',
    tdsRate: {{ $tdsConfig['tds_rate'] }},
    tdsThreshold: {{ $tdsConfig['tds_threshold'] }},
    tdsEnabled: {{ $tdsConfig['tds_enabled'] ? 'true' : 'false' }},
    
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
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-dark tracking-tight">Astrologer Payouts & TDS Settlements</h1>
            <p class="text-sm text-gray font-medium">Manage monthly partner settlements, automate scalable TDS deductions, and issue official payment slips.</p>
            @if(session('success'))
                <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-success/10 text-success rounded-xl text-sm font-semibold">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-danger/10 text-danger rounded-xl text-sm font-semibold">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" class="px-4 py-2.5 bg-white border border-gray-lighter text-dark rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2 text-sm shadow-xs">
                <i class="fas fa-cog text-primary"></i> TDS Configuration
            </a>
        </div>
    </div>

    <!-- Overview Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Unpaid Wallet Liability -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border-l-4 border-amber-500 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-extrabold uppercase text-gray tracking-wider">Unpaid Balances</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <i class="fas fa-wallet text-lg"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalWalletLiabilities, 2) }}</div>
            <span class="text-[11px] text-gray font-medium">Total accrued astrologer earnings</span>
        </div>

        <!-- Disbursed This Month -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border-l-4 border-primary hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-extrabold uppercase text-gray tracking-wider">Paid This Month</span>
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <i class="fas fa-calendar-check text-lg"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalDisbursedThisMonth, 2) }}</div>
            <span class="text-[11px] text-gray font-medium">Disbursed since {{ now()->startOfMonth()->format('d M') }}</span>
        </div>

        <!-- Disbursed All Time -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border-l-4 border-success hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-extrabold uppercase text-gray tracking-wider">Total Disbursed</span>
                <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center text-success">
                    <i class="fas fa-hand-holding-usd text-lg"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalDisbursedAllTime, 2) }}</div>
            <span class="text-[11px] text-gray font-medium">Lifetime net payouts</span>
        </div>

        <!-- Total TDS Deducted -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border-l-4 border-indigo-600 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-extrabold uppercase text-gray tracking-wider">TDS Deducted</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="fas fa-file-invoice-dollar text-lg"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-dark">₹{{ number_format($totalTdsDeductedAllTime, 2) }}</div>
            <span class="text-[11px] text-gray font-medium">Remitted under Income Tax Act</span>
        </div>
    </div>

    <!-- Active TDS Configuration Alert -->
    <div class="mb-8 p-4 bg-primary/5 border border-primary/20 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-lg">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h4 class="text-sm font-bold text-dark">Active Tax Deduction at Source (TDS) Policy</h4>
                <p class="text-xs text-gray mt-0.5">
                    @if($tdsConfig['tds_enabled'])
                        TDS Rate: <strong class="text-primary">{{ $tdsConfig['tds_rate'] }}%</strong> | 
                        Minimum Exemption Threshold: <strong class="text-dark">₹{{ number_format($tdsConfig['tds_threshold'], 2) }}</strong> 
                        (TDS applies only when settlement amount &ge; threshold).
                    @else
                        <span class="text-danger font-bold">TDS is currently DISABLED globally. Payouts are disbursed with 0% tax deduction.</span>
                    @endif
                </p>
            </div>
        </div>
        <span class="px-3 py-1 bg-white border text-xs font-bold text-primary rounded-lg uppercase tracking-wider">
            {{ $tdsConfig['tds_enabled'] ? 'TDS Active' : 'TDS Inactive' }}
        </span>
    </div>

    <!-- Main Tabs & Table Container -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-lighter overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex border-b border-gray-lighter bg-light/30 px-6 pt-4">
            <button @click="activeTab = 'astrologers'" :class="activeTab === 'astrologers' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray hover:text-dark'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all rounded-t-xl flex items-center gap-2">
                <i class="fas fa-users-cog"></i> Astrologer Balances & Payout Processing
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray hover:text-dark'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all rounded-t-xl flex items-center gap-2">
                <i class="fas fa-receipt"></i> Settlement History & Invoices
            </button>
        </div>

        <!-- TAB 1: Astrologer Balances -->
        <div x-show="activeTab === 'astrologers'" class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <form method="GET" action="{{ route('admin.astrologer-payouts.index') }}" class="w-full md:w-80">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search astrologer by name, phone..." class="w-full pl-10 pr-4 py-2.5 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-lighter bg-light/20 text-gray text-xs uppercase font-extrabold tracking-wider">
                            <th class="py-3 px-4">Astrologer</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Verified Bank A/C</th>
                            <th class="py-3 px-4">Available Balance</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter text-sm">
                        @forelse($astrologers as $astro)
                            @php
                                $balance = (float) ($astro->user->wallet->balance ?? 0);
                                $defaultBank = $astro->bankAccounts->where('is_default', true)->first() ?? $astro->bankAccounts->first();
                            @endphp
                            <tr class="hover:bg-light/30 transition-all">
                                <td class="py-4 px-4 font-bold text-dark flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold overflow-hidden">
                                        @if($astro->profile_photo)
                                            <img src="{{ \App\Helpers\MediaHelper::getUrl($astro->profile_photo) }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($astro->user->name ?? 'A', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-dark">{{ $astro->user->name ?? 'Astrologer' }}</div>
                                        <div class="text-xs text-gray">ID: #{{ $astro->id }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="text-xs text-dark font-medium">{{ $astro->user->phone ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray">{{ $astro->user->email ?? 'N/A' }}</div>
                                </td>
                                <td class="py-4 px-4">
                                    @if($defaultBank)
                                        <div class="text-xs font-bold text-dark">{{ $defaultBank->bank_name }}</div>
                                        <div class="text-xs font-mono text-gray">A/C: {{ $defaultBank->account_number }} ({{ $defaultBank->ifsc_code }})</div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-danger/10 text-danger">No Verified Bank</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <span class="text-base font-black {{ $balance > 0 ? 'text-success' : 'text-gray' }}">
                                        ₹{{ number_format($balance, 2) }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button @click="openPayoutModal({{ $astro->id }})" class="px-4 py-2 bg-primary text-white rounded-xl text-xs font-bold shadow-xs hover:bg-primary-dark transition-all inline-flex items-center gap-1.5">
                                        <i class="fas fa-paper-plane"></i> Process Payout
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray text-sm">No approved astrologers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $astrologers->links() }}
            </div>
        </div>

        <!-- TAB 2: Settlement History -->
        <div x-show="activeTab === 'history'" class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <form method="GET" action="{{ route('admin.astrologer-payouts.index') }}" class="w-full md:w-80">
                    <input type="hidden" name="active_tab" value="history">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray text-xs"></i>
                        <input type="text" name="history_search" value="{{ request('history_search') }}" placeholder="Search by Payout #, UTR, Name..." class="w-full pl-10 pr-4 py-2.5 border border-gray-lighter rounded-xl text-sm focus:outline-none focus:border-primary transition-all">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-lighter bg-light/20 text-gray text-xs uppercase font-extrabold tracking-wider">
                            <th class="py-3 px-4">Payout #</th>
                            <th class="py-3 px-4">Astrologer</th>
                            <th class="py-3 px-4">Gross Amount</th>
                            <th class="py-3 px-4">TDS Deducted</th>
                            <th class="py-3 px-4">Net Paid</th>
                            <th class="py-3 px-4">Payment Mode & UTR</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Settlement Slip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-lighter text-sm">
                        @forelse($payouts as $payout)
                            <tr class="hover:bg-light/30 transition-all">
                                <td class="py-4 px-4 font-mono font-bold text-primary">
                                    {{ $payout->payout_number }}
                                </td>
                                <td class="py-4 px-4 font-bold text-dark">
                                    {{ $payout->user->name ?? 'Astrologer' }}
                                    <div class="text-xs text-gray font-normal">#{{ $payout->astrologer_id }}</div>
                                </td>
                                <td class="py-4 px-4 font-bold text-dark">
                                    ₹{{ number_format($payout->gross_amount, 2) }}
                                </td>
                                <td class="py-4 px-4 font-bold text-danger">
                                    @if($payout->tds_amount > 0)
                                        - ₹{{ number_format($payout->tds_amount, 2) }}
                                        <span class="text-[10px] text-gray block">({{ number_format($payout->tds_percent, 1) }}%)</span>
                                    @else
                                        <span class="text-gray text-xs">₹0.00 (Exempt)</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 font-black text-success text-base">
                                    ₹{{ number_format($payout->net_paid_amount, 2) }}
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary">
                                        {{ $payout->payment_mode }}
                                    </span>
                                    @if($payout->utr_number)
                                        <div class="text-xs font-mono text-gray mt-0.5">UTR: {{ $payout->utr_number }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-xs text-gray font-medium">
                                    {{ $payout->payment_date->format('d M Y') }}
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <a href="{{ route('admin.astrologer-payouts.download-slip', $payout->id) }}" target="_blank" class="px-3 py-1.5 bg-light border border-gray-lighter text-dark rounded-lg text-xs font-bold hover:bg-gray-100 transition-all inline-flex items-center gap-1">
                                        <i class="fas fa-file-pdf text-danger"></i> PDF Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray text-sm">No payout settlements recorded yet.</td>
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

    <!-- PROCESS PAYOUT MODAL (Alpine.js) -->
    <div x-show="payoutModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="payoutModal = false" class="bg-white rounded-3xl max-w-xl w-full p-8 shadow-2xl border border-gray-100 transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-lighter pb-4 mb-6">
                <div>
                    <h3 class="text-lg font-extrabold text-dark">Process Astrologer Payout</h3>
                    <p class="text-xs text-gray mt-0.5">Disburse monthly earnings with automated TDS deduction.</p>
                </div>
                <button @click="payoutModal = false" class="text-gray hover:text-dark">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <template x-if="loadingAstro">
                <div class="py-12 text-center text-gray">
                    <i class="fas fa-spinner fa-spin text-2xl text-primary"></i>
                    <p class="text-xs font-bold mt-2">Loading financial profile...</p>
                </div>
            </template>

            <template x-if="!loadingAstro && selectedAstro">
                <form action="{{ route('admin.astrologer-payouts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input type="hidden" name="astrologer_id" :value="selectedAstro.id">

                    <!-- Profile & Balance Card -->
                    <div class="p-4 bg-light/40 rounded-2xl border border-gray-lighter flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray uppercase font-bold">Astrologer Partner</div>
                            <div class="text-sm font-bold text-dark" x-text="selectedAstro.user ? selectedAstro.user.name : 'Astrologer'"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray uppercase font-bold">Wallet Balance</div>
                            <div class="text-lg font-black text-success" x-text="'₹' + Number(walletBalance).toFixed(2)"></div>
                        </div>
                    </div>

                    <!-- Gross Settlement Amount Input -->
                    <div>
                        <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Gross Settlement Amount (₹) *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-gray">₹</span>
                            <input type="number" step="0.01" name="gross_amount" x-model="grossAmount" min="1" :max="walletBalance" required class="w-full pl-9 pr-4 py-3 border border-gray-lighter rounded-xl text-base font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                        <span class="text-[11px] text-gray mt-1 block">Maximum allowed: Available balance (₹<span x-text="Number(walletBalance).toFixed(2)"></span>)</span>
                    </div>

                    <!-- Real-Time Tax & Net Breakdown Calculator -->
                    <div class="p-4 bg-primary/5 rounded-2xl border border-primary/10 space-y-2">
                        <div class="flex justify-between text-xs text-gray font-bold">
                            <span>Gross Settlement:</span>
                            <span class="text-dark" x-text="'₹' + (parseFloat(grossAmount) || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-danger font-bold">
                            <span>TDS Deduction (<span x-text="isTdsApplicable ? tdsRate + '%' : '0% (Exempt)'"></span>):</span>
                            <span x-text="'- ₹' + tdsAmount.toFixed(2)"></span>
                        </div>
                        <div class="border-t border-primary/10 pt-2 flex justify-between text-sm font-extrabold text-dark">
                            <span>Net Disbursed to Bank:</span>
                            <span class="text-success text-base" x-text="'₹' + netPaid.toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Bank Account Selection -->
                    <div>
                        <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Disbursement Bank Account *</label>
                        <template x-if="bankAccounts.length > 0">
                            <select name="bank_account_id" class="w-full px-4 py-3 border border-gray-lighter rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <template x-for="bank in bankAccounts" :key="bank.id">
                                    <option :value="bank.id" x-text="bank.bank_name + ' - A/C: ' + bank.account_number + ' (IFSC: ' + bank.ifsc_code + ')' + (bank.is_default ? ' [Default]' : '')"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="bankAccounts.length === 0">
                            <div class="space-y-3">
                                <span class="text-xs text-danger font-bold block">No pre-verified bank found. Enter manual transfer details:</span>
                                <input type="text" name="custom_bank_name" placeholder="Bank Name" class="w-full px-3 py-2 border rounded-xl text-xs">
                                <input type="text" name="custom_account_number" placeholder="Account Number" class="w-full px-3 py-2 border rounded-xl text-xs font-mono">
                                <input type="text" name="custom_ifsc" placeholder="IFSC Code" class="w-full px-3 py-2 border rounded-xl text-xs font-mono">
                            </div>
                        </template>
                    </div>

                    <!-- Payment Mode & UTR -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Payment Mode *</label>
                            <select name="payment_mode" required class="w-full px-4 py-3 border border-gray-lighter rounded-xl text-sm font-medium">
                                <option value="IMPS">IMPS (Instant)</option>
                                <option value="NEFT" selected>NEFT</option>
                                <option value="RTGS">RTGS</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">UTR / Reference No.</label>
                            <input type="text" name="utr_number" placeholder="e.g. UTR123456789" class="w-full px-4 py-3 border border-gray-lighter rounded-xl text-sm font-mono">
                        </div>
                    </div>

                    <!-- Payment Date & Receipt Attachment -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Payment Date *</label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full px-4 py-3 border border-gray-lighter rounded-xl text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Payment Proof (Optional)</label>
                            <input type="file" name="receipt_proof" accept="application/pdf,image/*" class="w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-light file:text-dark hover:file:bg-gray-200">
                        </div>
                    </div>

                    <!-- Remarks / Notes -->
                    <div>
                        <label class="block text-xs font-bold text-gray uppercase tracking-wider mb-2">Settlement Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="e.g. Monthly settlement for August 2026" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-sm">
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-lighter">
                        <button type="button" @click="payoutModal = false" class="px-5 py-2.5 border border-gray-lighter rounded-xl text-sm font-bold text-gray hover:bg-light">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-xl text-sm font-bold shadow-md hover:bg-primary-dark transition-all">Disburse & Deduct Wallet</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
