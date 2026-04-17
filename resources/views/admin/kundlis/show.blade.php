@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.kundlis.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Kundlis
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ $kundli->name }}</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Horoscope & Birth Chart Record</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.kundlis.edit', $kundli->id) }}" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <form action="{{ route('admin.kundlis.destroy', $kundli->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
        <!-- Left: Kundli Details -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Personal Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Personal Information
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Full Name</div>
                            <div class="text-2xl font-black text-dark">{{ $kundli->name }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Gender</div>
                            @if($kundli->gender === 'male')
                                <div class="inline-block px-4 py-2.5 bg-info/10 text-info text-sm font-black uppercase rounded-2xl border border-info/20">
                                    <i class="fas fa-mars mr-2"></i> Male
                                </div>
                            @elseif($kundli->gender === 'female')
                                <div class="inline-block px-4 py-2.5 bg-danger/10 text-danger text-sm font-black uppercase rounded-2xl border border-danger/20">
                                    <i class="fas fa-venus mr-2"></i> Female
                                </div>
                            @else
                                <div class="inline-block px-4 py-2.5 bg-gray/10 text-gray text-sm font-black uppercase rounded-2xl border border-gray/20">
                                    <i class="fas fa-question-circle mr-2"></i> Other
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Birth Information -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> Birth Details
                </h2>
                
                <div class="space-y-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Birth Date</div>
                        <p class="text-lg font-black text-dark">{{ $kundli->birth_date ? \Carbon\Carbon::parse($kundli->birth_date)->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Birth Time</div>
                        <p class="text-lg font-black text-dark">{{ $kundli->birth_time ? \Carbon\Carbon::parse($kundli->birth_time)->format('H:i A') : 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Birth Location</div>
                        <p class="text-lg font-black text-dark">{{ $kundli->birth_location ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Geographical Coordinates -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Location Coordinates
                </h2>
                
                <div class="space-y-6">
                    @if($kundli->latitude && $kundli->longitude)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Latitude</div>
                                <p class="text-lg font-mono font-black text-dark">{{ $kundli->latitude }}</p>
                            </div>
                            <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Longitude</div>
                                <p class="text-lg font-mono font-black text-dark">{{ $kundli->longitude }}</p>
                            </div>
                        </div>
                        <div class="bg-success/10 p-6 rounded-2xl border border-success/20">
                            <p class="text-[10px] font-black text-success uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> Coordinates Available
                            </p>
                        </div>
                    @else
                        <div class="bg-warning/10 p-6 rounded-2xl border border-warning/20">
                            <p class="text-[10px] font-black text-warning uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i> Coordinates Not Set
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Additional Notes -->
            @if($kundli->notes)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-warning uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-warning/30"></span> Notes
                </h2>
                
                <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                    <p class="text-dark font-medium whitespace-pre-wrap">{{ $kundli->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-dark uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <i class="fas fa-info-circle"></i> Record Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Created At</div>
                        <p class="text-dark font-bold">{{ $kundli->created_at->format('M d, Y H:i A') }}</p>
                    </div>
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Updated At</div>
                        <p class="text-dark font-bold">{{ $kundli->updated_at->format('M d, Y H:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.kundlis.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <a href="{{ route('admin.kundlis.edit', $kundli->id) }}" class="block w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all text-center">
                    <i class="fas fa-edit mr-2"></i> Edit Kundli
                </a>
            </div>

            <!-- Details Card -->
            <div class="bg-primary/10 p-8 rounded-[32px] border border-primary/20">
                <h3 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-line"></i> Kundli Stats
                </h3>
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Name:</span>
                        <span class="text-dark font-black">{{ Str::limit($kundli->name, 15) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Gender:</span>
                        <span class="text-dark font-black capitalize">{{ $kundli->gender }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Age:</span>
                        <span class="text-dark font-black">
                            @if($kundli->birth_date)
                                {{ \Carbon\Carbon::parse($kundli->birth_date)->age }} years
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Created:</span>
                        <span class="text-dark font-black">{{ $kundli->created_at->format('M d') }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div class="bg-success/10 p-8 rounded-[32px] border border-success/20">
                <h3 class="text-[10px] font-black text-success uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Record Active
                </h3>
                <p class="text-[10px] text-gray font-medium">This kundli record is available for use in astrological services.</p>
            </div>
        </div>
    </div>
</div>
@endsection
