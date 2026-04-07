@extends('admin.layouts.app')

@section('content')
<div x-data="{ orderModal: false, selectedOrder: {} }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1 text-center md:text-left">Order Management</h1>
            <p class="text-sm text-gray font-medium text-center md:text-left">Real-time audit log of all financial and professional interactions.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <button class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all flex items-center gap-2 text-xs">
                <i class="fas fa-file-export"></i> Global CSV Export
            </button>
            <a href="{{ route('admin.orders.create') }}" class="bg-primary text-white px-4 py-2.5 rounded-xl font-bold hover:bg-primary-dark transition-all flex items-center gap-2 text-xs shadow-lg shadow-primary/20">
                <i class="fas fa-plus"></i> Manual Order
            </a>
        </div>
    </div>

    <!-- Order Stats Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Total Volume</div>
            <div class="text-3xl font-black text-dark">{{ number_format($totalVolume) }}</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-success py-0.5 px-2 bg-success/10 rounded-full">+12%</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">from last week</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-success/5 rounded-full blur-xl group-hover:bg-success/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Today High</div>
            <div class="text-3xl font-black text-dark">₹{{ number_format($todayHigh, 0) }}</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-success py-0.5 px-2 bg-success/10 rounded-full">+₹0</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">vs yesterday</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-info/5 rounded-full blur-xl group-hover:bg-info/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Completion</div>
            <div class="text-3xl font-black text-dark">{{ $completionPercent }}%</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-info py-0.5 px-2 bg-info/10 rounded-full">Elite</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">platform health</span>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm hover:shadow-xl transition-all group overflow-hidden relative">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-danger/5 rounded-full blur-xl group-hover:bg-danger/20 transition-all"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Refund Req.</div>
            <div class="text-3xl font-black text-dark">{{ number_format($refundReq) }}</div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="text-[9px] font-black text-danger py-0.5 px-2 bg-danger/10 rounded-full">Action</span>
                <span class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">pending review</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Universal Search</label>
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input type="text" placeholder="Search ID, User, or Astrologer..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </div>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Service Type</label>
            <select class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                <option>All Services</option>
                <option>Voice Call</option>
                <option>Chat Session</option>
                <option>Video Call</option>
                <option>Report Generation</option>
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Log Status</label>
            <select class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                <option>All Status</option>
                <option>Completed</option>
                <option>Processing</option>
                <option>Cancelled</option>
                <option>Refunded</option>
            </select>
        </div>
        <button class="bg-dark text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 transform active:scale-95 h-[48px]">Apply</button>
    </div>

    <!-- Order Table -->
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Digital ID</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Customer Insight</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Astro Partner</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Session Logic</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Settlement</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Flow State</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($orders as $ord)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5">
                            <div class="text-[11px] font-black text-dark underline decoration-primary/20 decoration-2 underline-offset-4">{{ $ord['id'] }}</div>
                            <div class="text-[9px] font-bold text-gray uppercase mt-1">{{ $ord['date'] }}</div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-bold text-dark group-hover:text-primary transition-colors">{{ $ord['user'] }}</div>
                            <div class="text-[10px] text-gray-light font-mono">{{ $ord['user_mail'] }}</div>
                        </td>
                        <td class="px-6 py-5 text-sm font-black text-dark/70">{{ $ord['astro'] }}</td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                @if($ord['type'] == 'Call') <i class="fas fa-phone-alt text-[10px] text-info"></i>
                                @elseif($ord['type'] == 'Chat') <i class="fas fa-comment-dots text-[10px] text-primary"></i>
                                @elseif($ord['type'] == 'Video') <i class="fas fa-video text-[10px] text-danger"></i>
                                @else <i class="fas fa-file-invoice text-[10px] text-accent"></i> @endif
                                <span class="text-xs font-black text-dark">{{ $ord['type'] }}</span>
                            </div>
                            <div class="text-[9px] font-bold text-gray-light uppercase tracking-tighter">{{ $ord['duration'] }} duration</div>
                        </td>
                        <td class="px-6 py-5 text-sm font-black text-dark">{{ $ord['amount'] }}</td>
                        <td class="px-6 py-5">
                            @if($ord['status'] == 'Completed') <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20 shadow-xs">Settled</span>
                            @elseif($ord['status'] == 'Cancelled') <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20 shadow-xs">Aborted</span>
                            @elseif($ord['status'] == 'Refunded') <span class="px-3 py-1 bg-warning/10 text-warning text-[9px] font-black uppercase rounded-full border border-warning/20 shadow-xs">Reversed</span>
                            @else <span class="px-3 py-1 bg-info/10 text-info text-[9px] font-black uppercase rounded-full border border-info/20 shadow-xs animate-pulse">In Flow</span> @endif
                        </td>
                        <td class="px-6 py-5 text-right space-x-2">
                            <a href="{{ route('admin.orders.show', ['type' => strtolower($ord['type']), 'id' => explode('-', $ord['id'])[1]]) }}" class="inline-flex w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <form action="{{ route('admin.orders.destroy', ['type' => strtolower($ord['type']), 'id' => explode('-', $ord['id'])[1]]) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm" onclick="return confirm('Delete this order?');">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray text-xs">No orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-6 border-t border-gray-lighter flex justify-between items-center bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ number_format($orders->count()) }} orders</div>
            <div class="flex items-center gap-1.5">
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                <button class="w-10 h-10 rounded-xl bg-dark text-white font-black text-xs">1</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-dark font-black text-xs hover:bg-dark hover:text-white transition-all">2</button>
                <button class="w-10 h-10 rounded-xl bg-white border border-gray-lighter text-gray hover:text-dark hover:border-dark transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal (Receipt/Invoice View) -->
    <div x-show="orderModal" 
         class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[40px] shadow-[0_40px_80px_rgba(0,0,0,0.4)] overflow-hidden" @click.away="orderModal = false">
            <!-- Modal Header -->
            <div class="p-8 border-b border-gray-lighter bg-light/30 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-dark text-white rounded-2xl flex items-center justify-center text-xl font-black shadow-lg">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-dark uppercase tracking-tighter" x-text="'Invoice: ' + selectedOrder.id"></h3>
                        <p class="text-[10px] font-black text-gray uppercase tracking-widest mt-1" x-text="selectedOrder.date"></p>
                    </div>
                </div>
                <button @click="orderModal = false" class="w-12 h-12 bg-white hover:bg-gray-lighter text-gray rounded-2xl flex items-center justify-center transition-all shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-8">
                <!-- Receipt Body -->
                <div class="border-2 border-dashed border-gray-lighter rounded-[32px] p-8 bg-light/10 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>
                    
                    <!-- Transaction Header -->
                    <div class="flex justify-between items-start mb-8 border-b border-gray-lighter pb-6">
                        <div>
                            <div class="text-[9px] font-black text-gray uppercase mb-1">Customer</div>
                            <div class="text-lg font-black text-dark" x-text="selectedOrder.user"></div>
                            <div class="text-xs font-bold text-gray" x-text="selectedOrder.user_mail"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-[9px] font-black text-gray uppercase mb-1">Status</div>
                            <div class="text-sm font-black text-success" x-text="selectedOrder.status == 'Completed' ? 'Successfully Settled' : selectedOrder.status"></div>
                            <div class="text-[9px] font-bold text-primary uppercase mt-1">Transaction Verified</div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="space-y-4 mb-10">
                        <div class="flex justify-between items-center text-xs p-4 bg-white rounded-2xl border border-gray-lighter">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-light flex items-center justify-center"><i class="fas fa-leaf text-[10px] text-accent"></i></span>
                                <div>
                                    <div class="font-black text-dark" x-text="selectedOrder.type + ' Session'"></div>
                                    <div class="text-[9px] font-bold text-gray uppercase" x-text="'Partner: ' + selectedOrder.astro"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-dark" x-text="selectedOrder.amount"></div>
                                <div class="text-[9px] font-bold text-gray-light uppercase" x-text="selectedOrder.duration"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="space-y-3 border-t border-gray-lighter pt-6">
                        <div class="flex justify-between items-center text-[11px] font-bold text-gray uppercase">
                            <span>Platform Fee (10%)</span>
                            <span x-text="'₹' + (parseInt(selectedOrder.amount ? selectedOrder.amount.replace('₹','').replace(',','') : 0) * 0.1)"></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold text-gray uppercase">
                            <span>Service Tax (GST)</span>
                            <span>₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-lighter">
                            <span class="text-xs font-black text-dark uppercase tracking-widest">Total Settlement</span>
                            <span class="text-2xl font-black text-dark" x-text="selectedOrder.amount"></span>
                        </div>
                    </div>

                    <!-- Bottom Barcode Deco -->
                    <div class="mt-10 flex flex-col items-center opacity-30">
                        <i class="fas fa-barcode text-5xl mb-2"></i>
                        <div class="text-[10px] font-mono" x-text="'TRANS_ID_' + selectedOrder.id"></div>
                    </div>
                </div>
            </div>
            
            <div class="p-8 bg-light/30 border-t border-gray-lighter flex justify-end gap-4 overflow-hidden relative">
                <button class="px-8 py-4 bg-white border border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-gray-lighter transition-all font-mono">Download PDF</button>
                <button @click="orderModal = false" class="px-12 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 transform active:scale-95">Acknowledge</button>
            </div>
        </div>
    </div>
</div>
@endsection
