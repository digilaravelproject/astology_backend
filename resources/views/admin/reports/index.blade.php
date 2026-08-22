@extends('admin.layouts.app')

@section('content')
<div x-data="{ activeReport: 'financial', showFilterModal: false }" class="space-y-8 w-full max-w-full pb-12">
    <!-- Page Header & Period Selector (Fully Responsive) -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 border-b border-gray-lighter pb-6 sm:pb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Intelligence Center</h1>
            <p class="text-xs sm:text-sm text-gray font-medium mt-1 italic">Synthesizing live platform telemetry into actionable strategic insights.</p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 shrink-0">
            <!-- Dynamic Period Dropdown / Filter -->
            <form method="GET" action="{{ route('admin.reports.index') }}" class="w-full sm:w-auto">
                <select name="period" onchange="this.form.submit()" class="w-full sm:w-auto px-4 sm:px-5 py-3 bg-white border-2 border-gray-lighter text-dark text-xs font-black uppercase rounded-2xl hover:border-primary/40 focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer shadow-sm">
                    <option value="last_30_days" {{ $period === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="last_7_days" {{ $period === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>

            <!-- Report Category Switcher -->
            <div class="flex items-center bg-dark rounded-2xl p-1 shadow-xl shadow-dark/20 overflow-x-auto scrollbar-none">
                <button @click="activeReport = 'financial'" :class="activeReport === 'financial' ? 'bg-primary text-white shadow-lg font-black' : 'text-gray-light hover:text-white font-bold'" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Financials</button>
                <button @click="activeReport = 'operations'" :class="activeReport === 'operations' ? 'bg-primary text-white shadow-lg font-black' : 'text-gray-light hover:text-white font-bold'" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Operations</button>
                <button @click="activeReport = 'growth'" :class="activeReport === 'growth' ? 'bg-primary text-white shadow-lg font-black' : 'text-gray-light hover:text-white font-bold'" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Growth</button>
            </div>
        </div>
    </div>

    <!-- Cluster Analytics (Responsive Top KPIs: 2-col on mobile, 3-col on tablet, 6-col on desktop) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-6">
        <!-- Net GPV -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Platform GPV</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter truncate">₹ {{ number_format($netGPV, 2) }}</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1 truncate">
                <i class="fas fa-arrow-trend-up mr-0.5"></i> {{ $periodLabel }}
            </div>
        </div>

        <!-- Avg Basket -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Avg. Basket</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter truncate">₹ {{ number_format($avgBasket, 2) }}</div>
            <div class="text-[8px] font-bold text-primary uppercase mt-1 truncate">Telemetry Avg</div>
        </div>

        <!-- Active Users -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Active Users</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter">{{ number_format($activeUsersCount) }}</div>
            <div class="text-[8px] font-bold text-success uppercase mt-1 truncate">{{ number_format($totalUsers) }} Total</div>
        </div>

        <!-- Churn Rate -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Inactivity</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter">{{ $churnRate }}%</div>
            <div class="text-[8px] font-bold {{ $churnRate > 30 ? 'text-danger' : 'text-info' }} uppercase mt-1">30-Day Delta</div>
        </div>

        <!-- Astrologer Load -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Astro Load</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter">{{ $astroLoadPercentage }}%</div>
            <div class="text-[8px] font-bold {{ $astroLoadPercentage > 60 ? 'text-warning' : 'text-success' }} uppercase mt-1 truncate">
                {{ $astroLoadPercentage > 60 ? 'High Demand' : 'Normal Load' }}
            </div>
        </div>

        <!-- Rating Avg -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl hover:border-primary/20 transition-all text-center">
            <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-1 sm:mb-2">Rating Avg</div>
            <div class="text-lg sm:text-2xl font-black text-dark tracking-tighter flex items-center justify-center gap-1">
                <i class="fas fa-star text-primary text-sm sm:text-base"></i> {{ $ratingAvg }}
            </div>
            <div class="text-[8px] font-bold text-success uppercase mt-1 truncate">Verified Reviews</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 1: FINANCIAL PERFORMANCE -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'financial'" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            <!-- Revenue Distribution -->
            <div class="bg-white p-5 sm:p-8 lg:p-10 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter">Revenue Distribution</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Inter-segment earning breakdown ({{ $periodLabel }})</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray shrink-0"><i class="fas fa-coins text-primary"></i></div>
                </div>
                <div class="space-y-4 sm:space-y-6">
                    <!-- Live Consultation (Chat + Call) -->
                    <div class="p-4 sm:p-6 bg-light/30 rounded-2xl sm:rounded-3xl border border-gray-lighter flex items-center justify-between gap-3 group hover:border-primary/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-primary/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-primary text-base sm:text-lg shrink-0"><i class="fas fa-comments"></i></div>
                            <div class="min-w-0">
                                <div class="text-xs sm:text-sm font-black text-dark truncate">Live Consultations (Chat & Call)</div>
                                <div class="text-[8px] sm:text-[9px] font-bold text-gray uppercase tracking-widest mt-0.5 truncate">{{ $liveConsultationPercent }}% of Consultation GPV</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm sm:text-lg font-black text-dark">₹ {{ number_format($totalConsultationRevenue, 2) }}</div>
                            <div class="text-[8px] sm:text-[9px] font-black text-success uppercase tracking-widest">
                                Chat: ₹{{ number_format($chatConsultationSpend, 0) }} | Call: ₹{{ number_format($callConsultationSpend, 0) }}
                            </div>
                        </div>
                    </div>

                    <!-- Prepaid Consultation Packages -->
                    <div class="p-4 sm:p-6 bg-light/30 rounded-2xl sm:rounded-3xl border border-gray-lighter flex items-center justify-between gap-3 group hover:border-success/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-success/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-success text-base sm:text-lg shrink-0"><i class="fas fa-box-open"></i></div>
                            <div class="min-w-0">
                                <div class="text-xs sm:text-sm font-black text-dark truncate">Prepaid Consultation Packages</div>
                                <div class="text-[8px] sm:text-[9px] font-bold text-gray uppercase tracking-widest mt-0.5 truncate">{{ $packageRevenuePercent }}% of Total Platform GPV</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm sm:text-lg font-black text-dark">₹ {{ number_format($packageSalesRevenue, 2) }}</div>
                            <div class="text-[8px] sm:text-[9px] font-black text-success uppercase tracking-widest">Active Hybrid Packs</div>
                        </div>
                    </div>

                    <!-- Wallet Topups & Deposits -->
                    <div class="p-4 sm:p-6 bg-light/30 rounded-2xl sm:rounded-3xl border border-gray-lighter flex items-center justify-between gap-3 group hover:border-info/30 hover:bg-light/60 transition-all">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-info/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-info text-base sm:text-lg shrink-0"><i class="fas fa-wallet"></i></div>
                            <div class="min-w-0">
                                <div class="text-xs sm:text-sm font-black text-dark truncate">Direct Wallet Deposits</div>
                                <div class="text-[8px] sm:text-[9px] font-bold text-gray uppercase tracking-widest mt-0.5 truncate">Payment Inflow</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm sm:text-lg font-black text-dark">₹ {{ number_format($netGPV, 2) }}</div>
                            <div class="text-[8px] sm:text-[9px] font-black text-info uppercase tracking-widest">Total Inflow Value</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settlement Pipeline (Real Astrologer Payouts) -->
            <div class="bg-white p-5 sm:p-8 lg:p-10 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter">Settlement Pipeline</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Astrologer payout & withdrawal tracking</p>
                    </div>
                    <a href="{{ route('admin.astrologer-payouts.index') }}" class="w-10 h-10 rounded-xl bg-light flex items-center justify-center text-gray hover:text-dark hover:bg-light/80 transition-all shrink-0">
                        <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                </div>
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="w-full min-w-[320px]">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-left">Partner Astrologer</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-center">Amount</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @if($settlements->isNotEmpty())
                                @foreach($settlements as $payout)
                                <tr class="group hover:bg-light/30 transition-all">
                                    <td class="py-3 sm:py-4">
                                        <div class="text-xs font-black text-dark">{{ $payout->astrologer?->user?->name ?? 'Astrologer #' . $payout->astrologer_id }}</div>
                                        <div class="text-[8px] font-bold text-gray uppercase mt-0.5">{{ $payout->payout_number ?? 'PO-' . $payout->id }} • {{ $payout->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="py-3 sm:py-4 text-center whitespace-nowrap">
                                        <div class="text-xs font-black text-dark">₹ {{ number_format($payout->gross_amount, 2) }}</div>
                                    </td>
                                    <td class="py-3 sm:py-4 text-right whitespace-nowrap">
                                        @if($payout->status === 'processed' || $payout->status === 'approved')
                                            <span class="text-[8px] font-black uppercase px-2 py-0.5 bg-success/10 text-success rounded-lg border border-success/20">Settled</span>
                                        @elseif($payout->status === 'pending')
                                            <span class="text-[8px] font-black uppercase px-2 py-0.5 bg-warning/10 text-warning rounded-lg border border-warning/20">Pending</span>
                                        @else
                                            <span class="text-[8px] font-black uppercase px-2 py-0.5 bg-danger/10 text-danger rounded-lg border border-danger/20">{{ ucfirst($payout->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @elseif($topEarners->isNotEmpty())
                                @foreach($topEarners as $astro)
                                <tr class="group hover:bg-light/30 transition-all">
                                    <td class="py-3 sm:py-4">
                                        <div class="text-xs font-black text-dark">{{ $astro->user?->name ?? 'Astro #' . $astro->id }}</div>
                                        <div class="text-[8px] font-bold text-gray uppercase mt-0.5">UID: AST-{{ $astro->id }} • Verified</div>
                                    </td>
                                    <td class="py-3 sm:py-4 text-center whitespace-nowrap">
                                        <div class="text-xs font-black text-dark">₹ {{ number_format($astro->wallet_balance ?? 0, 2) }}</div>
                                    </td>
                                    <td class="py-3 sm:py-4 text-right whitespace-nowrap">
                                        <span class="text-[8px] font-black uppercase px-2 py-0.5 bg-info/10 text-info rounded-lg border border-info/20">Accruing</span>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-xs text-gray italic">No payout records found for this period.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('admin.astrologer-payouts.index') }}" class="block w-full mt-5 sm:mt-6 py-3.5 border-2 border-dashed border-gray-lighter text-dark text-[10px] font-black uppercase text-center rounded-2xl hover:border-primary/30 hover:bg-primary/5 transition-all">
                    Review All Settlements & Payouts <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 2: OPERATIONAL HEALTH -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'operations'" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <!-- Real-time Queue Depth -->
            <div class="lg:col-span-1 bg-white p-6 sm:p-8 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm">
                <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter mb-6 sm:mb-8 italic">Astro Load Pressure</h3>
                <div class="relative w-40 h-40 sm:w-48 sm:h-48 mx-auto mb-6 sm:mb-8">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" class="text-light" />
                        <circle cx="96" cy="96" r="80" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="502.65" stroke-dashoffset="{{ 502.65 - (502.65 * $astroLoadPercentage / 100) }}" class="text-primary" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-3xl sm:text-4xl font-black text-dark tracking-tighter">{{ $astroLoadPercentage }}%</div>
                        <div class="text-[8px] font-black text-gray uppercase tracking-widest mt-1">Utilization</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3.5 bg-light/30 rounded-2xl border border-gray-lighter">
                        <span class="text-xs font-bold text-gray">Active Live Channels</span>
                        <span class="text-xs font-black text-dark">{{ $activeSessionsCount }} Streams</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 bg-light/30 rounded-2xl border border-gray-lighter">
                        <span class="text-xs font-bold text-gray">Drop / Miss Rate</span>
                        <span class="text-xs font-black {{ $dropRate > 5 ? 'text-danger' : 'text-success' }}">{{ $dropRate }}%</span>
                    </div>
                </div>
            </div>

            <!-- Real-Time Session Logistics Feed -->
            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter">Live Session Telemetry</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Real-time chat & call execution records</p>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="text-[10px] font-black text-primary uppercase hover:underline">View All &rarr;</a>
                </div>
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="w-full min-w-[550px]">
                        <thead class="border-b border-gray-lighter">
                            <tr>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-left">Session ID</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-left">Consumer</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-left">Astrologer</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-center">Duration</th>
                                <th class="pb-3 text-[9px] font-black text-gray uppercase tracking-widest text-right">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-lighter">
                            @forelse($logistics as $session)
                            <tr class="group hover:bg-light/30 transition-all">
                                <td class="py-3 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs font-black text-primary">{{ $session['id'] }}</div>
                                    <div class="text-[8px] font-bold text-gray uppercase">{{ $session['type'] }} • {{ $session['date'] }}</div>
                                </td>
                                <td class="py-3 sm:py-4">
                                    <div class="text-xs font-bold text-dark">{{ $session['user'] }}</div>
                                </td>
                                <td class="py-3 sm:py-4">
                                    <div class="text-xs font-bold text-dark">{{ $session['astro'] }}</div>
                                </td>
                                <td class="py-3 sm:py-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 bg-light rounded-lg text-[9px] font-bold text-gray">{{ $session['duration'] }}</span>
                                </td>
                                <td class="py-3 sm:py-4 text-right whitespace-nowrap">
                                    <div class="text-xs font-black text-dark">{{ $session['cost'] }}</div>
                                    <div class="text-[8px] font-bold text-success uppercase">{{ $session['status'] }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-gray italic">No live sessions recorded in this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REPORT CLUSTER 3: GROWTH & COHORTS -->
    <!-- ========================================================================= -->
    <div x-show="activeReport === 'growth'" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <!-- User Acquisition Monthly Bars -->
            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm">
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <div>
                        <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter">User Acquisition Trajectory</h3>
                        <p class="text-[10px] text-gray font-bold uppercase tracking-widest mt-1">Monthly registration trends (Last 12 Months)</p>
                    </div>
                </div>
                <div class="h-44 sm:h-52 flex items-end justify-between gap-1.5 sm:gap-3 pt-6 border-b border-gray-lighter overflow-x-auto pb-2">
                    @foreach($growthMonths as $bar)
                    <div class="flex-1 min-w-[20px] flex flex-col items-center gap-2 group">
                        <div class="text-[8px] font-black text-primary opacity-0 group-hover:opacity-100 transition-all whitespace-nowrap">{{ $bar['count'] }}</div>
                        <div class="w-full bg-light group-hover:bg-primary/20 rounded-xl transition-all relative overflow-hidden flex items-end" style="height: 120px;">
                            <div class="w-full bg-primary rounded-xl transition-all" style="height: {{ $bar['height'] }}%;"></div>
                        </div>
                        <span class="text-[9px] font-black text-gray uppercase">{{ $bar['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Platform Health Gauges -->
            <div class="lg:col-span-1 bg-white p-6 sm:p-8 rounded-2xl sm:rounded-[40px] border border-gray-lighter shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-lg sm:text-xl font-black text-dark uppercase tracking-tighter mb-4">Cohort Performance</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-light/30 rounded-2xl border border-gray-lighter">
                            <div class="text-xs font-bold text-gray">Consultation Conversion Rate</div>
                            <div class="text-2xl font-black text-dark mt-1">{{ $conversionRate }}%</div>
                            <div class="text-[9px] text-success font-bold mt-0.5">Users who booked at least 1 session</div>
                        </div>
                        <div class="p-4 bg-light/30 rounded-2xl border border-gray-lighter">
                            <div class="text-xs font-bold text-gray">Repeat Client Velocity</div>
                            <div class="text-2xl font-black text-dark mt-1">{{ $referralVelocity }}%</div>
                            <div class="text-[9px] text-primary font-bold mt-0.5">Users with multi-session consults</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-lighter">
                    <a href="{{ route('admin.users.index') }}" class="block w-full py-3 bg-dark text-white rounded-xl text-center text-xs font-black uppercase tracking-wider hover:bg-black transition-all">
                        View User Roster &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
