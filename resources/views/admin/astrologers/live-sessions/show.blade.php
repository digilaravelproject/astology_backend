@extends('admin.layouts.app')

@section('content')
<div x-data="{ statusModal: false }">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $liveSession->title }}</h1>
            <p class="text-sm text-gray font-medium">{{ $liveSession->astrologer->user->name }}</p>
            <div class="text-xs text-gray mt-1">Live Session Details</div>
        </div>
        <div class="flex gap-3">
            <button @click="statusModal = true" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary-dark transition-all flex items-center gap-2">
                <i class="fas fa-edit"></i> Change Status
            </button>
            <a href="{{ route('admin.astrologers.live-sessions.index') }}" class="bg-light text-dark px-5 py-2.5 rounded-xl font-bold hover:bg-gray-lighter transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-success/10 border border-success/20 text-success px-6 py-4 rounded-2xl mb-8 flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="md:col-span-2">
            <!-- Session Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-6 mb-6">
                <h2 class="text-lg font-black text-dark mb-6">Session Details</h2>
                <div class="space-y-4">
                    <div class="border-b border-gray-lighter pb-4">
                        <label class="block text-[10px] font-black text-gray uppercase mb-2">Title</label>
                        <p class="text-sm font-bold text-dark">{{ $liveSession->title }}</p>
                    </div>
                    <div class="border-b border-gray-lighter pb-4">
                        <label class="block text-[10px] font-black text-gray uppercase mb-2">Description</label>
                        <p class="text-sm text-dark">{{ $liveSession->description ?? 'No description provided' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-lighter pb-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray uppercase mb-2">Session Type</label>
                            @if($liveSession->session_type === 'public')
                                <span class="px-2.5 py-1 bg-info/10 text-info text-[10px] font-black rounded-lg uppercase tracking-widest border border-info/20 inline-block">Public</span>
                            @else
                                <span class="px-2.5 py-1 bg-accent/10 text-accent text-[10px] font-black rounded-lg uppercase tracking-widest border border-accent/20 inline-block">Private</span>
                            @endif
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray uppercase mb-2">Duration</label>
                            <p class="text-sm font-bold text-dark">{{ $liveSession->duration_minutes }} minutes</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-lighter pb-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray uppercase mb-2">Max Participants</label>
                            <p class="text-sm font-bold text-dark">{{ $liveSession->max_participants }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray uppercase mb-2">Current Participants</label>
                            <p class="text-sm font-bold text-dark">{{ $liveSession->current_participants }}</p>
                        </div>
                    </div>
                    @if($liveSession->live_url)
                        <div class="border-b border-gray-lighter pb-4">
                            <label class="block text-[10px] font-black text-gray uppercase mb-2">Live URL</label>
                            <a href="{{ $liveSession->live_url }}" target="_blank" class="text-primary font-semibold hover:underline text-sm break-all">{{ $liveSession->live_url }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Schedule Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-6">
                <h2 class="text-lg font-black text-dark mb-6">Schedule Details</h2>
                <div class="space-y-4">
                    <div class="border-b border-gray-lighter pb-4">
                        <label class="block text-[10px] font-black text-gray uppercase mb-2">Scheduled Date & Time</label>
                        <p class="text-sm font-bold text-dark">{{ $liveSession->scheduled_at->format('d M Y, H:i:s') }}</p>
                    </div>
                    <div class="border-b border-gray-lighter pb-4">
                        <label class="block text-[10px] font-black text-gray uppercase mb-2">Created At</label>
                        <p class="text-sm font-bold text-dark">{{ $liveSession->created_at->format('d M Y, H:i:s') }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray uppercase mb-2">Last Updated</label>
                        <p class="text-sm font-bold text-dark">{{ $liveSession->updated_at->format('d M Y, H:i:s') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Status Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-6 mb-6">
                <h2 class="text-lg font-black text-dark mb-6">Status</h2>
                <div class="space-y-3">
                    @if($liveSession->status === 'upcoming')
                        <span class="px-4 py-2 bg-primary/10 text-primary text-[11px] font-black rounded-lg uppercase tracking-widest border border-primary/20 block text-center">Upcoming</span>
                    @elseif($liveSession->status === 'ongoing')
                        <span class="px-4 py-2 bg-success/10 text-success text-[11px] font-black rounded-lg uppercase tracking-widest border border-success/20 block text-center">Ongoing</span>
                    @elseif($liveSession->status === 'completed')
                        <span class="px-4 py-2 bg-accent/10 text-accent text-[11px] font-black rounded-lg uppercase tracking-widest border border-accent/20 block text-center">Completed</span>
                    @else
                        <span class="px-4 py-2 bg-danger/10 text-danger text-[11px] font-black rounded-lg uppercase tracking-widest border border-danger/20 block text-center">Cancelled</span>
                    @endif
                    <p class="text-[10px] text-gray-light text-center mt-3">Click "Change Status" to update</p>
                </div>
            </div>

            <!-- Astrologer Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter p-6">
                <h2 class="text-lg font-black text-dark mb-6">Astrologer</h2>
                <div class="space-y-3">
                    <div class="text-sm font-black text-dark">{{ $liveSession->astrologer->user->name }}</div>
                    <div class="text-[10px] text-gray break-all">{{ $liveSession->astrologer->user->email }}</div>
                    <a href="{{ route('admin.astrologers.show', $liveSession->astrologer->id) }}" class="block mt-4 px-4 py-2 bg-light text-dark text-center rounded-xl font-bold hover:bg-gray-lighter transition-all text-xs">View Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div x-show="statusModal" @click.self="statusModal = false" 
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" 
         style="display: none;" x-transition>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-lighter max-w-md w-full mx-4">
            <div class="border-b border-gray-lighter bg-light/50 p-6">
                <h3 class="font-black text-dark text-lg">Change Session Status</h3>
            </div>
            <form action="{{ route('admin.astrologers.live-sessions.update-status', $liveSession->id) }}" method="POST">
                @csrf
                <div class="p-6">
                    <label class="block text-[11px] font-black text-gray uppercase mb-3">Select New Status</label>
                    <select name="status" class="w-full px-4 py-3 bg-light/50 border border-gray-lighter rounded-xl focus:outline-none focus:border-primary/50 text-sm" required>
                        <option value="">-- Select Status --</option>
                        <option value="upcoming" {{ $liveSession->status === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="ongoing" {{ $liveSession->status === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ $liveSession->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $liveSession->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="border-t border-gray-lighter bg-light/50 p-4 flex justify-end gap-3">
                    <button type="button" @click="statusModal = false" class="px-4 py-2 bg-light text-dark rounded-xl font-bold hover:bg-gray-lighter transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-xl font-bold hover:bg-primary-dark transition-all">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
