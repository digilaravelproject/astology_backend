@extends('admin.layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-1">Astrologer Profile</h1>
            <p class="text-sm text-slate-500">Review full astrologer details, documents, and service settings.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.astrologers.edit', $user->id) }}" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-sky-600 text-white shadow-sm hover:bg-sky-700 transition">
                <i class="fas fa-edit"></i>
                <span>Edit</span>
            </a>
            <form method="POST" action="{{ route('admin.astrologers.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this astrologer?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-rose-600 text-white shadow-sm hover:bg-rose-700 transition">
                    <i class="fas fa-trash-alt"></i>
                    <span>Delete</span>
                </button>
            </form>
            <a href="{{ route('admin.astrologers.index') }}" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                <i class="fas fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-6 xl:p-8 grid grid-cols-1 xl:grid-cols-4 gap-6">
            <div class="rounded-3xl bg-slate-50 p-6 flex flex-col items-center text-center gap-4">
                @if(optional($user)->astrologer?->profile_photo)
                    <div class="w-32 h-32 rounded-3xl overflow-hidden shadow-lg">
                        <img src="{{ optional($user)->astrologer?->profile_photo }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                    </div>
                @else
                    <div class="w-32 h-32 rounded-3xl bg-slate-200 flex items-center justify-center text-6xl font-black text-slate-600 shadow-lg">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-[0.24em] mt-2">ID: #USR-{{ $user->id }}</p>
                </div>
                <div class="space-y-2 w-full">
                    <div class="inline-flex items-center justify-center w-full rounded-full bg-sky-100 text-sky-700 text-xs font-semibold uppercase tracking-[0.2em] py-2">Astrologer</div>
                    <div class="inline-flex items-center justify-center w-full rounded-full {{ optional($user)->astrologer?->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : (optional($user)->astrologer?->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }} text-xs font-semibold uppercase tracking-[0.2em] py-2">
                        {{ ucfirst(optional($user)->astrologer?->status ?? 'pending') }}
                    </div>
                </div>
            </div>
            <div class="xl:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 mb-3">Wallet Balance</p>
                    <p class="text-3xl font-extrabold text-slate-900">₹{{ number_format(optional($user->wallet)->balance ?? 0, 2) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 mb-3">Experience</p>
                    <p class="text-3xl font-extrabold text-sky-600">{{ optional($user)->astrologer?->years_of_experience ?? '-' }} <span class="text-base text-slate-500">yrs</span></p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 mb-3">Expertise Areas</p>
                    <p class="text-3xl font-extrabold text-amber-600">{{ count((array) (optional($user)->astrologer?->areas_of_expertise ?? [])) }} <span class="text-base text-slate-500">fields</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="space-y-6 xl:col-span-1">
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Contact</p>
                        <h3 class="text-lg font-bold text-slate-900">Details</h3>
                    </div>
                </div>
                <div class="space-y-4 text-sm text-slate-700">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Email</p>
                        <a href="mailto:{{ $user->email }}" class="font-semibold text-slate-900 hover:text-sky-600 break-words">{{ $user->email ?? '-' }}</a>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Phone</p>
                        <a href="tel:{{ $user->phone }}" class="font-semibold text-slate-900 hover:text-sky-600">{{ $user->phone ?? '-' }}</a>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">City</p>
                        <p class="font-semibold text-slate-900">{{ $user->city ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Country</p>
                        <p class="font-semibold text-slate-900">{{ $user->country ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Billing</p>
                        <h3 class="text-lg font-bold text-slate-900">Address</h3>
                    </div>
                </div>
                <div class="space-y-4 text-sm text-slate-700">
                    @if($billingAddress)
                        <div>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Address Line 1</p>
                            <p class="font-semibold text-slate-900">{{ $billingAddress->address_line1 }}</p>
                        </div>
                        @if($billingAddress->address_line2)
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Address Line 2</p>
                                <p class="font-semibold text-slate-900">{{ $billingAddress->address_line2 }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">City</p>
                            <p class="font-semibold text-slate-900">{{ $billingAddress->city }}</p>
                        </div>
                        @if($billingAddress->state)
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">State</p>
                                <p class="font-semibold text-slate-900">{{ $billingAddress->state }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Postal Code</p>
                            <p class="font-semibold text-slate-900">{{ $billingAddress->postal_code }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Country</p>
                            <p class="font-semibold text-slate-900">{{ $billingAddress->country }}</p>
                        </div>
                        @if($billingAddress->invoice_name)
                            <div>
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Invoice Name</p>
                                <p class="font-semibold text-slate-900">{{ $billingAddress->invoice_name }}</p>
                            </div>
                        @endif
                    @else
                        <p class="text-slate-500 italic">No billing address added yet.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Timeline</p>
                        <h3 class="text-lg font-bold text-slate-900">Activity</h3>
                    </div>
                </div>
                <div class="space-y-4 text-sm text-slate-700">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Joined On</p>
                        <p class="font-semibold text-slate-900">{{ $user->created_at?->format('d M Y') }}</p>
                        <p class="text-xs text-slate-500">{{ $user->created_at?->format('H:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Last Updated</p>
                        <p class="font-semibold text-slate-900">{{ $user->updated_at?->format('d M Y') }}</p>
                        <p class="text-xs text-slate-500">{{ $user->updated_at?->format('H:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Professional Details</p>
                        <h3 class="text-lg font-bold text-slate-900">Astrologer Info</h3>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Years of Experience</p>
                        <p class="text-2xl font-bold text-slate-900">{{ optional($user)->astrologer?->years_of_experience ?? '-' }} yrs</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Date of Birth</p>
                        <p class="text-lg font-bold text-slate-900">{{ optional(optional($user)->astrologer?->date_of_birth)->format('d M Y') ?? '-' }}</p>
                    </div>
                </div>
                <div class="mt-6 space-y-5">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Areas of Expertise</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse((array) (optional($user)->astrologer?->areas_of_expertise ?? []) as $expertise)
                                <span class="px-3 py-1 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold">{{ $expertise }}</span>
                            @empty
                                <span class="text-slate-500">Not specified</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Languages</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse((array) (optional($user)->astrologer?->languages ?? []) as $language)
                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">{{ $language }}</span>
                            @empty
                                <span class="text-slate-500">Not specified</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Bio</p>
                        <p class="text-sm leading-relaxed text-slate-700">{{ optional($user)->astrologer?->bio ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Documents</p>
                        <h3 class="text-lg font-bold text-slate-900">Verification Files</h3>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">ID Proof Number</p>
                        <p class="font-semibold text-slate-900 font-mono">{{ optional($user)->astrologer?->id_proof_number ?? '-' }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-3">Profile Photo</p>
                        @if(optional($user)->astrologer?->profile_photo)
                            <a href="{{ optional($user)->astrologer?->profile_photo }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-sky-100 text-sky-700 hover:bg-sky-200 transition font-semibold text-sm">
                                <i class="fas fa-image"></i>
                                View Photo
                            </a>
                        @else
                            <p class="text-sm text-slate-500 italic">Not uploaded</p>
                        @endif
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-3">ID Proof Document</p>
                        @if(optional($user)->astrologer?->id_proof)
                            <a href="{{ optional($user)->astrologer?->id_proof }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-cyan-100 text-cyan-700 hover:bg-cyan-200 transition font-semibold text-sm">
                                <i class="fas fa-file-pdf"></i>
                                View Document
                            </a>
                        @else
                            <p class="text-sm text-slate-500 italic">Not uploaded</p>
                        @endif
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-3">Certificate</p>
                        @if(optional($user)->astrologer?->certificate)
                            <a href="{{ optional($user)->astrologer?->certificate }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition font-semibold text-sm">
                                <i class="fas fa-certificate"></i>
                                View Certificate
                            </a>
                        @else
                            <p class="text-sm text-slate-500 italic">Not uploaded</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Service Configuration</p>
                        <h3 class="text-lg font-bold text-slate-900">Service Rates</h3>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center"><i class="fas fa-comments"></i></div>
                            <span class="px-3 py-1 rounded-full {{ optional($user)->astrologer?->chat_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} text-[10px] font-semibold uppercase">{{ optional($user)->astrologer?->chat_enabled ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Chat</p>
                        <p class="text-2xl font-bold text-sky-700">₹{{ optional($user)->astrologer?->chat_rate_per_minute ?? '0' }}<span class="text-sm text-slate-500">/min</span></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center"><i class="fas fa-phone"></i></div>
                            <span class="px-3 py-1 rounded-full {{ optional($user)->astrologer?->call_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} text-[10px] font-semibold uppercase">{{ optional($user)->astrologer?->call_enabled ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Voice Call</p>
                        <p class="text-2xl font-bold text-indigo-700">₹{{ optional($user)->astrologer?->call_rate_per_minute ?? '0' }}<span class="text-sm text-slate-500">/min</span></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fas fa-video"></i></div>
                            <span class="px-3 py-1 rounded-full {{ optional($user)->astrologer?->video_call_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} text-[10px] font-semibold uppercase">{{ optional($user)->astrologer?->video_call_enabled ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">Video Call</p>
                        <p class="text-2xl font-bold text-emerald-700">₹{{ optional($user)->astrologer?->video_call_rate_per_minute ?? '0' }}<span class="text-sm text-slate-500">/min</span></p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-5 border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center"><i class="fas fa-star"></i></div>
                            <span class="px-3 py-1 rounded-full {{ optional($user)->astrologer?->po_at_5_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} text-[10px] font-semibold uppercase">{{ optional($user)->astrologer?->po_at_5_enabled ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-2">PO at ₹5</p>
                        <p class="text-2xl font-bold text-orange-700">₹{{ optional($user)->astrologer?->po_at_5_rate_per_minute ?? '0' }}<span class="text-sm text-slate-500">/min</span></p>
                        <div class="mt-4 text-[10px] uppercase tracking-[0.2em] text-slate-500 mb-1">Sessions</div>
                        <p class="text-2xl font-bold text-orange-700">{{ optional($user)->astrologer?->po_at_5_sessions ?? '0' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection