@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">User Details</h1>
            <p class="text-sm text-gray">View full user profile details and account information.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-white rounded-lg shadow-sm hover:bg-secondary-dark transition-all">
                <i class="fas fa-edit"></i>
                <span>Edit</span>
            </a>
            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-danger text-white rounded-lg shadow-sm hover:bg-danger-dark transition-all">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </form>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-lighter text-gray rounded-lg hover:bg-light transition-all">
                <i class="fas fa-arrow-left"></i>
                <span>Back to list</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
        <div class="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="flex flex-col items-center gap-4">
                <div class="w-28 h-28 rounded-[22px] bg-linear-to-br from-primary-light to-primary text-white flex items-center justify-center text-6xl font-black shadow-inner">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="text-center">
                    <h2 class="text-xl font-bold text-dark">{{ $user->name }}</h2>
                    <div class="text-xs text-gray uppercase tracking-widest">ID: #USR-{{ $user->id }}</div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest bg-info/10 text-info">{{ ucfirst($user->user_type) }}</span>
                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest {{ $user->profile_completed ? 'bg-success/10 text-success' : 'bg-gray-lighter text-gray' }}">
                        {{ $user->profile_completed ? 'Profile Complete' : 'Incomplete' }}
                    </span>
                </div>
                <div class="w-full p-6 bg-light/30 rounded-3xl border border-gray-lighter">
                    <div class="text-xs font-black text-gray uppercase tracking-widest mb-2">Wallet Balance</div>
                    <div class="text-3xl font-black text-dark">₹{{ number_format(optional($user->wallet)->balance ?? 0, 2) }}</div>
                </div>
            </div>

            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6">
                    <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Contact Information</div>
                    <div class="space-y-3">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Email</div>
                            <div class="text-sm font-bold text-dark">{{ $user->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Phone</div>
                            <div class="text-sm font-bold text-dark">{{ $user->phone ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">City</div>
                            <div class="text-sm font-bold text-dark">{{ $user->city ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Country</div>
                            <div class="text-sm font-bold text-dark">{{ $user->country ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6">
                    <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Profile Details</div>
                    <div class="space-y-3">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Gender</div>
                            <div class="text-sm font-bold text-dark">{{ ucfirst($user->gender ?? '-') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Date of Birth</div>
                            <div class="text-sm font-bold text-dark">{{ optional($user->date_of_birth)->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Time of Birth</div>
                            <div class="text-sm font-bold text-dark">{{ optional($user->time_of_birth)->format('H:i') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Place of Birth</div>
                            <div class="text-sm font-bold text-dark">{{ $user->place_of_birth ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Languages</div>
                            <div class="text-sm font-bold text-dark">{{ empty($user->languages) ? '-' : implode(', ', (array) $user->languages) }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-light/30 rounded-3xl border border-gray-lighter p-6 md:col-span-2">
                    <div class="text-[10px] font-black text-gray uppercase mb-2 tracking-widest">Account Info</div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Joined On</div>
                            <div class="text-sm font-bold text-dark">{{ $user->created_at?->format('d M Y, H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Last Updated</div>
                            <div class="text-sm font-bold text-dark">{{ $user->updated_at?->format('d M Y, H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">Email Verified</div>
                            <div class="text-sm font-bold text-dark">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y, H:i') : 'No' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-gray uppercase">User Type</div>
                            <div class="text-sm font-bold text-dark">{{ ucfirst($user->user_type) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
