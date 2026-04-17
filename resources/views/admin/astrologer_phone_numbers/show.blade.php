@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.astrologer-phone-numbers.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Phone Numbers
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ $phone->phone_number }}</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">{{ $phone->country_code }}{{ $phone->phone_number }}</p>
        </div>
        <div class="flex gap-3">
            @if(!$phone->is_default)
            <form action="{{ route('admin.astrologer-phone-numbers.set-default', $phone->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-8 py-4 bg-primary/10 border-2 border-primary text-primary text-[11px] font-black uppercase rounded-2xl hover:bg-primary/20 transition-all">
                    <i class="fas fa-star mr-2"></i> Set as Default
                </button>
            </form>
            @endif
            <form action="{{ route('admin.astrologer-phone-numbers.destroy', $phone->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
        <!-- Left: Phone Details -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Phone Summary -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Phone Information
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Phone Number</div>
                            <div class="text-2xl font-mono font-black text-dark">{{ $phone->country_code }}{{ $phone->phone_number }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Country</div>
                            <div class="text-lg font-black text-dark">{{ $phone->country_name ?? $phone->country_code }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Verification Status</div>
                            @if($phone->otp_verified_at)
                                <div class="inline-block px-4 py-2.5 bg-success/10 text-success text-sm font-black uppercase rounded-2xl border border-success/20">
                                    <i class="fas fa-check-circle mr-2"></i> Verified
                                </div>
                                <p class="text-[10px] text-gray mt-2">{{ $phone->otp_verified_at->format('M d, Y H:i A') }}</p>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-warning/10 text-warning text-sm font-black uppercase rounded-2xl border border-warning/20">
                                    <i class="fas fa-clock mr-2"></i> Unverified
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Default Phone</div>
                            @if($phone->is_default)
                                <div class="inline-block px-4 py-2.5 bg-primary/10 text-primary text-sm font-black uppercase rounded-2xl border border-primary/20">
                                    <i class="fas fa-star mr-2"></i> Primary
                                </div>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-gray/10 text-gray text-sm font-black uppercase rounded-2xl border border-gray/20">
                                    <i class="fas fa-star-o mr-2"></i> Secondary
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Astrologer Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> Associated Astrologer
                </h2>
                
                @if($phone->astrologer)
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Name</div>
                            <p class="text-lg font-black text-dark">{{ $phone->astrologer->name }}</p>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Email</div>
                            <p class="text-sm font-medium text-dark">{{ $phone->astrologer->email }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Primary Phone</div>
                            <p class="text-sm font-mono font-bold text-dark">{{ $phone->astrologer->phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Status</div>
                            @if($phone->astrologer->is_active)
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-check-circle mr-1"></i> Active
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-full border border-danger/20">
                                    <i class="fas fa-ban mr-1"></i> Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.astrologers.show', $phone->astrologer->id) }}" class="inline-block px-6 py-3 bg-info/10 text-info font-black text-[10px] uppercase rounded-2xl hover:bg-info/20 transition-all border border-info/20">
                        <i class="fas fa-user mr-2"></i> View Astrologer Profile
                    </a>
                </div>
                @else
                <p class="text-gray font-medium">No astrologer assigned</p>
                @endif
            </div>

            <!-- Additional Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Metadata
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Created At</div>
                        <p class="text-dark font-bold">{{ $phone->created_at->format('M d, Y H:i A') }}</p>
                    </div>
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Updated At</div>
                        <p class="text-dark font-bold">{{ $phone->updated_at->format('M d, Y H:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Verification Control -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-shield-alt mr-2"></i> Verification Control
                </h3>
                <form action="{{ route('admin.astrologer-phone-numbers.toggle-verification', $phone->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @if($phone->otp_verified_at)
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
                                <i class="fas fa-clock mr-2"></i> Unverified
                            </p>
                        </div>
                        <button type="submit" class="w-full bg-success/10 text-success py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-success/20 transition-all border border-success/20">
                            <i class="fas fa-check-circle mr-2"></i> Mark Verified
                        </button>
                    @endif
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.astrologer-phone-numbers.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                @if(!$phone->is_default)
                <form action="{{ route('admin.astrologer-phone-numbers.set-default', $phone->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all">
                        <i class="fas fa-star mr-2"></i> Set as Default
                    </button>
                </form>
                @else
                <div class="w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center border border-info/20">
                    <i class="fas fa-check-circle mr-2"></i> Default Number
                </div>
                @endif
            </div>

            <!-- Status Card -->
            <div class="bg-primary/10 p-8 rounded-[32px] border border-primary/20">
                <h3 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> Phone Info
                </h3>
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Format:</span>
                        <span class="text-dark font-black font-mono">{{ $phone->country_code }}{{ $phone->phone_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Country:</span>
                        <span class="text-dark font-black">{{ $phone->country_name ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Default:</span>
                        <span class="text-dark font-black">{{ $phone->is_default ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
