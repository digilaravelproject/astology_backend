@extends('admin.layouts.app')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.offers.index') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1 mb-2">
        <i class="fas fa-arrow-left"></i> Back to Offers List
    </a>
    <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Edit Offer: {{ $offer->name }}</h1>
    <p class="text-sm text-gray font-medium">Update promotional campaign configurations and commission split splits.</p>
</div>

<div class="max-w-3xl bg-white rounded-2xl shadow-sm border border-gray-lighter p-6 md:p-8">
    @if($errors->any())
    <div class="mb-6 p-4 bg-danger/10 text-danger border-l-4 border-danger rounded-lg text-xs font-bold space-y-1">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form action="{{ route('admin.offers.update', $offer) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
                <label for="name" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Offer Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $offer->name) }}" placeholder="e.g., Summer Bonanza 20%" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
            </div>

            <div class="flex flex-col">
                <label for="discount_percentage" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Discount Percentage (%)</label>
                <input type="number" name="discount_percentage" id="discount_percentage" min="0" max="100" step="0.01" value="{{ old('discount_percentage', $offer->discount_percentage) }}" placeholder="e.g., 20" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
            </div>
        </div>

        <hr class="border-gray-lighter">

        <!-- Call splits -->
        <div>
            <h3 class="text-xs font-black text-primary uppercase tracking-wider mb-4"><i class="fas fa-phone-alt mr-1"></i> Call Commission Split</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col">
                    <label for="call_astrologer_share" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Astrologer Share (%)</label>
                    <input type="number" name="call_astrologer_share" id="call_astrologer_share" min="0" max="100" step="0.01" value="{{ old('call_astrologer_share', $offer->call_astrologer_share) }}" placeholder="e.g., 60" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
                </div>
                <div class="flex flex-col">
                    <label for="call_admin_share" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Admin Commission (%)</label>
                    <input type="number" name="call_admin_share" id="call_admin_share" min="0" max="100" step="0.01" value="{{ old('call_admin_share', $offer->call_admin_share) }}" placeholder="e.g., 40" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
                </div>
            </div>
        </div>

        <hr class="border-gray-lighter">

        <!-- Chat splits -->
        <div>
            <h3 class="text-xs font-black text-primary uppercase tracking-wider mb-4"><i class="fas fa-comments mr-1"></i> Chat Commission Split</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col">
                    <label for="chat_astrologer_share" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Astrologer Share (%)</label>
                    <input type="number" name="chat_astrologer_share" id="chat_astrologer_share" min="0" max="100" step="0.01" value="{{ old('chat_astrologer_share', $offer->chat_astrologer_share) }}" placeholder="e.g., 70" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
                </div>
                <div class="flex flex-col">
                    <label for="chat_admin_share" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Admin Commission (%)</label>
                    <input type="number" name="chat_admin_share" id="chat_admin_share" min="0" max="100" step="0.01" value="{{ old('chat_admin_share', $offer->chat_admin_share) }}" placeholder="e.g., 30" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary" required>
                </div>
            </div>
        </div>

        <hr class="border-gray-lighter">

        <!-- Expiry & Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
                <label for="expires_at" class="text-xs font-bold text-dark mb-2 uppercase tracking-wide">Expiration Date & Time (Optional)</label>
                <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at', $offer->expires_at ? $offer->expires_at->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2.5 border border-gray-lighter rounded-xl text-xs font-semibold focus:outline-hidden focus:border-primary">
            </div>

            <div class="flex items-center gap-3 pt-6">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $offer->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-lighter rounded-sm focus:ring-primary focus:ring-2">
                <label for="is_active" class="text-xs font-bold text-dark cursor-pointer select-none">Offer is Active</label>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.offers.index') }}" class="px-5 py-2.5 bg-light hover:bg-gray-lighter text-dark rounded-xl text-xs font-bold transition-all">Cancel</a>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer">Update Offer</button>
        </div>
    </form>
</div>
@endsection
