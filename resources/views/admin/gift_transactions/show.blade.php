@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Gift Transaction #{{ $transaction->id }}</h1>
            <p class="text-sm text-gray font-medium">View details for this gift transaction.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.gift_transactions.index') }}" class="bg-white border border-gray-lighter text-dark px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-chevron-left"></i> Back to list
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-lighter rounded-[32px] shadow-sm p-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-6">
            <div>
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Overview</div>
                <div class="text-sm font-black text-dark">Gift: {{ $transaction->gift->title ?? '-' }}</div>
                <div class="text-[10px] text-gray">Amount: ₹{{ number_format($transaction->amount, 2) }}</div>
                <div class="text-[10px] text-gray">Status: {{ ucfirst($transaction->status) }}</div>
                <div class="text-[10px] text-gray">Method: {{ $transaction->payment_provider ?? 'wallet' }}</div>
                <div class="text-[10px] text-gray">Created: {{ $transaction->created_at?->format('d M, Y H:i') }}</div>
            </div>

            <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Sender</div>
                <div class="text-sm font-black text-dark">{{ $transaction->sender->name ?? '-' }}</div>
                <div class="text-[10px] text-gray">Email: {{ $transaction->sender->email ?? '-' }}</div>
            </div>

            <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Astrologer</div>
                <div class="text-sm font-black text-dark">{{ optional($transaction->astrologer->user)->name ?? '-' }}</div>
                <div class="text-[10px] text-gray">Astrologer ID: {{ $transaction->astrologer_id }}</div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-light/50 p-6 rounded-3xl border border-gray-lighter">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-2">Payment Reference</div>
                <div class="text-sm font-black text-dark">Order ID: {{ $transaction->provider_order_id ?? '—' }}</div>
                <div class="text-[10px] text-gray">Payment ID: {{ $transaction->provider_payment_id ?? '—' }}</div>
                <div class="text-[10px] text-gray">Meta: {{ json_encode($transaction->meta) }}</div>
            </div>
            <div>
                <form action="{{ route('admin.gift_transactions.destroy', $transaction->id) }}" method="POST" onsubmit="return confirm('Delete this gift transaction?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-danger text-white px-8 py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-700 transition-all shadow-sm">Delete Transaction</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
