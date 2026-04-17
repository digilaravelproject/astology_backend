@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.app-notifications.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Notifications
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ $notification->title }}</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">{{ $notification->created_at->format('M d, Y H:i A') }}</p>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('admin.app-notifications.destroy', $notification->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
        <!-- Left: Notification Content -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- Notification Content -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> Notification Details
                </h2>
                
                <div class="space-y-6">
                    <div>
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Title</div>
                        <h3 class="text-3xl font-black text-dark">{{ $notification->title }}</h3>
                    </div>

                    <div class="bg-light/30 p-8 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Message</div>
                        <div class="text-dark font-medium leading-relaxed whitespace-pre-wrap">{{ $notification->body }}</div>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Sent Date</div>
                        <p class="text-dark font-bold">{{ $notification->created_at->format('M d, Y H:i A') }}</p>
                    </div>
                    <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                        <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Status</div>
                        @if($notification->is_read)
                            <p class="text-success font-black">Viewed</p>
                        @else
                            <p class="text-warning font-black">Unread</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Additional Meta -->
            @if($notification->meta && count($notification->meta) > 0)
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> Additional Data
                </h2>
                
                <div class="space-y-4">
                    @foreach($notification->meta as $key => $value)
                        <div class="bg-light/30 p-6 rounded-2xl border border-gray-lighter">
                            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                            <p class="text-dark font-medium break-words">{{ is_array($value) ? json_encode($value) : $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right: Action Panel -->
        <div class="space-y-6">
            <!-- Status Control -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-envelope-open mr-2"></i> Mark As
                </h3>
                @if($notification->is_read)
                    <div class="p-4 bg-success/10 border border-success/20 rounded-2xl mb-4">
                        <p class="text-[10px] font-black text-success uppercase tracking-widest">
                            <i class="fas fa-check-circle mr-2"></i> Read
                        </p>
                    </div>
                    <form action="{{ route('admin.app-notifications.mark-unread', $notification->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-warning/10 text-warning py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-warning/20 transition-all border border-warning/20">
                            <i class="fas fa-envelope mr-2"></i> Mark Unread
                        </button>
                    </form>
                @else
                    <div class="p-4 bg-warning/10 border border-warning/20 rounded-2xl mb-4">
                        <p class="text-[10px] font-black text-warning uppercase tracking-widest">
                            <i class="fas fa-envelope mr-2"></i> Unread
                        </p>
                    </div>
                    <form action="{{ route('admin.app-notifications.mark-read', $notification->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-success/10 text-success py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-success/20 transition-all border border-success/20">
                            <i class="fas fa-check-circle mr-2"></i> Mark Read
                        </button>
                    </form>
                @endif
            </div>

            <!-- User Information -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">
                    <i class="fas fa-user mr-2"></i> Recipient
                </h3>
                @if($notification->user)
                <div class="space-y-4 text-[10px]">
                    <div>
                        <span class="text-gray font-bold">Name:</span>
                        <p class="text-dark font-black">{{ $notification->user->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray font-bold">Email:</span>
                        <p class="text-dark font-bold font-mono">{{ $notification->user->email }}</p>
                    </div>
                    <div>
                        <span class="text-gray font-bold">Phone:</span>
                        <p class="text-dark font-bold">{{ $notification->user->phone ?? 'N/A' }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.users.show', $notification->user->id) }}" class="block mt-4 w-full bg-primary/10 text-primary py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-user-circle mr-2"></i> View User
                </a>
                @else
                <p class="text-gray font-medium">User no longer exists</p>
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm space-y-3">
                <h3 class="text-[10px] font-black text-dark uppercase tracking-widest mb-6">Quick Actions</h3>
                <a href="{{ route('admin.app-notifications.index') }}" class="block w-full bg-primary/10 text-primary py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary/20 transition-all text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>

            <!-- Info Card -->
            <div class="bg-primary/10 p-8 rounded-[32px] border border-primary/20">
                <h3 class="text-[10px] font-black text-primary uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> Details
                </h3>
                <div class="space-y-3 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Status:</span>
                        <span class="text-dark font-black">{{ $notification->is_read ? 'Read' : 'Unread' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray font-bold">Created:</span>
                        <span class="text-dark font-black">{{ $notification->created_at->format('M d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
