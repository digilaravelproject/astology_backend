@extends('admin.layouts.app')

@section('title', 'Firebase Push Notification Settings')

@section('content')
<div class="space-y-8" x-data="fcmSettingsManager()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-lighter pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                    FCM Engine v1
                </span>
                <span class="text-text-muted text-xs">• RFC 7519 OAuth2 RS256</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-text-primary tracking-tight flex items-center gap-3">
                <i class="fas fa-fire text-amber-500"></i>
                Firebase Push Notification Settings
            </h1>
            <p class="text-sm text-text-secondary mt-1">
                Configure Firebase Cloud Messaging credentials and notification delivery channels for Android & iOS devices.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="testConnection()" :disabled="testing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-primary hover:from-amber-600 hover:to-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 disabled:opacity-50 cursor-pointer">
                <i class="fas" :class="testing ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                <span x-text="testing ? 'Testing Handshake...' : 'Test Connection'"></span>
            </button>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-800 shadow-xs">
            <i class="fas fa-check-circle text-lg text-emerald-600"></i>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-rose-800 shadow-xs">
            <i class="fas fa-exclamation-circle text-lg text-rose-600"></i>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Test Connection Result Live Card -->
    <div x-show="testResult !== null" x-cloak x-transition
         class="p-5 rounded-2xl border transition-all shadow-xs"
         :class="testResult?.success ? 'bg-emerald-50 border-emerald-300' : 'bg-rose-50 border-rose-300'">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                     :class="testResult?.success ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'">
                    <i class="fas" :class="testResult?.success ? 'fa-check' : 'fa-times'"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold" :class="testResult?.success ? 'text-emerald-900' : 'text-rose-900'" x-text="testResult?.message"></h3>
                    <template x-if="testResult?.details">
                        <div class="mt-2 text-xs font-mono bg-white/80 border border-gray-200 p-3 rounded-xl space-y-1 text-text-primary">
                            <div><strong>Project ID:</strong> <span x-text="testResult?.details?.project_id || 'N/A'"></span></div>
                            <div><strong>Client Email:</strong> <span x-text="testResult?.details?.client_email || 'N/A'"></span></div>
                            <div><strong>Latency:</strong> <span class="text-emerald-600 font-bold" x-text="(testResult?.details?.duration_ms || 0) + ' ms'"></span></div>
                        </div>
                    </template>
                </div>
            </div>
            <button @click="testResult = null" class="text-text-muted hover:text-text-primary p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- FCM Status -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-primary/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Connection Status</span>
                @if($isConfigured)
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active & Connected
                    </span>
                @else
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800 border border-rose-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Credentials Missing
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <div class="text-xl font-extrabold text-text-primary">
                    {{ $projectId ?? 'No Project Configured' }}
                </div>
                <p class="text-xs text-text-muted mt-1">Google Cloud / Firebase Project ID</p>
            </div>
        </div>

        <!-- API Engine -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-primary/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">API Protocol</span>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-800 border border-blue-200">
                    HTTP v1 (OAuth2 RS256)
                </span>
            </div>
            <div class="mt-4">
                <div class="text-xl font-extrabold text-text-primary">Google OAuth2 Token</div>
                <p class="text-xs text-text-muted mt-1">Cached in memory (55m refresh cycle)</p>
            </div>
        </div>

        <!-- High-Priority Wakeup -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs hover:border-primary/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Call Wake-up Channel</span>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-50 text-purple-800 border border-purple-200">
                    PRIORITY_MAX
                </span>
            </div>
            <div class="mt-4">
                <div class="text-xl font-extrabold text-text-primary">{{ $setting->call_channel_id ?? 'call_channel' }}</div>
                <p class="text-xs text-text-muted mt-1">Android & iOS background wake-up</p>
            </div>
        </div>
    </div>

    <!-- Main Settings Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Service Account Upload Card -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-key text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-text-primary">Service Account Key</h2>
                        <p class="text-xs text-text-muted">Firebase private key credentials</p>
                    </div>
                </div>

                <p class="text-xs text-text-secondary mb-5 leading-relaxed">
                    Upload your Firebase Service Account JSON file downloaded from <strong>Firebase Console &gt; Project Settings &gt; Service Accounts &gt; Generate new private key</strong>.
                </p>

                @if($fileDetails)
                    <div class="p-4 bg-light/30 rounded-xl border border-gray-200 mb-6 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-text-muted">Installed File:</span>
                            <span class="text-xs font-mono font-bold text-emerald-700 flex items-center gap-1.5 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <i class="fas fa-file-code"></i> {{ $fileDetails['filename'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-text-secondary">
                            <span>File Size:</span>
                            <span class="font-semibold">{{ $fileDetails['size_kb'] }} KB</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-text-secondary">
                            <span>Last Updated:</span>
                            <span class="font-semibold">{{ $fileDetails['modified_at'] }}</span>
                        </div>
                        <div class="pt-2 border-t border-gray-200/80 flex items-center gap-1.5 text-[11px] text-text-muted">
                            <i class="fas fa-shield-alt text-emerald-600"></i>
                            <span>Stored securely in <code>storage/app/firebase/</code></span>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 mb-6 text-amber-900 text-xs flex items-start gap-2.5 leading-relaxed">
                        <i class="fas fa-info-circle text-amber-600 text-sm shrink-0 mt-0.5"></i>
                        <div>
                            <strong>No service account file uploaded yet.</strong> Upload your <code>service-account.json</code> below to enable push notifications.
                        </div>
                    </div>
                @endif

                <!-- Upload Form -->
                <form action="{{ route('admin.settings.firebase.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Select Service Account JSON File
                        </label>
                        <input type="file" name="service_account_file" accept=".json,application/json" required
                               class="w-full text-xs text-text-primary file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-primary-dark file:cursor-pointer cursor-pointer bg-light/20 border border-gray-300 rounded-xl p-2 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fas fa-upload"></i> Upload & Verify Key
                    </button>
                </form>
            </div>
        </div>

        <!-- Configuration Options Card -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
            <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-5">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i class="fas fa-sliders-h text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-text-primary">Notification Channel Configuration</h2>
                    <p class="text-xs text-text-muted">Define delivery channels and sound profiles for Flutter client</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.firebase.update') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-between p-4 bg-light/30 rounded-xl border border-gray-200">
                    <div>
                        <div class="text-sm font-bold text-text-primary">Enable Push Notification System</div>
                        <div class="text-xs text-text-muted">Toggle push notification delivery for all mobile devices</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ ($setting->is_active ?? true) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Project ID -->
                <div>
                    <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                        Firebase Project ID
                    </label>
                    <input type="text" name="project_id" value="{{ old('project_id', $setting->project_id ?? $projectId) }}"
                           placeholder="e.g. astology-production"
                           class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Call Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Call Notification Channel ID
                        </label>
                        <input type="text" name="call_channel_id" value="{{ old('call_channel_id', $setting->call_channel_id ?? 'call_channel') }}"
                               class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-text-muted mt-1 block">Used for incoming audio/video call alerts</span>
                    </div>

                    <!-- Chat Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Chat Notification Channel ID
                        </label>
                        <input type="text" name="chat_channel_id" value="{{ old('chat_channel_id', $setting->chat_channel_id ?? 'chat_channel') }}"
                               class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-text-muted mt-1 block">Used for chat messages</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Live Session Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Live Session Notification Channel ID
                        </label>
                        <input type="text" name="live_channel_id" value="{{ old('live_channel_id', $setting->live_channel_id ?? 'live_session_channel') }}"
                                class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-text-muted mt-1 block">Used for Live Stream & scheduled live alerts</span>
                    </div>

                    <!-- Default Channel ID -->
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Default Channel ID
                        </label>
                        <input type="text" name="default_channel_id" value="{{ old('default_channel_id', $setting->default_channel_id ?? 'astology_notifications') }}"
                                class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <span class="text-[11px] text-text-muted mt-1 block">Used for general announcements and promotions</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Default Sound -->
                    <div>
                        <label class="block text-xs font-bold text-text-secondary uppercase tracking-wider mb-2">
                            Default Sound
                        </label>
                        <input type="text" name="default_sound" value="{{ old('default_sound', $setting->default_sound ?? 'default') }}"
                               class="w-full px-4 py-3 text-sm bg-white border border-gray-300 rounded-xl text-text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                            class="px-7 py-3 bg-primary hover:bg-primary-dark text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md shadow-primary/20 transition-all duration-200 cursor-pointer">
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
