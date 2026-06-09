@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Price Increase Requests</h1>
            <p class="text-sm text-gray font-medium">Review and approve/reject astrologer price increase requests.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-primary/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Total Requests</div>
            <div class="text-3xl font-black text-dark">{{ $requests->total() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-info font-black text-[9px] uppercase">
                <i class="fas fa-clock"></i> All Time
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-warning/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Pending</div>
            <div class="text-3xl font-black text-warning">{{ \App\Models\PriceIncreaseRequest::where('status', 'pending')->count() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-warning font-black text-[9px] uppercase">
                <i class="fas fa-hourglass-half"></i> Awaiting Review
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-success/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Approved</div>
            <div class="text-3xl font-black text-success">{{ \App\Models\PriceIncreaseRequest::where('status', 'approved')->count() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-success font-black text-[9px] uppercase">
                <i class="fas fa-check-circle"></i> Completed
            </div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm group hover:shadow-xl transition-all relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 w-16 h-16 bg-danger/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Rejected</div>
            <div class="text-3xl font-black text-danger">{{ \App\Models\PriceIncreaseRequest::where('status', 'rejected')->count() }}</div>
            <div class="mt-2 flex items-center gap-1.5 text-danger font-black text-[9px] uppercase">
                <i class="fas fa-times-circle"></i> Declined
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-[28px] border border-gray-lighter shadow-sm mb-8 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Search</label>
            <form method="GET" action="{{ route('admin.price-increase-requests.index') }}" class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray transition-colors group-focus-within:text-dark"></i>
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by astrologer name or email..." class="w-full bg-light/50 border border-gray-lighter pl-11 pr-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all focus:bg-white focus:shadow-sm">
            </form>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
            <form method="GET" action="{{ route('admin.price-increase-requests.index') }}">
                <select name="status" onchange="this.form.submit()" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="" {{ request('status') === null ? 'selected' : '' }}>All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </form>
        </div>
        <div class="w-full sm:w-48">
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Price Type</label>
            <form method="GET" action="{{ route('admin.price-increase-requests.index') }}">
                <select name="price_type" onchange="this.form.submit()" class="w-full bg-light/50 border border-gray-lighter px-4 py-3.5 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all appearance-none cursor-pointer">
                    <option value="" {{ request('price_type') === null ? 'selected' : '' }}>All</option>
                    <option value="call" {{ request('price_type') === 'call' ? 'selected' : '' }}>Call</option>
                    <option value="chat" {{ request('price_type') === 'chat' ? 'selected' : '' }}>Chat</option>
                </select>
            </form>
        </div>
        <a href="{{ route('admin.price-increase-requests.index') }}" class="bg-dark text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-dark/10 h-[52px] flex items-center">Reset</a>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">#</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Astrologer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Level</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Type</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Old → New</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Increase</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Date</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($requests as $req)
                        <tr class="hover:bg-light/30 transition-all group">
                            <td class="px-6 py-5 text-sm font-black text-dark">R-{{ $req->id }}</td>
                            <td class="px-6 py-5">
                                <div class="text-sm font-black text-dark">{{ $req->astrologer?->user?->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray mt-1">{{ $req->astrologer?->user?->email ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 bg-primary/10 text-primary text-[9px] font-black uppercase rounded-full border border-primary/20">{{ $req->level?->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-5">
                                @if($req->price_type === 'call')
                                    <span class="px-3 py-1 bg-info/10 text-info text-[9px] font-black uppercase rounded-full border border-info/20">Call</span>
                                @else
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Chat</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm font-bold">
                                <span class="text-gray">${{ number_format($req->old_price, 2) }}</span>
                                <i class="fas fa-arrow-right text-xs mx-1 text-gray"></i>
                                <span class="text-dark">${{ number_format($req->new_price, 2) }}</span>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-success">+${{ number_format($req->increase_amount, 2) }}</td>
                            <td class="px-6 py-5">
                                @if($req->status === 'pending')
                                    <span class="px-3 py-1 bg-warning/10 text-warning text-[9px] font-black uppercase rounded-full border border-warning/20">Pending</span>
                                @elseif($req->status === 'approved')
                                    <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Approved</span>
                                @else
                                    <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20">Rejected</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-gray">{{ $req->created_at?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.price-increase-requests.show', $req->id) }}" class="w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-sm" title="View">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-gray">No price increase requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-6 border-t border-gray-lighter flex flex-col md:flex-row justify-between items-center gap-4 bg-light/20">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest">Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} requests</div>
            <div>
                {{ $requests->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
