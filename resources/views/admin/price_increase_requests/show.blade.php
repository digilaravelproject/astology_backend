@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.price-increase-requests.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Requests
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Price Increase Request #{{ $request->id }}</h1>
            <p class="text-sm text-gray font-medium mt-2">{{ $request->created_at->format('M d, Y H:i A') }}</p>
        </div>
        <div class="flex gap-3">
            @if($request->status === 'pending')
                <form action="{{ route('admin.price-increase-requests.approve', $request->id) }}" method="POST" class="inline" id="approveForm">
                    @csrf
                    <button type="button" onclick="document.getElementById('approveRemark').classList.toggle('hidden'); this.classList.add('hidden')" class="px-8 py-4 bg-success text-white text-[11px] font-black uppercase rounded-2xl hover:bg-success/90 transition-all shadow-lg shadow-success/20">
                        <i class="fas fa-check mr-2"></i> Approve
                    </button>
                </form>
                <form action="{{ route('admin.price-increase-requests.reject', $request->id) }}" method="POST" class="inline" id="rejectForm">
                    @csrf
                    <button type="button" onclick="document.getElementById('rejectRemark').classList.toggle('hidden'); this.classList.add('hidden')" class="px-8 py-4 bg-danger text-white text-[11px] font-black uppercase rounded-2xl hover:bg-danger/90 transition-all shadow-lg shadow-danger/20">
                        <i class="fas fa-times mr-2"></i> Reject
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Approve Remark -->
    <div id="approveRemark" class="hidden mb-6 bg-success/5 border border-success/20 rounded-[32px] p-6">
        <form action="{{ route('admin.price-increase-requests.approve', $request->id) }}" method="POST">
            @csrf
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-3 block">Remark (Optional)</label>
            <textarea name="admin_remark" rows="2" class="w-full bg-white border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-success transition-all" placeholder="Optional remark for approval..."></textarea>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-success text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-success/90 transition-all shadow-lg shadow-success/20">Confirm Approve</button>
                <button type="button" onclick="document.getElementById('approveRemark').classList.add('hidden'); document.querySelector('[onclick*=\"approveRemark\"]')?.classList.remove('hidden')" class="bg-white border border-gray-lighter text-dark px-6 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Reject Remark -->
    <div id="rejectRemark" class="hidden mb-6 bg-danger/5 border border-danger/20 rounded-[32px] p-6">
        <form action="{{ route('admin.price-increase-requests.reject', $request->id) }}" method="POST">
            @csrf
            <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-3 block">Remark (Optional)</label>
            <textarea name="admin_remark" rows="2" class="w-full bg-white border border-gray-lighter rounded-2xl px-4 py-3.5 text-xs font-bold focus:outline-none focus:border-danger transition-all" placeholder="Optional remark for rejection..."></textarea>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-danger text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-danger/90 transition-all shadow-lg shadow-danger/20">Confirm Reject</button>
                <button type="button" onclick="document.getElementById('rejectRemark').classList.add('hidden'); document.querySelector('[onclick*=\"rejectRemark\"]')?.classList.remove('hidden')" class="bg-white border border-gray-lighter text-dark px-6 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all">Cancel</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 space-y-10">
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Request Details
                </h2>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Astrologer</div>
                            <div class="text-lg font-black text-dark">{{ $request->astrologer?->user?->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray mt-1">{{ $request->astrologer?->user?->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Level</div>
                            <span class="inline-block px-4 py-2 bg-primary/10 text-primary text-[10px] font-black uppercase rounded-full border border-primary/20">
                                {{ $request->level?->name ?? 'N/A' }} (Level {{ $request->level?->level_number ?? '-' }})
                            </span>
                        </div>
                    </div>

                    <div class="bg-light/30 p-8 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-6">Pricing Change</div>
                        <div class="flex items-center justify-center gap-6 flex-wrap">
                            <div class="text-center">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">Current Rate</div>
                                <div class="text-3xl font-black text-gray">${{ number_format($request->old_price, 2) }}</div>
                                <div class="text-[9px] font-bold text-gray mt-1 uppercase">{{ $request->price_type }} / min</div>
                            </div>
                            <div class="text-2xl text-gray">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="text-center">
                                <div class="text-[9px] font-black text-gray uppercase tracking-widest mb-2">New Rate</div>
                                <div class="text-3xl font-black text-success">${{ number_format($request->new_price, 2) }}</div>
                                <div class="text-[9px] font-bold text-gray mt-1 uppercase">{{ $request->price_type }} / min</div>
                            </div>
                        </div>
                        <div class="mt-6 text-center">
                            <span class="inline-block px-4 py-2 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                +${{ number_format($request->increase_amount, 2) }} increase
                            </span>
                        </div>
                    </div>

                    @if($request->admin_remark)
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Admin Remark</div>
                        <p class="text-dark font-medium">{{ $request->admin_remark }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Timeline
                </h2>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-clock text-xs"></i>
                        </div>
                        <div>
                            <div class="text-sm font-black text-dark">Request Submitted</div>
                            <div class="text-xs text-gray">{{ $request->created_at->format('M d, Y H:i A') }}</div>
                        </div>
                    </div>

                    @if($request->approved_at)
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-success/10 text-success flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-success">Approved</div>
                                <div class="text-xs text-gray">{{ $request->approved_at->format('M d, Y H:i A') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($request->rejected_at)
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-danger/10 text-danger flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-times text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-black text-danger">Rejected</div>
                                <div class="text-xs text-gray">{{ $request->rejected_at->format('M d, Y H:i A') }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-info-circle mr-2"></i> Status
                </h3>
                @if($request->status === 'pending')
                    <div class="p-4 bg-warning/10 border border-warning/20 rounded-2xl mb-4">
                        <p class="text-[10px] font-black text-warning uppercase tracking-widest">
                            <i class="fas fa-hourglass-half mr-2"></i> Pending Review
                        </p>
                    </div>
                @elseif($request->status === 'approved')
                    <div class="p-4 bg-success/10 border border-success/20 rounded-2xl mb-4">
                        <p class="text-[10px] font-black text-success uppercase tracking-widest">
                            <i class="fas fa-check-circle mr-2"></i> Approved
                        </p>
                        <p class="text-xs text-gray mt-2">{{ $request->approved_at?->diffForHumans() }}</p>
                    </div>
                    <div class="p-4 bg-info/10 border border-info/20 rounded-2xl">
                        <p class="text-[10px] font-black text-info uppercase tracking-widest mb-1">Rate Updated</p>
                        <p class="text-xs text-gray">{{ $request->astrologer?->user?->name }}'s {{ $request->price_type }} rate set to ${{ number_format($request->new_price, 2) }}</p>
                    </div>
                @else
                    <div class="p-4 bg-danger/10 border border-danger/20 rounded-2xl mb-4">
                        <p class="text-[10px] font-black text-danger uppercase tracking-widest">
                            <i class="fas fa-times-circle mr-2"></i> Rejected
                        </p>
                        <p class="text-xs text-gray mt-2">{{ $request->rejected_at?->diffForHumans() }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.price-increase-requests.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                @if($request->astrologer)
                    <a href="{{ route('admin.astrologers.edit', $request->astrologer_id) }}" class="block w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all text-center">
                        <i class="fas fa-user mr-2"></i> View Astrologer
                    </a>
                @endif
            </div>

            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Request ID:</span>
                        <span class="text-dark font-black">R-{{ $request->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Price Type:</span>
                        <span class="text-dark font-black capitalize">{{ $request->price_type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Level:</span>
                        <span class="text-dark font-black">Level {{ $request->level?->level_number ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Old Rate:</span>
                        <span class="text-dark font-black">${{ number_format($request->old_price, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">New Rate:</span>
                        <span class="text-dark font-black">${{ number_format($request->new_price, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Increase:</span>
                        <span class="text-success font-black">+${{ number_format($request->increase_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
