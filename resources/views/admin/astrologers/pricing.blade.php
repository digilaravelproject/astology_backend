@extends('admin.layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Default Astrologer Pricing</h1>
        <p class="text-sm text-gray">Set default rates that apply to new astrologers at signup.</p>
    </div>
    <a href="{{ route('admin.astrologers.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Astrologers</span>
    </a>
</div>

<div class="max-w-3xl">
    <form action="{{ route('admin.astrologers.pricing.update') }}" method="POST">
        @csrf
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 md:p-8 space-y-6">
                <div class="flex items-center gap-2 text-primary font-semibold text-sm">
                    <i class="fas fa-tag"></i>
                    <span>Default Rates (per minute)</span>
                </div>

                <div class="p-5 rounded-xl border border-dashed border-primary/30 bg-primary/5 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="default_chat_rate_per_minute" class="block text-xs font-semibold text-gray-dark">Chat Rate (₹/min)</label>
                            <input type="number" id="default_chat_rate_per_minute" name="default_chat_rate_per_minute" step="0.01" min="0"
                                   value="{{ old('default_chat_rate_per_minute', $defaults['default_chat_rate_per_minute']) }}"
                                   class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @error('default_chat_rate_per_minute') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="default_call_rate_per_minute" class="block text-xs font-semibold text-gray-dark">Call Rate (₹/min)</label>
                            <input type="number" id="default_call_rate_per_minute" name="default_call_rate_per_minute" step="0.01" min="0"
                                   value="{{ old('default_call_rate_per_minute', $defaults['default_call_rate_per_minute']) }}"
                                   class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @error('default_call_rate_per_minute') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="default_video_call_rate_per_minute" class="block text-xs font-semibold text-gray-dark">Live Session Rate (₹/min)</label>
                            <input type="number" id="default_video_call_rate_per_minute" name="default_video_call_rate_per_minute" step="0.01" min="0"
                                   value="{{ old('default_video_call_rate_per_minute', $defaults['default_video_call_rate_per_minute']) }}"
                                   class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @error('default_video_call_rate_per_minute') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="default_po_at_5_rate_per_minute" class="block text-xs font-semibold text-gray-dark">PO at ₹5 Rate (₹/min)</label>
                            <input type="number" id="default_po_at_5_rate_per_minute" name="default_po_at_5_rate_per_minute" step="0.01" min="0"
                                   value="{{ old('default_po_at_5_rate_per_minute', $defaults['default_po_at_5_rate_per_minute']) }}"
                                   class="w-full px-4 py-2.5 border border-gray-lighter rounded-lg text-sm outline-none transition-all duration-300 focus:border-primary focus:ring-2 focus:ring-primary/20">
                            @error('default_po_at_5_rate_per_minute') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="p-4 bg-info/5 rounded-lg border border-info/20">
                        <p class="text-xs text-gray flex items-center gap-2">
                            <i class="fas fa-info-circle text-info"></i>
                            These defaults are applied automatically when a new astrologer signs up.
                            For existing astrologers, use the <strong>"Backfill Pricing"</strong> thinker command.
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 bg-light/30 border-t border-gray-lighter flex items-center gap-4">
                <button type="submit"
                        class="px-8 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary-dark transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Save Default Pricing</span>
                </button>
                <a href="{{ route('admin.astrologers.index') }}"
                   class="px-6 py-2.5 border border-gray-lighter text-gray text-sm font-semibold rounded-lg hover:bg-light transition-all duration-300">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection