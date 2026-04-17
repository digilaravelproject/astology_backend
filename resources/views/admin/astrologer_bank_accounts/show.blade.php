@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.astrologer-bank-accounts.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Bank Accounts
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Bank Account Details</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">{{ $account->account_holder_name }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.astrologer-bank-accounts.edit', $account->id) }}" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <form action="{{ route('admin.astrologer-bank-accounts.destroy', $account->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-8 py-4 bg-danger/10 border-2 border-danger text-danger text-[11px] font-black uppercase rounded-2xl hover:bg-danger/20 transition-all">
                    <i class="fas fa-trash mr-2"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Left: Account Details -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Account Summary -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Account Summary
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Account Holder</div>
                            <div class="text-2xl font-black text-dark">{{ $account->account_holder_name }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Status</div>
                            @if($account->is_active)
                                <div class="inline-block px-4 py-2.5 bg-success/10 text-success text-sm font-black uppercase rounded-2xl border border-success/20">
                                    <i class="fas fa-check-circle mr-2"></i> Verified
                                </div>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-warning/10 text-warning text-sm font-black uppercase rounded-2xl border border-warning/20">
                                    <i class="fas fa-clock mr-2"></i> Pending
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> Bank Details
                </h2>
                
                <div class="space-y-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Bank Name</div>
                        <p class="text-lg font-black text-dark">{{ $account->bank_name }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Branch</div>
                        <p class="text-lg font-black text-dark">{{ $account->branch_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">IFSC Code</div>
                        <p class="text-lg font-bold text-dark font-mono">{{ $account->ifsc_code }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Account Type</div>
                        <p class="text-lg font-black text-dark capitalize">{{ $account->account_type }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Account Number (Last 4)</div>
                        <div class="inline-block px-4 py-2.5 bg-light rounded-2xl border border-gray-lighter">
                            <p class="text-lg font-mono font-black text-dark">****{{ substr($account->account_number, -4) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Astrologer Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Associated Astrologer
                </h2>
                
                @if($account->astrologer)
                <div class="space-y-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Name</div>
                        <p class="text-lg font-black text-dark">{{ $account->astrologer->name }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Email</div>
                        <p class="text-sm font-medium text-dark">{{ $account->astrologer->email }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Phone</div>
                        <p class="text-sm font-medium text-dark">{{ $account->astrologer->phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Specialization</div>
                        <p class="text-sm font-medium text-dark">{{ $account->astrologer->specialization ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.astrologers.show', $account->astrologer->id) }}" class="inline-block px-6 py-3 bg-success/10 text-success font-black text-[10px] uppercase rounded-2xl hover:bg-success/20 transition-all border border-success/20">
                        <i class="fas fa-user mr-2"></i> View Astrologer Profile
                    </a>
                </div>
                @else
                <p class="text-gray font-medium">No astrologer assigned</p>
                @endif
            </div>

            <!-- Document Information -->
            @if($account->document_type)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-warning uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-warning/30"></span> Document Details
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Document Type</div>
                        <p class="text-lg font-black text-dark capitalize">{{ $account->document_type }}</p>
                    </div>
                    @if($account->passbook_document)
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Uploaded Document</div>
                        <a href="{{ asset('storage/' . $account->passbook_document) }}" target="_blank" class="inline-block px-6 py-3 bg-primary/10 text-primary font-black text-[10px] uppercase rounded-2xl hover:bg-primary/20 transition-all border border-primary/20">
                            <i class="fas fa-download mr-2"></i> Download Document
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($account->notes)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <i class="fas fa-sticky-note"></i> Internal Notes
                </h2>
                
                <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                    <p class="text-dark font-medium whitespace-pre-wrap">{{ $account->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Verification Button -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-shield-alt mr-2"></i> Verification
                </h3>
                <form action="{{ route('admin.astrologer-bank-accounts.toggle-verification', $account->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @if($account->is_active)
                        <div class="p-4 bg-success/10 border border-success/20 rounded-2xl mb-4">
                            <p class="text-[10px] font-black text-success uppercase tracking-widest">
                                <i class="fas fa-check-circle mr-2"></i> Verified
                            </p>
                        </div>
                        <button type="submit" class="w-full bg-danger/10 text-danger py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-danger/20 transition-all border border-danger/20">
                            <i class="fas fa-times mr-2"></i> Mark Unverified
                        </button>
                    @else
                        <div class="p-4 bg-warning/10 border border-warning/20 rounded-2xl mb-4">
                            <p class="text-[10px] font-black text-warning uppercase tracking-widest">
                                <i class="fas fa-clock mr-2"></i> Pending
                            </p>
                        </div>
                        <button type="submit" class="w-full bg-success/10 text-success py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-success/20 transition-all border border-success/20">
                            <i class="fas fa-check-circle mr-2"></i> Verify Account
                        </button>
                    @endif
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.astrologer-bank-accounts.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <a href="{{ route('admin.astrologer-bank-accounts.edit', $account->id) }}" class="block w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all text-center">
                    <i class="fas fa-edit mr-2"></i> Edit Details
                </a>
            </div>

            <!-- Account Info Card -->
            <div class="bg-primary/10 p-8 rounded-[32px] border border-primary/20">
                <h3 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> Account Info
                </h3>
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Created:</span>
                        <span class="text-dark font-black">{{ $account->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Updated:</span>
                        <span class="text-dark font-black">{{ $account->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
