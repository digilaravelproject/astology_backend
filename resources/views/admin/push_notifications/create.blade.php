@extends('admin.layouts.app')

@section('title', 'Compose Push Notification')

@section('content')
<div class="space-y-6" x-data="broadcastComposer()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.push-notifications.index') }}" class="text-xs font-semibold text-gray-500 hover:text-primary transition-colors flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Back to Campaigns
                </a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-paper-plane text-primary"></i>
                Compose Push Notification Broadcast
            </h1>
        </div>
    </div>

    <!-- Main Grid: Composer Form on Left, Live Mobile Preview on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Form Section -->
        <div class="lg:col-span-7 bg-white dark:bg-card-dark rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
            <form action="{{ route('admin.push-notifications.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Target Audience -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                        1. Target Audience <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                               :class="targetType === 'all' ? 'border-primary bg-primary/5 text-primary font-bold shadow-sm' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'">
                            <input type="radio" name="target_type" value="all" x-model="targetType" class="sr-only">
                            <i class="fas fa-users text-lg mb-1 block"></i>
                            <span class="text-xs">All Users</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                               :class="targetType === 'users' ? 'border-primary bg-primary/5 text-primary font-bold shadow-sm' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'">
                            <input type="radio" name="target_type" value="users" x-model="targetType" class="sr-only">
                            <i class="fas fa-user text-lg mb-1 block"></i>
                            <span class="text-xs">Consumers Only</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                               :class="targetType === 'astrologers' ? 'border-primary bg-primary/5 text-primary font-bold shadow-sm' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'">
                            <input type="radio" name="target_type" value="astrologers" x-model="targetType" class="sr-only">
                            <i class="fas fa-star-and-crescent text-lg mb-1 block"></i>
                            <span class="text-xs">Astrologers Only</span>
                        </label>

                        <label class="cursor-pointer border rounded-xl p-3 text-center transition-all"
                               :class="targetType === 'single_user' ? 'border-primary bg-primary/5 text-primary font-bold shadow-sm' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'">
                            <input type="radio" name="target_type" value="single_user" x-model="targetType" class="sr-only">
                            <i class="fas fa-user-tag text-lg mb-1 block"></i>
                            <span class="text-xs">Specific User</span>
                        </label>
                    </div>
                </div>

                <!-- Single User Search (Conditional) -->
                <div x-show="targetType === 'single_user'" x-cloak class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Search User (Name / Phone / Email)
                    </label>
                    <div class="relative">
                        <input type="text" x-model="userQuery" @input.debounce.300ms="searchUsers()" placeholder="Type at least 2 characters..."
                               class="w-full px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/20">
                        
                        <input type="hidden" name="target_user_id" :value="selectedUser ? selectedUser.id : ''">

                        <!-- Dropdown Search Results -->
                        <div x-show="searchResults.length > 0" @click.away="searchResults = []"
                             class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-card-dark border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                            <template x-for="u in searchResults" :key="u.id">
                                <div @click="selectUser(u)" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer border-b border-gray-100 dark:border-gray-800 last:border-0 flex items-center justify-between text-xs">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="u.name"></div>
                                        <div class="text-[11px] text-gray-400" x-text="u.phone + ' | ' + (u.email || 'No email')"></div>
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
                            <button type="button" @click="clearUser()" class="text-gray-400 hover:text-rose-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Message Content -->
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        2. Notification Message <span class="text-rose-500">*</span>
                    </label>

                    <div>
                        <input type="text" name="title" x-model="title" required maxlength="191"
                               placeholder="Notification Title (e.g. 50% Off on Vedic Readings today!)"
                               class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    <div>
                        <textarea name="body" x-model="body" rows="3" required maxlength="1000"
                                  placeholder="Write your push notification body copy here..."
                                  class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"></textarea>
                    </div>
                </div>

                <!-- Banner Image URL -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        3. Rich Banner Image URL (Optional)
                    </label>
                    <input type="url" name="image_url" x-model="imageUrl"
                           placeholder="https://astologyapp.com/storage/promotions/banner.jpg"
                           class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    <span class="text-[11px] text-gray-400 mt-1 block">Supports HTTPS URLs for image rich expansion in Android & iOS trays</span>
                </div>

                <!-- Click Action / Deep Link -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        4. Action / Deep Link
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">
                        <select @change="if($event.target.value) clickAction = $event.target.value" class="px-4 py-2.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none">
                            <option value="">Choose preset destination...</option>
                            <option value="FLUTTER_NOTIFICATION_CLICK">Default App Home</option>
                            <option value="NAV_WALLET_TOPUP">Wallet Top-up Screen</option>
                            <option value="NAV_ASTROLOGER_LIST">Top Astrologers List</option>
                            <option value="NAV_LIVE_SESSIONS">Live Streams Page</option>
                            <option value="NAV_KUNDLI_REPORT">Kundli Report Screen</option>
                        </select>
                        <input type="text" name="click_action" x-model="clickAction" placeholder="Custom click_action or URI route"
                               class="px-4 py-2.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none">
                    </div>
                </div>

                <!-- Custom Data Payload (JSON) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        5. Custom Key-Value Data Payload (Optional JSON)
                    </label>
                    <textarea name="custom_data" x-model="customData" rows="2"
                              placeholder='{"promo_code": "ASTRO50", "discount_pct": "50"}'
                              class="w-full px-4 py-2 text-xs font-mono bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <a href="{{ route('admin.push-notifications.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700">Cancel</a>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-primary to-orange-600 hover:from-primary-dark hover:to-orange-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-lg shadow-primary/25 transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Dispatch Push Broadcast</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Phone Simulator on Right -->
        <div class="lg:col-span-5 sticky top-6">
            <div class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 text-center">
                <i class="fas fa-mobile-alt mr-1"></i> Live Mobile Push Notification Preview
            </div>

            <!-- Phone Frame -->
            <div class="w-full max-w-[340px] mx-auto bg-slate-950 rounded-[44px] p-3.5 shadow-2xl border-[4px] border-slate-800 relative">
                <!-- Notch -->
                <div class="w-28 h-4 bg-slate-900 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-800 mr-2"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-700"></div>
                </div>

                <!-- Lock Screen Background / Wallpaper -->
                <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 rounded-[32px] p-4 min-h-[460px] flex flex-col justify-between relative overflow-hidden">
                    <!-- Top Clock -->
                    <div class="text-center pt-2 text-white/80">
                        <div class="text-4xl font-extralight tracking-tight">12:30</div>
                        <div class="text-[11px] font-medium text-white/50 mt-0.5">Tuesday, 18 August</div>
                    </div>

                    <!-- Push Notification Banner Card -->
                    <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-3.5 rounded-2xl shadow-xl border border-white/20 dark:border-white/10 my-auto transition-all">
                        <!-- App Header -->
                        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 mb-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-4 h-4 rounded-full bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center text-[8px] text-white font-bold">
                                    <i class="fas fa-sun"></i>
                                </div>
                                <span class="font-bold text-gray-800 dark:text-gray-200">ASTOLOGY</span>
                            </div>
                            <span class="text-[10px]">now</span>
                        </div>

                        <!-- Notification Title -->
                        <div class="text-xs font-bold text-gray-900 dark:text-white" x-text="title || 'Your Notification Title Here'"></div>

                        <!-- Notification Body -->
                        <div class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-3" x-text="body || 'The message body you write will dynamically appear here just like on a user\'s lock screen.'"></div>

                        <!-- Image Banner Preview -->
                        <template x-if="imageUrl">
                            <div class="mt-2.5 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 max-h-32">
                                <img :src="imageUrl" alt="Preview" class="w-full h-full object-cover" @error="$el.style.display='none'">
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Lock Screen Bar -->
                    <div class="text-center pb-2">
                        <div class="w-24 h-1 bg-white/30 rounded-full mx-auto"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function broadcastComposer() {
    return {
        targetType: 'all',
        title: '',
        body: '',
        imageUrl: '',
        clickAction: 'FLUTTER_NOTIFICATION_CLICK',
        customData: '',
        userQuery: '',
        searchResults: [],
        selectedUser: null,
        async searchUsers() {
            if (this.userQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            try {
                const res = await fetch(`{{ route('admin.push-notifications.search-users') }}?q=${encodeURIComponent(this.userQuery)}`);
                this.searchResults = await res.json();
            } catch (e) {
                this.searchResults = [];
            }
        },
        selectUser(user) {
            this.selectedUser = user;
            this.userQuery = `${user.name} (${user.phone})`;
            this.searchResults = [];
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
