@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Details</h1>
            <p class="text-sm text-gray">View the complete user profile and API-sourced data fields.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-white rounded-2xl shadow-sm hover:bg-secondary-dark transition-all">
                <i class="fas fa-edit"></i>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-danger text-white rounded-2xl shadow-sm hover:bg-danger-dark transition-all">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            </form>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-lighter text-gray rounded-2xl hover:bg-light transition-all">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-gray-lighter shadow-sm overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 p-8">
            <div class="space-y-6 bg-light/30 rounded-3xl p-6">
                <div class="flex items-center justify-center w-28 h-28 rounded-[30px] bg-primary/10 text-primary text-6xl font-black">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-2xl font-black text-dark">{{ $user->name }}</h2>
                    <p class="text-xs text-gray uppercase tracking-widest mt-2">ID: #USR-{{ $user->id }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="px-3 py-2 rounded-full bg-info/10 text-info text-[11px] font-black uppercase tracking-[0.2em]">{{ ucfirst($user->user_type) }}</span>
                    <span class="px-3 py-2 rounded-full {{ $user->profile_completed ? 'bg-success/10 text-success' : 'bg-gray-lighter text-gray' }} text-[11px] font-black uppercase tracking-[0.2em]">
                        {{ $user->profile_completed ? 'Profile Complete' : 'Incomplete' }}
                    </span>
                </div>
                <div class="space-y-3">
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Wallet Balance</div>
                    <div class="text-3xl font-black text-dark">₹{{ number_format(optional($user->wallet)->balance ?? 0, 2) }}</div>
                </div>
                <div class="space-y-3">
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Online / Busy</div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-2 rounded-full {{ $user->is_online ? 'bg-success/10 text-success' : 'bg-gray-lighter text-gray' }} text-[11px] font-black uppercase">Online</span>
                        <span class="px-3 py-2 rounded-full {{ $user->is_busy ? 'bg-warning/10 text-warning' : 'bg-gray-lighter text-gray' }} text-[11px] font-black uppercase">Busy</span>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6">
                    <h3 class="text-xs font-black text-gray uppercase tracking-[0.25em] mb-4">Contact Information</h3>
                    <div class="space-y-4 text-sm text-dark">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Email</div>
                            <div>{{ $user->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Phone</div>
                            <div>{{ $user->phone ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">City</div>
                            <div>{{ $user->city ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Country</div>
                            <div>{{ $user->country ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6">
                    <h3 class="text-xs font-black text-gray uppercase tracking-[0.25em] mb-4">Birth & Profile Data</h3>
                    <div class="space-y-4 text-sm text-dark">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Gender</div>
                            <div>{{ ucfirst($user->gender ?? '-') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Date of Birth</div>
                            <div>{{ optional($user->date_of_birth)->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Time of Birth</div>
                            <div>{{ optional($user->time_of_birth)->format('H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Place of Birth</div>
                            <div>{{ $user->place_of_birth ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Languages</div>
                            <div>{{ empty($user->languages) ? '-' : implode(', ', (array) $user->languages) }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6 lg:col-span-2">
                    <h3 class="text-xs font-black text-gray uppercase tracking-[0.25em] mb-4">Account & API Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-dark">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Account Created</div>
                            <div>{{ $user->created_at?->format('d M Y, H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Updated</div>
                            <div>{{ $user->updated_at?->format('d M Y, H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Email Verified</div>
                            <div>{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y, H:i') : 'No' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Plan</div>
                            <div>{{ optional($user->plan)->name ?? ($user->plan_id ? 'Plan #' . $user->plan_id : '-') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Plan Started</div>
                            <div>{{ optional($user->plan_started_at)->format('d M Y, H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Plan Expires</div>
                            <div>{{ optional($user->plan_expires_at)->format('d M Y, H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Last Seen</div>
                            <div>{{ optional($user->last_seen_at)->format('d M Y, H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Busy Session ID</div>
                            <div>{{ $user->busy_session_id ?? '-' }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-[10px] font-black text-gray uppercase">FCM Token</div>
                            <div class="break-words">{{ $user->fcm_token ?? '-' }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-[10px] font-black text-gray uppercase">OTP</div>
                            <div>{{ $user->otp ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">OTP Expires At</div>
                            <div>{{ optional($user->otp_expires_at)->format('d M Y, H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">OTP Verified At</div>
                            <div>{{ optional($user->otp_verified_at)->format('d M Y, H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
