@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.notices.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Notices
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ $notice->title }}</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">{{ $notice->created_at->format('M d, Y H:i A') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.notices.edit', $notice->id) }}" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <form action="{{ route('admin.notices.destroy', $notice->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
        <!-- Left: Notice Content -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Notice Content -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Notice Content
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3 flex items-center gap-2">
                            <span class="text-2xl">{{ $notice->icon ?? '📢' }}</span> Title
                        </div>
                        <h3 class="text-3xl font-black text-dark">{{ $notice->title }}</h3>
                    </div>

                    <div class="bg-light/30 p-8 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Description</div>
                        <div class="text-dark font-medium leading-relaxed whitespace-pre-wrap">{{ $notice->body }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Category</div>
                            <span class="inline-block px-3 py-1.5 bg-info/10 text-info text-[10px] font-black uppercase rounded-full border border-info/20">
                                {{ ucfirst($notice->tag) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Urgency</div>
                            @if($notice->is_urgent)
                                <span class="inline-block px-3 py-1.5 bg-danger/10 text-danger text-[10px] font-black uppercase rounded-full border border-danger/20">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Urgent
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-check-circle mr-1"></i> Normal
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Status</div>
                            @if($notice->is_active)
                                <span class="inline-block px-3 py-1.5 bg-success/10 text-success text-[10px] font-black uppercase rounded-full border border-success/20">
                                    <i class="fas fa-broadcast-tower mr-1"></i> Active
                                </span>
                            @else
                                <span class="inline-block px-3 py-1.5 bg-gray/10 text-gray text-[10px] font-black uppercase rounded-full border border-gray/20">
                                    <i class="fas fa-eye-slash mr-1"></i> Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            @if($notice->notes)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-warning uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-warning/30"></span> Internal Notes
                </h2>
                
                <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                    <p class="text-dark font-medium whitespace-pre-wrap">{{ $notice->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Details
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Created At</div>
                        <p class="text-dark font-bold">{{ $notice->created_at->format('M d, Y H:i A') }}</p>
                    </div>
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Updated At</div>
                        <p class="text-dark font-bold">{{ $notice->updated_at->format('M d, Y H:i A') }}</p>
                    </div>
                    @if($notice->target_audience)
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Target Audience</div>
                        <p class="text-dark font-bold capitalize">{{ $notice->target_audience }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Status Control -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-broadcast-tower mr-2"></i> Publishing
                </h3>
                <form action="{{ route('admin.notices.toggle-status', $notice->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @if($notice->is_active)
                        <div class="p-4 bg-success/10 border border-success/20 rounded-2xl mb-4">
                            <p class="text-[10px] font-black text-success uppercase tracking-widest">
                                <i class="fas fa-broadcast-tower mr-2"></i> Published
                            </p>
                        </div>
                        <button type="submit" class="w-full bg-warning/10 text-warning py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-warning/20 transition-all border border-warning/20">
                            <i class="fas fa-eye-slash mr-2"></i> Unpublish
                        </button>
                    @else
                        <div class="p-4 bg-gray/10 border border-gray/20 rounded-2xl mb-4">
                            <p class="text-[10px] font-black text-gray uppercase tracking-widest">
                                <i class="fas fa-eye-slash mr-2"></i> Draft
                            </p>
                        </div>
                        <button type="submit" class="w-full bg-success/10 text-success py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-success/20 transition-all border border-success/20">
                            <i class="fas fa-broadcast-tower mr-2"></i> Publish
                        </button>
                    @endif
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.notices.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <a href="{{ route('admin.notices.edit', $notice->id) }}" class="block w-full bg-info/10 text-info py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-info/20 transition-all text-center">
                    <i class="fas fa-edit mr-2"></i> Edit Notice
                </a>
            </div>

            <!-- Info Card -->
            <div class="bg-primary/10 p-8 rounded-[32px] border border-primary/20">
                <h3 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> Notice Info
                </h3>
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Status:</span>
                        <span class="text-dark font-black">{{ $notice->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Urgency:</span>
                        <span class="text-dark font-black">{{ $notice->is_urgent ? 'High' : 'Normal' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Category:</span>
                        <span class="text-dark font-black capitalize">{{ $notice->tag }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
