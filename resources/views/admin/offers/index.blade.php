@extends('admin.layouts.app')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Offers & Commission Management</h1>
        <p class="text-sm text-gray font-medium">Create promotional offers, configure astrologer/admin split rates, and toggle active promotions.</p>
    </div>
    <div>
        <a href="{{ route('admin.offers.create') }}" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> Create New Offer
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-6 p-4 bg-success/10 text-success border-l-4 border-success rounded-lg text-xs font-bold">
    {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-lighter overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-light/50">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase">Offer Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase">Discount</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase">Call Payout Split (Astro / Admin)</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase">Chat Payout Split (Astro / Admin)</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase text-center">Expires At</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase text-center">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray uppercase text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-lighter">
                @forelse($offers as $offer)
                <tr class="hover:bg-light/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="text-xs font-black text-dark">{{ $offer->name }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-primary/10 text-primary text-[10px] font-black rounded">{{ number_format($offer->discount_percentage) }}% OFF</span>
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-gray">
                        {{ number_format($offer->call_astrologer_share) }}% / {{ number_format($offer->call_admin_share) }}%
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-gray">
                        {{ number_format($offer->chat_astrologer_share) }}% / {{ number_format($offer->chat_admin_share) }}%
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray font-medium">
                        {{ $offer->expires_at ? $offer->expires_at->format('d M Y, h:i A') : 'Never' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="toggleOfferStatus({{ $offer->id }})" id="status-badge-{{ $offer->id }}" class="px-2 py-1 text-[10px] font-black rounded uppercase cursor-pointer transition-all {{ $offer->is_active ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-gray/10 text-gray hover:bg-gray/20' }}">
                            {{ $offer->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.offers.edit', $offer) }}" class="w-7 h-7 bg-light hover:bg-primary/10 hover:text-primary rounded-lg flex items-center justify-center text-xs text-gray transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.offers.destroy', $offer) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this offer?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-7 h-7 bg-light hover:bg-danger/10 hover:text-danger rounded-lg flex items-center justify-center text-xs text-gray transition-all cursor-pointer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray text-xs font-bold">No offers found. Create one to begin.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($offers->hasPages())
    <div class="px-6 py-4 border-t border-gray-lighter">
        {{ $offers->links() }}
    </div>
    @endif
</div>

<script>
function toggleOfferStatus(id) {
    fetch(`/admin/offers/${id}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const badge = document.getElementById(`status-badge-${id}`);
            if(data.is_active) {
                badge.className = "px-2 py-1 text-[10px] font-black rounded uppercase cursor-pointer transition-all bg-success/10 text-success hover:bg-success/20";
                badge.innerText = "Active";
            } else {
                badge.className = "px-2 py-1 text-[10px] font-black rounded uppercase cursor-pointer transition-all bg-gray/10 text-gray hover:bg-gray/20";
                badge.innerText = "Inactive";
            }
        }
    })
    .catch(error => console.error('Error toggling offer status:', error));
}
</script>
@endsection
