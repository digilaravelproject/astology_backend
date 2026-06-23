@extends('admin.layouts.app')

@section('content')
<div x-data="{ activeTab: 'rate-limits' }">
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Rate Limit Governance</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Control API throttling globally and per-endpoint tier. Changes apply instantly without deploy.</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.settings.index') }}" class="px-8 py-4 bg-white border-2 border-gray-lighter text-dark text-[11px] font-black uppercase rounded-2xl hover:bg-light transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Back to Settings
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-medium flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Rate Limits Card -->
    <div class="bg-white rounded-[48px] border border-gray-lighter shadow-sm overflow-hidden">
        <div class="p-8 lg:p-12">
            <!-- Master Toggle -->
            <div class="mb-12 p-8 bg-light/30 rounded-3xl border border-gray-lighter">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-dark uppercase tracking-tighter">Rate Limiting Master Switch</h3>
                        <p class="text-xs text-gray font-medium mt-1">Disable to turn off ALL throttling across the platform</p>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.rate-limits.update') }}" class="flex items-center gap-4">
                        @csrf
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox"
                               name="enabled"
                               value="1"
                               {{ $enabled ? 'checked' : '' }}
                               class="w-12 h-7 appearance-none bg-gray-lighter rounded-full relative cursor-pointer after:absolute after:top-0.5 after:left-0.5 after:w-6 after:h-6 after:bg-white after:rounded-full after:transition-all checked:after:translate-x-full checked:bg-primary checked:border-primary border-2"
                               onchange="this.form.submit()">
                        <span class="text-xs font-black uppercase tracking-widest {{ $enabled ? 'text-primary' : 'text-gray' }}">
                            {{ $enabled ? 'ENABLED' : 'DISABLED' }}
                        </span>
                    </form>
                </div>
            </div>

            <!-- Per-Limiter Settings -->
            <div class="space-y-6">
                <h4 class="text-[10px] font-black text-gray uppercase tracking-widest">Per-Tier Limits (requests per minute)</h4>
                <p class="text-xs text-gray font-medium mb-6">Each tier corresponds to specific route groups. Value of 0 = no limit for that tier.</p>

                <form method="POST" action="{{ route('admin.settings.rate-limits.update') }}" ref="enabledForm">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $enabled ? '1' : '0' }}">

                    @foreach($limits as $limit)
                    <div class="bg-light/30 p-6 rounded-3xl border border-gray-lighter flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-2">{{ $limit['label'] }}</label>
                            <div class="flex items-center gap-3">
                                <input type="number"
                                       name="limits[{{ $limit['key'] }}]"
                                       value="{{ $limit['value'] }}"
                                       min="0"
                                       max="10000"
                                       class="w-24 md:w-32 bg-white border-2 border-gray-lighter rounded-xl px-4 py-3 text-center font-black text-sm focus:border-primary/30 transition-all"
                                       step="1">
                                <span class="text-xs font-bold text-gray">req/min</span>
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full md:w-auto px-6 py-3 bg-dark text-white text-[10px] font-black uppercase rounded-2xl hover:bg-primary transition-all shadow-lg flex items-center justify-center gap-2 self-start md:self-center"
                                name="save_{{ $limit['key'] }}"
                                value="1">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                    @endforeach
                </form>
            </div>

            <!-- Legend / Info -->
            <div class="mt-12 pt-8 border-t border-gray-lighter">
                <h5 class="text-[10px] font-black text-gray uppercase tracking-widest mb-4">Tier Mapping Reference</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">OTP Endpoints</div>
                        <div class="text-gray font-medium">/send-otp, /verify-otp, /resend-otp (5/min default)</div>
                    </div>
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">Authentication</div>
                        <div class="text-gray font-medium">/signup (60/min default)</div>
                    </div>
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">General Public API</div>
                        <div class="text-gray font-medium">/live/now, /astrologers, /blogs, /remedies (60/min default)</div>
                    </div>
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">Tiered/Mutation</div>
                        <div class="text-gray font-medium">/wallet, /reviews, /comments, /plans (30/min default)</div>
                    </div>
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">Live Watch</div>
                        <div class="text-gray font-medium">/live/{id}/watch (100/min default)</div>
                    </div>
                    <div class="bg-light/30 p-4 rounded-xl">
                        <div class="font-black text-dark uppercase tracking-widest mb-1">General API</div>
                        <div class="text-gray font-medium">Authenticated endpoints not in other tiers (120/min default)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection