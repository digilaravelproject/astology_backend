@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Order Details</h1>
            <p class="text-sm text-gray font-medium">View the order record and session details.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.orders.index') }}" class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all text-xs">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <form action="{{ route('admin.orders.destroy', ['type' => $type, 'id' => explode('-', $order['id'])[1]]) }}" method="POST" onsubmit="return confirm('Delete this order?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-danger text-white px-4 py-2.5 rounded-xl font-bold hover:bg-danger-dark transition-all text-xs">
                    <i class="fas fa-trash"></i> Delete Order
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="space-y-4">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Order ID</div>
                    <div class="text-2xl font-black text-dark">{{ $order['id'] }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Customer</div>
                    <div class="text-lg font-black text-dark">{{ $order['user'] }}</div>
                    <div class="text-xs text-gray-light">{{ $order['user_mail'] }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Astro Partner</div>
                    <div class="text-lg font-black text-dark">{{ $order['astro'] }}</div>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Order Type</div>
                    <div class="text-lg font-black text-dark">{{ $order['type'] }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Status</div>
                    <div class="text-lg font-black text-dark">{{ $order['status'] }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray uppercase tracking-widest">Amount</div>
                    <div class="text-lg font-black text-dark">{{ $order['amount'] }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-light/50 rounded-3xl p-6">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Duration</div>
                <div class="text-lg font-black text-dark">{{ $order['duration'] }}</div>
            </div>
            <div class="bg-light/50 rounded-3xl p-6">
                <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Dates</div>
                <div class="text-sm text-dark font-black">Started: {{ $order['started_at'] ?? 'N/A' }}</div>
                <div class="text-sm text-dark font-black">Ended: {{ $order['ended_at'] ?? 'N/A' }}</div>
                <div class="text-sm text-gray mt-2">Created: {{ $order['date'] }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
