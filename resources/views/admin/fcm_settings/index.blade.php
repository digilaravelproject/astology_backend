@extends('admin.layouts.app')

@section('title', 'Firebase Push Notification Settings')

@section('content')
<div class="space-y-6" x-data="fcmSettingsManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-fire text-amber-500"></i>
                Firebase Push Notification Settings (HTTP v1)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Configure Firebase Cloud Messaging credentials and notification delivery channels for Android & iOS devices.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="testConnection()" :disabled="testing"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-orange-500/20 transition-all duration-200 disabled:opacity-50">
                <i class="fas" :class="testing ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                <span x-text="testing ? 'Testing Handshake...' : 'Test Connection'"></span>
            </button>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl flex items-center gap-3 text-emerald-800 dark:text-emerald-300">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl flex items-center gap-3 text-rose-800 dark:text-rose-300">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Test Connection Result Live Card -->
    <div x-show="testResult !== null" x-cloak x-transition
         class="p-5 rounded-2xl border transition-all"
         :class="testResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-800'">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                     :class="testResult?.success ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'">
                    <i class="fas" :class="testResult?.success ? 'fa-check' : 'fa-times'"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold" :class="testResult?.success ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200'" x-text="testResult?.message"></h3>
                    <template x-if="testResult?.details">
                        <div class="mt-2 text-xs font-mono bg-black/10 dark:bg-black/40 p-3 rounded-lg space-y-1">
                            <div><strong>Project ID:</strong> <span x-text="testResult?.details?.project_id || 'N/A'"></span></div>
                            <div><strong>Client Email:</strong> <span x-text="testResult?.details?.client_email || 'N/A'"></span></div>
                            <div><strong>Latency:</strong> <span x-text="(testResult?.details?.duration_ms || 0) + ' ms'"></span></div>
                        </div>
                    </template>
                </div>
            </div>
            <button @click="testResult = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- FCM Status -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Connection Status</span>
                @if($isConfigured)
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active & Connected
                    </span>
                @else
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Credentials Missing
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <div class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $projectId ?? 'No Project Configured' }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Google Cloud / Firebase Project ID</p>
            </div>
        </div>

        <!-- API Engine -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">API Protocol</span>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                    HTTP v1 (OAuth2 RS256)
                </span>
            </div>
            <div class="mt-4">
                <div class="text-xl font-bold text-gray-900 dark:text-white">Google OAuth2 Token</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cached in memory (55m refresh cycle)</p>
            </div>
        </div>

        <!-- High-Priority Wakeup -->
        <div class="bg-white dark:bg-card-dark p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Call Wake-up Channel</span>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                    PRIORITY_MAX
                </span>
            </div>
            <div class="mt-4">
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $setting->call_channel_id ?? 'call_channel' }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Android & iOS background wake-up</p>
            </div>
        </div>
    </div>

    <!-- Main Settings Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Service Account Upload Card -->
        <div class="lg:col-span-5 bg-white dark:bg-card-dark rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                    <i class="fas fa-key text-amber-500"></i>
                    Service Account Credentials
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">
                    Upload your Firebase Service Account private key JSON file downloaded from Firebase Console > Project Settings > Service Accounts.
                </p>

                @if($fileDetails)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700/80 mb-6 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Installed File:</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <i class="fas fa-file-code"></i> {{ $fileDetails['filename'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>File Size:</span>
                            <span>{{ $fileDetails['size_kb'] }} KB</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Last Updated:</span>
                            <span>{{ $fileDetails['modified_at'] }}</span>
                        </div>
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center gap-1 text-[11px] text-gray-400">
                            <i class="fas fa-lock text-amber-500"></i>
                            <span>Stored in non-public path: <code>storage/app/firebase/</code></span>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60 mb-6 text-amber-800 dark:text-amber-300 text-xs">
                        <i class="fas fa-info-circle mr-1"></i> No service account file uploaded yet. Upload your <code>service-account.json</code> below to enable push notifications.
                    </div>
                @endif

                <!-- Upload Form -->
                <form action="{{ route('admin.settings.firebase.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Select Service Account JSON File
                        </label>
                        <input type="file" name="service_account_file" accept=".json,application/json" required
                               class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 focus:outline-none">
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-upload"></i> Upload & Verify Key
                    </button>
                </form>
            </div>
        </div>

        <!-- Configuration Options Card -->
        <div class="lg:col-span-7 bg-white dark:bg-card-dark rounded-2xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                <i class="fas fa-sliders-h text-primary"></i>
                Notification Channel Configuration
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">
                Define standard system sounds and Android Notification Channel IDs to match the Flutter client.
            </p>

            <form action="{{ route('admin.settings.firebase.update') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white">Enable Push Notification System</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Toggle push notification delivery for all mobile devices</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ ($setting->is_active ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <!-- Project ID -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        Firebase Project ID
                    </label>
                    <input type="text" name="project_id" value="{{ old('project_id', $setting->project_id ?? $projectId) }}"
                           placeholder="e.g. astology-production"
                           class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Call Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Call Notification Channel ID
                        </label>
                        <input type="text" name="call_channel_id" value="{{ old('call_channel_id', $setting->call_channel_id ?? 'call_channel') }}"
                               class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-gray-400 mt-1 block">Used for incoming audio/video call alerts</span>
                    </div>

                    <!-- Chat Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Chat Notification Channel ID
                        </label>
                        <input type="text" name="chat_channel_id" value="{{ old('chat_channel_id', $setting->chat_channel_id ?? 'chat_channel') }}"
                               class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-gray-400 mt-1 block">Used for chat messages</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Default Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Default Channel ID
                        </label>
                        <input type="text" name="default_channel_id" value="{{ old('default_channel_id', $setting->default_channel_id ?? 'astology_notifications') }}"
                               class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    <!-- Default Sound -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Default Sound
                        </label>
                        <input type="text" name="default_sound" value="{{ old('default_sound', $setting->default_sound ?? 'default') }}"
                               class="w-full px-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <button type="submit"
                            class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200">
                        Save Configurations
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fcmSettingsManager() {
    return {
        testing: false,
        testResult: null,
        async testConnection() {
            this.testing = true;
            this.testResult = null;
            try {
                const response = await fetch("{{ route('admin.settings.firebase.test') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                this.testResult = data;
            } catch (err) {
                this.testResult = {
                    success: false,
                    message: 'Network request error: ' + err.message
                };
            } finally {
                this.testing = false;
            }
        }
    };
}
</script>
@endsection
