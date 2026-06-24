@extends('admin.layouts.app')

@section('content')
<div x-data="{ 
    tab: 'general',
    showAddModal: false,
    showEditModal: false,
    currentOperator: { id: '', name: '', email: '', phone: '', role: 'admin', is_active: 1 }
}">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight">Website Settings & Control</h1>
            <p class="text-sm text-text-muted mt-1">Manage website settings, payment gateways, commission logic, protection controls, and team accounts.</p>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden min-h-[600px] flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <div class="lg:w-72 bg-light/20 border-r border-gray-200 p-6 space-y-1">
            <button @click="tab = 'general'" :class="tab === 'general' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-desktop w-5 text-center"></i> General Settings
            </button>
            <button @click="tab = 'commission'" :class="tab === 'commission' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-percentage w-5 text-center"></i> Commission Rates
            </button>
            <button @click="tab = 'wallet'" :class="tab === 'wallet' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-wallet w-5 text-center"></i> Wallet & Money Rules
            </button>
            <button @click="tab = 'payment'" :class="tab === 'payment' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-credit-card w-5 text-center"></i> Payment Gateway
            </button>
            <button @click="tab = 'astro'" :class="tab === 'astro' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-user-tie w-5 text-center"></i> Astrologer Pricing
            </button>
            <button @click="tab = 'security'" :class="tab === 'security' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-shield-alt w-5 text-center"></i> Website Protection
            </button>
            <button @click="tab = 'admin'" :class="tab === 'admin' ? 'bg-primary/10 text-primary border-primary' : 'text-text-secondary hover:bg-light border-transparent'" class="w-full flex items-center gap-3 px-5 py-3.5 rounded-xl text-sm font-semibold transition-all border-l-4">
                <i class="fas fa-users-cog w-5 text-center"></i> Team & Admins (RBAC)
            </button>
        </div>

        <!-- Content Area -->
        <div class="flex-1 p-8 lg:p-10">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf

                <!-- General Settings -->
                <div x-show="tab === 'general'" class="space-y-6">
                    <h3 class="text-lg font-bold text-text-primary border-b pb-3">General Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Website / App Name</label>
                            <input type="text" name="app_name" value="{{ $settings['app_name'] }}" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Support Email Address</label>
                            <input type="email" name="support_email" value="{{ $settings['support_email'] }}" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Website Description for Google (SEO Description)</label>
                        <textarea name="seo_meta_description" rows="3" class="w-full border border-gray-300 px-4 py-3 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">{{ $settings['seo_meta_description'] }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <!-- Favicon -->
                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-white rounded-xl mb-4 flex items-center justify-center border shadow-xs overflow-hidden">
                                @if($settings['favicon_path'])
                                    <img src="{{ $settings['favicon_path'] }}" class="object-contain max-h-full max-w-full">
                                @else
                                    <i class="fas fa-image text-2xl text-gray-400"></i>
                                @endif
                            </div>
                            <div class="text-xs font-bold text-text-primary uppercase mb-1">Tab Icon (Favicon)</div>
                            <div class="text-[10px] text-text-muted mb-3">Small icon on browser tab (ICO/PNG)</div>
                            <input type="file" name="favicon" class="hidden" id="favicon_input">
                            <button type="button" onclick="document.getElementById('favicon_input').click()" class="px-4 py-2 bg-light border text-xs font-bold text-text-secondary rounded-lg hover:bg-gray-100 transition-all">Upload Icon</button>
                        </div>

                        <!-- Logo -->
                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-white rounded-xl mb-4 flex items-center justify-center border shadow-xs overflow-hidden">
                                @if($settings['logo_path'])
                                    <img src="{{ $settings['logo_path'] }}" class="object-contain max-h-full max-w-full">
                                @else
                                    <i class="fas fa-signature text-2xl text-gray-400"></i>
                                @endif
                            </div>
                            <div class="text-xs font-bold text-text-primary uppercase mb-1">Website Logo</div>
                            <div class="text-[10px] text-text-muted mb-3">Main logo image (PNG/SVG)</div>
                            <input type="file" name="logo" class="hidden" id="logo_input">
                            <button type="button" onclick="document.getElementById('logo_input').click()" class="px-4 py-2 bg-light border text-xs font-bold text-text-secondary rounded-lg hover:bg-gray-100 transition-all">Upload Logo</button>
                        </div>

                        <!-- Social Preview -->
                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl flex flex-col items-center text-center">
                            <div class="w-16 h-16 bg-white rounded-xl mb-4 flex items-center justify-center border shadow-xs overflow-hidden">
                                @if($settings['social_preview_path'])
                                    <img src="{{ $settings['social_preview_path'] }}" class="object-contain max-h-full max-w-full">
                                @else
                                    <i class="fas fa-share-alt text-2xl text-gray-400"></i>
                                @endif
                            </div>
                            <div class="text-xs font-bold text-text-primary uppercase mb-1">Share Preview Image</div>
                            <div class="text-[10px] text-text-muted mb-3">Preview image shown when link shared</div>
                            <input type="file" name="social_preview" class="hidden" id="social_preview_input">
                            <button type="button" onclick="document.getElementById('social_preview_input').click()" class="px-4 py-2 bg-light border text-xs font-bold text-text-secondary rounded-lg hover:bg-gray-100 transition-all">Upload Image</button>
                        </div>
                    </div>
                </div>

                <!-- Commission Rates -->
                <div x-show="tab === 'commission'" class="space-y-6">
                    <h3 class="text-lg font-bold text-text-primary border-b pb-3">Commission Rules</h3>
                    
                    <div class="p-6 bg-primary/5 rounded-2xl border border-primary/10 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div>
                            <h4 class="text-base font-bold text-text-primary mb-1">Standard Platform Fee</h4>
                            <p class="text-xs text-text-muted">The percentage the platform keeps from chat, call, and video session earnings.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="number" name="global_commission_percentage" value="{{ $settings['global_commission_percentage'] }}" min="0" max="100" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center text-base font-bold">
                            <span class="text-lg font-bold text-text-secondary">%</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-light/20 border border-gray-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="block text-sm font-bold text-text-primary">Shop/Marketplace Sales Commission</span>
                                <span class="block text-xs text-text-muted mt-0.5">Platform share for Selling physical items</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" name="ecommerce_commission_percentage" value="{{ $settings['ecommerce_commission_percentage'] }}" min="0" max="100" class="w-20 border border-gray-300 px-2 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-sm font-bold text-text-secondary">%</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/20 border border-gray-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="block text-sm font-bold text-text-primary">Premium Subscription Plan Commission</span>
                                <span class="block text-xs text-text-muted mt-0.5">Platform share for yearly subscription packages</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" name="premium_yearly_commission_percentage" value="{{ $settings['premium_yearly_commission_percentage'] }}" min="0" max="100" class="w-20 border border-gray-300 px-2 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-sm font-bold text-text-secondary">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wallet Rules -->
                <div x-show="tab === 'wallet'" class="space-y-6">
                    <h3 class="text-lg font-bold text-text-primary border-b pb-3">Wallet & Money Rules</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Min. Wallet Deposit</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" name="min_wallet_recharge" value="{{ $settings['min_wallet_recharge'] }}" min="1" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>
                            <span class="block text-[10px] text-text-muted mt-2">Minimum amount user can add to wallet</span>
                        </div>

                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Max. Wallet Balance</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" name="max_wallet_balance" value="{{ $settings['max_wallet_balance'] }}" min="1" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>
                            <span class="block text-[10px] text-text-muted mt-2">Maximum balance user wallet can hold</span>
                        </div>

                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Min. Withdrawal Limit</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" name="min_withdrawal_amount" value="{{ $settings['min_withdrawal_amount'] }}" min="1" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>
                            <span class="block text-[10px] text-text-muted mt-2">Minimum wallet amount required to request payout</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Gateway -->
                <div x-show="tab === 'payment'" class="space-y-6">
                    <h3 class="text-lg font-bold text-text-primary border-b pb-3">Payment Gateways</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Gateway Mode</label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center gap-2 text-sm font-bold text-text-secondary cursor-pointer">
                                    <input type="radio" name="payment_gateway_mode" value="sandbox" {{ $settings['payment_gateway_mode'] === 'sandbox' ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                    Test/Sandbox
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-text-secondary cursor-pointer">
                                    <input type="radio" name="payment_gateway_mode" value="live" {{ $settings['payment_gateway_mode'] === 'live' ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                    Live/Production
                                </label>
                            </div>
                        </div>

                        <div class="p-6 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">Active Gateways</label>
                            <div class="flex gap-6 mt-2">
                                @php
                                    $activeGateways = is_string($settings['active_gateways']) ? json_decode($settings['active_gateways'], true) : $settings['active_gateways'];
                                    if(!is_array($activeGateways)) $activeGateways = [];
                                @endphp
                                <label class="flex items-center gap-2 text-sm font-bold text-text-secondary cursor-pointer">
                                    <input type="checkbox" name="active_gateways[]" value="razorpay" {{ in_array('razorpay', $activeGateways) ? 'checked' : '' }} class="rounded text-primary focus:ring-primary">
                                    Razorpay
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-text-secondary cursor-pointer">
                                    <input type="checkbox" name="active_gateways[]" value="stripe" {{ in_array('stripe', $activeGateways) ? 'checked' : '' }} class="rounded text-primary focus:ring-primary">
                                    Stripe
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 pt-4">
                        <div class="p-6 border border-gray-200 rounded-2xl space-y-4">
                            <div class="flex items-center gap-3 border-b pb-2">
                                <i class="fab fa-cc-amazon-pay text-primary text-xl"></i>
                                <h4 class="text-sm font-extrabold text-text-primary uppercase">Razorpay Credentials</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-text-secondary mb-1">Razorpay Key</label>
                                    <input type="text" name="razorpay_key" value="{{ $settings['razorpay_key'] }}" class="w-full border border-gray-300 px-4 py-2.5 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-text-secondary mb-1">Razorpay Secret</label>
                                    <input type="password" name="razorpay_secret" value="{{ $settings['razorpay_secret'] }}" class="w-full border border-gray-300 px-4 py-2.5 rounded-xl text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="p-6 border border-gray-200 rounded-2xl space-y-4">
                            <div class="flex items-center gap-3 border-b pb-2">
                                <i class="fab fa-stripe text-indigo-600 text-xl"></i>
                                <h4 class="text-sm font-extrabold text-text-primary uppercase">Stripe Credentials</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-text-secondary mb-1">Stripe Key</label>
                                    <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] }}" class="w-full border border-gray-300 px-4 py-2.5 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-text-secondary mb-1">Stripe Secret</label>
                                    <input type="password" name="stripe_secret" value="{{ $settings['stripe_secret'] }}" class="w-full border border-gray-300 px-4 py-2.5 rounded-xl text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Astrologer Default Pricing -->
                <div x-show="tab === 'astro'" class="space-y-6">
                    <h3 class="text-lg font-bold text-text-primary border-b pb-3">Astrologer Default Rates</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Default Chat Rate</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" step="0.01" name="default_chat_rate_per_minute" value="{{ $settings['default_chat_rate_per_minute'] }}" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold">
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Default Voice Call Rate</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" step="0.01" name="default_call_rate_per_minute" value="{{ $settings['default_call_rate_per_minute'] }}" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold">
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Default Video Call Rate</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" step="0.01" name="default_video_call_rate_per_minute" value="{{ $settings['default_video_call_rate_per_minute'] }}" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold">
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Default Promo Rate</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-text-secondary">₹</span>
                                <input type="number" step="0.01" name="default_po_at_5_rate_per_minute" value="{{ $settings['default_po_at_5_rate_per_minute'] }}" class="w-full border border-gray-300 pl-8 pr-4 py-3 rounded-xl text-sm font-bold">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Website Protection (Rate Limits) -->
                <div x-show="tab === 'security'" class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-lg font-bold text-text-primary">Website Throttling & Protection</h3>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="rate_limit_enabled" value="0">
                            <input type="checkbox" name="rate_limit_enabled" value="1" {{ $settings['rate_limit_enabled'] ? 'checked' : '' }} class="w-10 h-6 appearance-none bg-gray-300 rounded-full relative cursor-pointer after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-all checked:after:translate-x-4 checked:bg-primary border-2 border-transparent">
                            <span class="text-xs font-bold text-text-secondary uppercase">Master Switch</span>
                        </div>
                    </div>

                    <p class="text-xs text-text-muted mt-1">Specify request limits to block bot attacks. Set value to 0 to disable throttling.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">OTP Limits</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_otp" value="{{ $settings['rate_limit_otp'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">requests per minute per IP</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">Login / Register Attempts</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_auth" value="{{ $settings['rate_limit_auth'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">attempts per minute per IP</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">Public Pages</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_general" value="{{ $settings['rate_limit_general'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">requests per minute per user/IP</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">Transactions & Mutations</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_tiered" value="{{ $settings['rate_limit_tiered'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">requests per minute per user/IP</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">Live Streams</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_live_watch" value="{{ $settings['rate_limit_live_watch'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">requests per minute per user/IP</span>
                            </div>
                        </div>

                        <div class="p-5 bg-light/10 border border-gray-200 rounded-2xl">
                            <label class="block text-xs font-bold text-text-secondary mb-2">General API Requests</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="rate_limit_api" value="{{ $settings['rate_limit_api'] }}" class="w-24 border border-gray-300 px-3 py-2 rounded-xl text-center font-bold text-sm">
                                <span class="text-xs text-text-muted">requests per minute per user/IP</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button for Settings Form -->
                <div x-show="tab !== 'admin'" class="pt-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-8 py-3.5 bg-primary text-white text-sm font-bold uppercase rounded-xl hover:bg-primary-dark transition-all shadow-md flex items-center gap-2">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>

            <!-- Team & Admins (RBAC) -->
            <div x-show="tab === 'admin'" class="space-y-6">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-text-primary">Admin Accounts & Team Members</h3>
                        <p class="text-xs text-text-muted mt-0.5">Manage operator profiles and their access roles.</p>
                    </div>
                    <button @click="showAddModal = true" class="px-5 py-2.5 bg-primary text-white text-xs font-bold uppercase rounded-xl hover:bg-primary-dark transition-all shadow-sm flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Add Team Member
                    </button>
                </div>

                <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-xs">
                    <table class="w-full text-left">
                        <thead class="bg-light/40 border-b">
                            <tr>
                                <th class="px-6 py-3.5 text-xs font-bold text-text-secondary uppercase">Member Profile</th>
                                <th class="px-6 py-3.5 text-xs font-bold text-text-secondary uppercase">Access Role</th>
                                <th class="px-6 py-3.5 text-xs font-bold text-text-secondary uppercase">Status</th>
                                <th class="px-6 py-3.5 text-xs font-bold text-text-secondary uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">
                            @foreach($operators as $operator)
                            <tr class="hover:bg-light/10 transition-all">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-text-primary">{{ $operator->name }}</div>
                                    <div class="text-xs text-text-muted">{{ $operator->email }}</div>
                                    @if($operator->phone)
                                        <div class="text-[10px] text-text-muted mt-0.5">{{ $operator->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($operator->role === 'super_admin')
                                        <span class="px-3 py-1 bg-primary/10 text-primary text-[10px] font-bold uppercase rounded-full">Super Admin</span>
                                    @else
                                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase rounded-full">Standard Admin</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($operator->is_active)
                                        <span class="px-2.5 py-0.5 bg-success/10 text-success-dark text-[10px] font-semibold rounded-md">Active</span>
                                    @else
                                        <span class="px-2.5 py-0.5 bg-danger/10 text-danger-dark text-[10px] font-semibold rounded-md">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="
                                        currentOperator = {
                                            id: '{{ $operator->id }}',
                                            name: '{{ addslashes($operator->name) }}',
                                            email: '{{ addslashes($operator->email) }}',
                                            phone: '{{ addslashes($operator->phone ?? '') }}',
                                            role: '{{ $operator->role }}',
                                            is_active: {{ $operator->is_active ? 1 : 0 }}
                                        };
                                        showEditModal = true;
                                    " class="w-8 h-8 rounded-lg border text-text-secondary hover:text-primary hover:border-primary transition-all inline-flex items-center justify-center mr-1">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    @if(Auth::guard('admin')->id() !== $operator->id)
                                    <form action="{{ route('admin.settings.operators.destroy', $operator->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this team member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg border text-text-secondary hover:text-danger hover:border-danger transition-all inline-flex items-center justify-center">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (Add / Edit Operators) -->
    <!-- Add Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="showAddModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden border">
            <div class="px-6 py-4 bg-light/50 border-b flex items-center justify-between">
                <h4 class="text-sm font-bold text-text-primary uppercase">Add Team Member</h4>
                <button @click="showAddModal = false" class="text-lg opacity-70 hover:opacity-100">&times;</button>
            </div>
            <form action="{{ route('admin.settings.operators.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Email Address</label>
                    <input type="email" name="email" required class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Mobile Phone (Optional)</label>
                    <input type="text" name="phone" class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Access Level Role</label>
                    <select name="role" required class="w-full border px-3 py-2 rounded-xl text-sm">
                        <option value="admin">Standard Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Status</label>
                    <select name="is_active" required class="w-full border px-3 py-2 rounded-xl text-sm">
                        <option value="1">Active / Enable</option>
                        <option value="0">Inactive / Disable</option>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 border text-xs font-bold text-text-secondary rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-dark">Add Member</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="showEditModal = false"></div>
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative z-10 overflow-hidden border">
            <div class="px-6 py-4 bg-light/50 border-b flex items-center justify-between">
                <h4 class="text-sm font-bold text-text-primary uppercase">Edit Team Member</h4>
                <button @click="showEditModal = false" class="text-lg opacity-70 hover:opacity-100">&times;</button>
            </div>
            <form :action="'{{ url('/admin/settings/operators') }}/' + currentOperator.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Full Name</label>
                    <input type="text" name="name" x-model="currentOperator.name" required class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Email Address</label>
                    <input type="email" name="email" x-model="currentOperator.email" required class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Mobile Phone (Optional)</label>
                    <input type="text" name="phone" x-model="currentOperator.phone" class="w-full border px-3 py-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Change Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="w-full border px-3 py-2 rounded-xl text-sm" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Access Level Role</label>
                    <select name="role" x-model="currentOperator.role" required class="w-full border px-3 py-2 rounded-xl text-sm">
                        <option value="admin">Standard Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-text-secondary mb-1">Status</label>
                    <select name="is_active" x-model="currentOperator.is_active" required class="w-full border px-3 py-2 rounded-xl text-sm">
                        <option value="1">Active / Enable</option>
                        <option value="0">Inactive / Disable</option>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 border text-xs font-bold text-text-secondary rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-dark">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
