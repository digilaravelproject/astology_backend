@extends('admin.layouts.app')

@section('title', 'Compose Push Notification')

@section('content')
<div class="space-y-8" x-data="broadcastComposer()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-lighter pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.push-notifications.index') }}" class="text-xs font-semibold text-text-muted hover:text-primary transition-colors flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Back to Campaigns
                </a>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight flex items-center gap-3">
                <i class="fas fa-paper-plane text-primary"></i>
                Compose Push Notification Broadcast
            </h1>
            <p class="text-sm text-text-secondary mt-1">
                Send rich notifications with deep links and images to your users & astrologers.
            </p>
        </div>
    </div>

    <!-- Main Grid: Composer Form on Left, Live Mobile Preview on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Form Section -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-xs">
            <form action="{{ route('admin.push-notifications.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Target Audience -->
                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-3">
                        1. Target Audience <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                               :class="targetType === 'all' ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs' : 'border-gray-200 text-text-secondary hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="all" x-model="targetType" class="sr-only">
                            <i class="fas fa-users text-lg mb-1 block"></i>
                            <span class="text-xs">All Users</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                                :class="targetType === 'users' ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs' : 'border-gray-200 text-text-secondary hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="users" x-model="targetType" class="sr-only">
                            <i class="fas fa-user text-lg mb-1 block"></i>
                            <span class="text-xs">Consumers Only</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                                :class="targetType === 'astrologers' ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs' : 'border-gray-200 text-text-secondary hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="astrologers" x-model="targetType" class="sr-only">
                            <i class="fas fa-star-and-crescent text-lg mb-1 block"></i>
                            <span class="text-xs">Astrologers Only</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                                :class="targetType === 'single_user' ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs' : 'border-gray-200 text-text-secondary hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="single_user" x-model="targetType" class="sr-only">
                            <i class="fas fa-user-tag text-lg mb-1 block"></i>
                            <span class="text-xs">Specific User</span>
                        </label>
                    </div>
                </div>

                <!-- Single User Search (Conditional) -->
                <div x-show="targetType === 'single_user'" x-cloak class="p-4 bg-light/30 rounded-xl border border-gray-200 space-y-3">
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider">
                        Search User (Name / Phone / Email)
                    </label>
                    <div class="relative">
                        <input type="text" x-model="userQuery" @input.debounce.300ms="searchUsers()" placeholder="Type at least 2 characters..."
                               class="w-full px-4 py-2.5 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        
                        <input type="hidden" name="target_user_id" :value="selectedUser ? selectedUser.id : ''">

                        <!-- Dropdown Search Results -->
                        <div x-show="searchResults.length > 0" @click.away="searchResults = []"
                             class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                            <template x-for="u in searchResults" :key="u.id">
                                <div @click="selectUser(u)" class="p-3 hover:bg-light/40 cursor-pointer border-b border-gray-100 last:border-0 flex items-center justify-between text-xs">
                                    <div>
                                        <div class="font-bold text-text-primary" x-text="u.name"></div>
                                        <div class="text-[11px] text-text-muted" x-text="u.phone + ' | ' + (u.email || 'No email')"></div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                          :class="u.user_type === 'astrologer' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                          x-text="u.user_type"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <template x-if="selectedUser">
                        <div class="flex items-center justify-between bg-primary/10 border border-primary/20 p-2.5 rounded-lg text-xs">
                            <div class="text-primary font-semibold flex items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <span>Selected: <strong x-text="selectedUser.name"></strong> (<span x-text="selectedUser.phone"></span>)</span>
                            </div>
                            <button type="button" @click="clearUser()" class="text-text-muted hover:text-rose-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Notification Content -->
                <div class="space-y-4 pt-4 border-t border-gray-100">
                    <div class="text-xs font-bold text-text-secondary uppercase tracking-wider">2. Notification Content</div>

                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Title <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" x-model="title" required maxlength="100"
                               placeholder="e.g. Special Weekend Offer: 50% Off on First Call!"
                               class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <div class="flex justify-between text-[11px] text-text-muted mt-1">
                            <span>Keep it punchy &amp; concise</span>
                            <span x-text="title.length + '/100'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Message Body <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="body" x-model="body" rows="3" required maxlength="255"
                                  placeholder="e.g. Consult with certified Vedic astrologers right now and discover what planets say about your upcoming week..."
                                  class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"></textarea>
                        <div class="flex justify-between text-[11px] text-text-muted mt-1">
                            <span>Appears in notification banner</span>
                            <span x-text="body.length + '/255'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Banner Image URL (Optional)
                        </label>
                        <input type="url" name="image_url" x-model="imageUrl"
                               placeholder="https://your-domain.com/banners/promo.png"
                               class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-text-muted mt-1 block">Full-width image for expandable Android/iOS push cards (16:9 ratio recommended)</span>
                    </div>
                </div>

                <!-- Deep Linking & Metadata -->
                <div class="space-y-4 pt-4 border-t border-gray-100">
                    <div class="text-xs font-bold text-text-secondary uppercase tracking-wider">3. Action & Deep Linking (Optional)</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                                App Screen Preset (Deep Link)
                            </label>
                            <select x-model="presetAction" @change="applyPreset()" class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                <option value="">Select App Screen...</option>
                                <option value="NAV_WALLET_TOPUP">Open Wallet Recharge Screen</option>
                                <option value="NAV_ASTROLOGER_LIST">Open Top Astrologers List</option>
                                <option value="NAV_LIVE_SESSIONS">Open Live Streaming Sessions</option>
                                <option value="NAV_KUNDLI_REPORT">Open Kundli Generator</option>
                                <option value="CUSTOM">Custom URL / Path</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                                Action Deep Link / Path
                            </label>
                            <input type="text" name="action_url" x-model="actionUrl"
                                   placeholder="app://astology/wallet"
                                   class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('admin.push-notifications.index') }}" class="text-xs font-bold text-text-muted hover:text-text-primary">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-8 py-3.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 flex items-center gap-2 cursor-pointer">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Broadcast Now</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Smartphone Push Simulator Preview -->
        <div class="lg:col-span-5 sticky top-8">
            <div class="bg-gradient-to-b from-gray-900 via-gray-900 to-black p-4 rounded-[40px] shadow-2xl border-4 border-gray-800 max-w-[340px] mx-auto text-white">
                <!-- Phone Top Notch -->
                <div class="flex justify-between items-center px-4 pt-2 pb-6 text-[10px] text-gray-400 font-semibold">
                    <span>09:41</span>
                    <div class="w-20 h-4 bg-black rounded-full mx-auto"></div>
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-signal"></i>
                        <i class="fas fa-wifi"></i>
                        <i class="fas fa-battery-full"></i>
                    </div>
                </div>

                <!-- Lockscreen Date & Clock -->
                <div class="text-center my-4">
                    <div class="text-xs text-gray-300 font-medium">Tuesday, August 18</div>
                    <div class="text-5xl font-light tracking-tight my-1">09:41</div>
                </div>

                <!-- Push Notification Card Simulated -->
                <div class="bg-white/95 backdrop-blur-md text-gray-900 rounded-2xl p-3.5 shadow-xl border border-white/40 my-6 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-md bg-primary text-white flex items-center justify-center text-[10px]">
                                <i class="fas fa-star-and-crescent"></i>
                            </div>
                            <span class="text-[11px] font-bold tracking-tight text-gray-800">{{ \App\Models\Setting::get('app_name', 'Astology') }}</span>
                            <span class="text-[9px] text-gray-400">• now</span>
                        </div>
                        <i class="fas fa-chevron-down text-[9px] text-gray-400"></i>
                    </div>

                    <div class="text-xs font-bold text-gray-900 line-clamp-1" x-text="title || 'Notification Title Here'"></div>
                    <div class="text-[11px] text-gray-600 line-clamp-2 mt-0.5" x-text="body || 'Your message preview will appear right here as you compose...'"></div>

                    <!-- Expandable Image Preview -->
                    <template x-if="imageUrl">
                        <div class="mt-2.5 rounded-lg overflow-hidden border border-gray-100 max-h-32 bg-gray-100">
                            <img :src="imageUrl" class="w-full h-full object-cover" @error="$el.style.display='none'">
                        </div>
                    </template>
                </div>

                <!-- Bottom Lockscreen Shortcuts -->
                <div class="flex justify-between items-center px-4 pt-12 pb-4 text-gray-400 text-sm">
                    <div class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center">
                        <i class="fas fa-flashlight"></i>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <!-- Home Bar -->
                <div class="w-32 h-1 bg-white/50 rounded-full mx-auto mt-2"></div>
            </div>
            <p class="text-center text-xs text-text-muted mt-3">Live mobile lock-screen preview simulator</p>
        </div>
    </div>
</div>

<script>
function broadcastComposer() {
    return {
        targetType: 'all',
        title: 'Special Live Consultation Offer 🌟',
        body: 'Connect with certified astrologers right now and discover what your birth chart says!',
        imageUrl: '',
        presetAction: '',
        actionUrl: '',
        userQuery: '',
        searchResults: [],
        selectedUser: null,
        applyPreset() {
            if (!this.presetAction || this.presetAction === 'CUSTOM') return;
            const map = {
                'NAV_WALLET_TOPUP': 'app://astology/wallet/topup',
                'NAV_ASTROLOGER_LIST': 'app://astology/astrologers',
                'NAV_LIVE_SESSIONS': 'app://astology/live',
                'NAV_KUNDLI_REPORT': 'app://astology/kundli'
            };
            this.actionUrl = map[this.presetAction] || '';
        },
        async searchUsers() {
            if (this.userQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            try {
                const res = await fetch(`{{ route('admin.push-notifications.search-users') }}?q=${encodeURIComponent(this.userQuery)}`);
                const data = await res.json();
                this.searchResults = data.users || [];
            } catch (err) {
                this.searchResults = [];
            }
        },
        selectUser(user) {
            this.selectedUser = user;
            this.searchResults = [];
            this.userQuery = user.name + ' (' + user.phone + ')';
        },
        clearUser() {
            this.selectedUser = null;
            this.userQuery = '';
            this.searchResults = [];
        }
    };
}
</script>
@endsection
