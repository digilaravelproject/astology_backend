@extends('admin.layouts.app')

@section('content')
<div x-data="{ activeReport: 'financial', showFilterModal: false }">
    <!-- Page Header & Period Selector -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Intelligence Center</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Synthesizing live platform telemetry into actionable strategic insights.</p>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <!-- Dynamic Period Dropdown / Filter -->
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
                <select name="period" onchange="this.form.submit()" class="px-5 py-3.5 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:border-primary/40 focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer shadow-sm">
                    <option value="last_30_days" {{ $period === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="last_7_days" {{ $period === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>

            <!-- Report Category Switcher -->
            <div class="flex items-center bg-dark rounded-2xl p-1 shadow-xl shadow-dark/20">
                <button @click="activeReport = 'financial'" :class="activeReport === 'financial' ? 'bg-primary text-white shadow-lg' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Financials</button>
                <button @click="activeReport = 'operations'" :class="activeReport === 'operations' ? 'bg-primary text-white shadow-lg' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Operations</button>
                <button @click="activeReport = 'growth'" :class="activeReport === 'growth' ? 'bg-primary text-white shadow-lg' : 'text-gray-light hover:text-white'" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Growth</button>
            </div>
        </div>
    </div>

    <!-- Cluster Analytics (Dynamic Top KPIs) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-10">
        <!-- Net GPV -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Net Platform GPV</div>
            <div class="text-2xl font-black text-dark tracking-tighter">₹ {{ number_format($netGPV, 2) }}</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">
                <i class="fas fa-arrow-trend-up mr-0.5"></i> {{ $periodLabel }}
            </div>
        </div>

        <!-- Avg Basket -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Avg. Basket / Order</div>
            <div class="text-2xl font-black text-dark tracking-tighter">₹ {{ number_format($avgBasket, 2) }}</div>
            <div class="text-[8px] font-bold text-primary uppercase mt-1">Telemetry Avg</div>
        </div>

        <!-- Active Users -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Active Consumers</div>
            <div class="text-2xl font-black text-dark tracking-tighter">{{ number_format($activeUsersCount) }}</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">{{ number_format($totalUsers) }} Total Users</div>
        </div>

        <!-- Churn Rate -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Inactivity / Churn</div>
            <div class="text-2xl font-black text-dark tracking-tighter">{{ $churnRate }}%</div>
            <div class="text-[8px] font-bold {{ $churnRate > 30 ? 'text-danger' : 'text-info' }} uppercase mt-1">30-Day Delta</div>
        </div>

        <!-- Astrologer Load -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Astro Load</div>
            <div class="text-2xl font-black text-dark tracking-tighter">{{ $astroLoadPercentage }}%</div>
            <div class="text-[8px] font-bold {{ $astroLoadPercentage > 60 ? 'text-warning' : 'text-success' }} uppercase mt-1">
                {{ $astroLoadPercentage > 60 ? 'High Demand' : 'Normal Load' }}
            </div>
        </div>

        <!-- Rating Avg -->
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Rating Avg</div>
            <div class="text-2xl font-black text-dark tracking-tighter flex items-center justify-center gap-1">
                <i class="fas fa-star text-primary text-lg"></i> {{ $ratingAvg }}
            </div>
            <div class="text-[8px] font-bold text-success uppercase mt-1">Verified Reviews</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 1: FINANCIAL PERFORMANCE -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'financial'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <!-- Revenue Distribution -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Revenue Distribution</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Inter-segment earning breakdown ({{ $periodLabel }})</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray"><i class="fas fa-coins text-primary"></i></div>
                </div>
                <div class="space-y-6">
                    <!-- Live Consultation (Chat + Call) -->
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-primary/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-lg"><i class="fas fa-comments"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Live Consultations (Chat & Call)</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">{{ $liveConsultationPercent }}% of Consultation GPV</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ {{ number_format($totalConsultationRevenue, 2) }}</div>
                            <div class="text-[9px] font-black text-success uppercase tracking-widest">
                                Chat: ₹{{ number_format($chatConsultationSpend, 0) }} | Call: ₹{{ number_format($callConsultationSpend, 0) }}
                            </div>
                        </div>
                    </div>

                    <!-- Prepaid Consultation Packages -->
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-success/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-success/10 rounded-2xl flex items-center justify-center text-success text-lg"><i class="fas fa-box-open"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Prepaid Consultation Packages</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">{{ $packageRevenuePercent }}% of Total Platform GPV</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ {{ number_format($packageSalesRevenue, 2) }}</div>
                            <div class="text-[9px] font-black text-success uppercase tracking-widest">Active Hybrid Packs</div>
                        </div>
                    </div>

                    <!-- Wallet Topups & Deposits -->
                    <div class="p-6 bg-light/30 rounded-3xl border border-gray-lighter flex items-center justify-between group hover:border-info/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-info/10 rounded-2xl flex items-center justify-center text-info text-lg"><i class="fas fa-wallet"></i></div>
                            <div>
                                <div class="text-sm font-black text-dark">Direct Wallet Deposits</div>
                                <div class="text-[9px] font-bold text-gray uppercase tracking-widest mt-1">Payment Gateway Inflow</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-dark">₹ {{ number_format($netGPV, 2) }}</div>
                            <div class="text-[9px] font-black text-info uppercase tracking-widest">Total Inflow Value</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settlement Pipeline (Real Astrologer Payouts) -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Settlement Pipeline</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Astrologer payout & withdrawal tracking</p>
                    </div>
                    <a href="{{ route('admin.astrologers.payouts.index') }}" class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray hover:text-dark hover:bg-light/80 transition-all">
                        <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Partner Astrologer</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-center">Amount</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @if($settlements->isNotEmpty())
                                @foreach($settlements as $payout)
                                <tr class="group hover:bg-light/30 transition-all">
                                    <td class="py-4">
                                        <div class="text-xs font-black text-dark">{{ $payout->astrologer?->user?->name ?? 'Astrologer #' . $payout->astrologer_id }}</div>
                                        <div class="text-[8px] font-bold text-gray uppercase mt-0.5">{{ $payout->payout_number ?? 'PO-' . $payout->id }} • {{ $payout->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="text-xs font-black text-dark">₹ {{ number_format($payout->gross_amount, 2) }}</div>
                                    </td>
                                    <td class="py-4 text-right px-2">
                                        @if($payout->status === 'processed' || $payout->status === 'approved')
                                            <span class="text-[8px] font-black uppercase px-2.5 py-1 bg-success/10 text-success rounded-lg border border-success/20">Settled</span>
                                        @elseif($payout->status === 'pending')
                                            <span class="text-[8px] font-black uppercase px-2.5 py-1 bg-warning/10 text-warning rounded-lg border border-warning/20">Pending</span>
                                        @else
                                            <span class="text-[8px] font-black uppercase px-2.5 py-1 bg-danger/10 text-danger rounded-lg border border-danger/20">{{ ucfirst($payout->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @elseif($topEarners->isNotEmpty())
                                @foreach($topEarners as $astro)
                                <tr class="group hover:bg-light/30 transition-all">
                                    <td class="py-4">
                                        <div class="text-xs font-black text-dark">{{ $astro->user?->name ?? 'Astro #' . $astro->id }}</div>
                                        <div class="text-[8px] font-bold text-gray uppercase mt-0.5">UID: AST-{{ $astro->id }} • {{ $astro->phone_number ?? 'Verified' }}</div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="text-xs font-black text-dark">₹ {{ number_format($astro->wallet_balance ?? 0, 2) }}</div>
                                    </td>
                                    <td class="py-4 text-right px-2">
                                        <span class="text-[8px] font-black uppercase px-2.5 py-1 bg-info/10 text-info rounded-lg border border-info/20">Accruing</span>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-xs text-gray italic">No payout records found for this period.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('admin.astrologers.payouts.index') }}" class="block w-full mt-6 py-4 border-2 border-dashed border-gray-lighter text-dark text-[10px] font-black uppercase text-center rounded-[24px] hover:border-primary/30 hover:bg-primary/5 transition-all">
                    Review All Settlements & Payouts <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 2: OPERATIONAL HEALTH -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'operations'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Real-time Queue Depth -->
            <div class="lg:col-span-1 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h3 class="text-xl font-black text-dark uppercase tracking-tighter mb-8 italic">Astro Load Pressure</h3>
                <div class="relative w-48 h-48 mx-auto mb-8">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="14" fill="transparent" class="text-light" />
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="14" fill="transparent" stroke-dasharray="552.92" stroke-dashoffset="{{ 552.92 - (552.92 * $astroLoadPercentage / 100) }}" class="text-primary" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-4xl font-black text-dark tracking-tighter">{{ $astroLoadPercentage }}%</div>
                        <div class="text-[8px] font-black text-gray uppercase tracking-widest mt-1">Utilization</div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Active Live Sessions</span>
                        <span class="text-dark font-black text-sm">{{ $activeSessionsCount }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Average Review Rating</span>
                        <span class="text-primary font-black text-sm">{{ $ratingAvg }} ★</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black text-gray uppercase">
                        <span>Missed/Drop Rate</span>
                        <span class="text-danger font-black text-sm">{{ $dropRate }}%</span>
                    </div>
                </div>
            </div>

            <!-- Consultation Logistics Log (Real Chats & Calls) -->
            <div class="lg:col-span-2 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Live Logistics Log</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Real-time session packets and telemetry</p>
                    </div>
                    <a href="{{ route('admin.astrologers.live') }}" class="px-4 py-2 bg-light rounded-xl text-[10px] font-black uppercase text-dark hover:bg-primary hover:text-white transition-all">
                        <i class="fas fa-tower-broadcast text-primary mr-1"></i> Live Monitor
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">Session</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-left">User & Astrologer</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-center">Duration</th>
                                <th class="pb-4 text-[9px] font-black text-gray uppercase tracking-widest text-right">Cost / Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @if($logistics->isNotEmpty())
                                @foreach($logistics as $log)
                                <tr class="group hover:bg-light/30 transition-all">
                                    <td class="py-4">
                                        <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg {{ $log['type'] === 'Call' ? 'bg-info/10 text-info border border-info/20' : 'bg-primary/10 text-primary border border-primary/20' }}">
                                            {{ $log['id'] }}
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <div class="text-[11px] font-black text-dark">{{ $log['user'] }} <i class="fas fa-arrows-alt-h mx-1.5 text-gray-lighter"></i> {{ $log['astro'] }}</div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="text-[10px] font-bold text-gray-light uppercase">{{ $log['duration'] }}</div>
                                    </td>
                                    <td class="py-4 text-right">
                                        <div class="text-xs font-black text-dark">{{ $log['cost'] }}</div>
                                        <div class="text-[8px] text-gray mt-0.5">{{ $log['date'] }}</div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-xs text-gray italic">No recent consultation sessions recorded.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 3: GROWTH ECOSYSTEM -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'growth'" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- Acquisition Velocity (Monthly Chart) -->
            <div class="lg:col-span-3 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-xl font-black text-dark uppercase tracking-tighter">Ecosystem Expansion</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Monthly consumer onboarding trajectory (Last 12 Months)</p>
                    </div>
                    <span class="px-4 py-2 bg-light rounded-xl text-[10px] font-black uppercase text-dark">
                        {{ number_format($totalUsers) }} Total Consumers
                    </span>
                </div>
                
                <!-- Dynamic Bar Chart -->
                <div class="flex items-end gap-3 h-48 mb-8 pt-6">
                    @foreach($growthMonths as $gm)
                    <div class="flex-1 flex flex-col items-center gap-2 group relative">
                        <!-- Tooltip -->
                        <div class="absolute -top-7 bg-dark text-white text-[8px] font-black px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-all pointer-events-none whitespace-nowrap shadow-lg">
                            {{ $gm['count'] }} Registrations
                        </div>
                        <div class="w-full bg-primary/20 group-hover:bg-primary transition-all rounded-t-xl" style="height: {{ $gm['height'] }}%"></div>
                        <span class="text-[8px] font-black text-gray uppercase">{{ $gm['label'] }}</span>
                    </div>
                    @endforeach
                </div>
                
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-gray-lighter">
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Conversion Alpha</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">{{ $conversionRate }}%</div>
                        <div class="text-[8px] font-black text-success uppercase mt-1">Consultation Paid Rate</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Active Consumer Base</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">{{ number_format($activeUsersCount) }}</div>
                        <div class="text-[8px] font-black text-info uppercase mt-1">In Selected Period</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1">Referral Velocity</div>
                        <div class="text-2xl font-black text-dark tracking-tighter">{{ $referralVelocity }}%</div>
                        <div class="text-[8px] font-black text-success uppercase mt-1">Referred Growth</div>
                    </div>
                </div>
            </div>

            <!-- Inflow Tech & Telemetry Breakdown -->
            <div class="lg:col-span-1 bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-xl font-black text-dark uppercase tracking-tighter mb-8">Platform Inflow</h3>
                    <div class="space-y-8">
                        <div>
                            <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                                <span>Mobile Android App</span>
                                <span class="text-dark font-black">74%</span>
                            </div>
                            <div class="h-2.5 w-full bg-light rounded-full overflow-hidden">
                                <div class="h-full bg-primary w-[74%] rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                                <span>Mobile iOS App</span>
                                <span class="text-dark font-black">18%</span>
                            </div>
                            <div class="h-2.5 w-full bg-light rounded-full overflow-hidden">
                                <div class="h-full bg-success w-[18%] rounded-full"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-[10px] font-black text-gray uppercase mb-2">
                                <span>Web & Portal Intake</span>
                                <span class="text-dark font-black">8%</span>
                            </div>
                            <div class="h-2.5 w-full bg-light rounded-full overflow-hidden">
                                <div class="h-full bg-info w-[8%] rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-lighter">
                    <a href="{{ route('admin.astrologers.performance') }}" class="w-full py-4 bg-light text-dark text-[10px] font-black uppercase rounded-2xl hover:bg-dark hover:text-white transition-all shadow-sm flex items-center justify-center gap-2">
                        <i class="fas fa-chart-line text-primary"></i> Astrologer Performance Hub
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
