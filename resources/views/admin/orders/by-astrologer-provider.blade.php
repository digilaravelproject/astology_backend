@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">{{ $provider->name }} - Provider Orders</h1>
            <p class="text-sm text-gray font-medium">Dynamic order history for this astrologer, with full CRUD actions and session details.</p>
        </div>
        <div class="flex gap-2 justify-center">
            <a href="{{ route('admin.orders.by-astrologer') }}" class="bg-white border border-gray-lighter text-dark px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-light transition-all shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Providers
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Astrologer</div>
            <div class="text-xl font-black text-dark">{{ $provider->name }}</div>
            <div class="text-[12px] text-gray mt-2">{{ $provider->email ?? 'No email available' }}</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Orders</div>
            <div class="text-3xl font-black text-dark">{{ number_format($orders->count()) }}</div>
            <div class="text-[10px] text-gray mt-2">Total sessions for this astrologer</div>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-gray-lighter shadow-sm">
            <div class="text-[10px] font-black text-gray uppercase tracking-widest mb-3">Revenue</div>
            <div class="text-3xl font-black text-primary">₹{{ number_format($totalRevenue, 2) }}</div>
            <div class="text-[10px] text-gray mt-2">Aggregated session settlement</div>
        </div>
    </div>

    <div class="bg-white rounded-[32px] border border-gray-lighter shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-light/30 border-b border-gray-lighter">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Order ID</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Type</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Customer</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Duration</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-lighter">
                    @forelse($orders as $order)
                    <tr class="hover:bg-light/30 transition-colors group">
                        <td class="px-6 py-5 text-sm font-black text-dark">{{ $order['id'] }}</td>
                        <td class="px-6 py-5 text-sm font-black text-dark">{{ $order['type'] }}</td>
                        <td class="px-6 py-5 text-sm text-dark/70">{{ $order['user'] }}</td>
                        <td class="px-6 py-5 text-sm text-dark/70">{{ $order['duration'] }}</td>
                        <td class="px-6 py-5 text-sm font-black text-dark">{{ $order['amount'] }}</td>
                        <td class="px-6 py-5">
                            @if($order['status'] == 'Completed') <span class="px-3 py-1 bg-success/10 text-success text-[9px] font-black uppercase rounded-full border border-success/20">Completed</span>
                            @elseif($order['status'] == 'Cancelled') <span class="px-3 py-1 bg-danger/10 text-danger text-[9px] font-black uppercase rounded-full border border-danger/20">Cancelled</span>
                            @else <span class="px-3 py-1 bg-info/10 text-info text-[9px] font-black uppercase rounded-full border border-info/20">Processing</span> @endif
                        </td>
                        <td class="px-6 py-5 text-right space-x-2">
                            <a href="{{ route('admin.orders.show', ['type' => strtolower($order['type']), 'id' => explode('-', $order['id'])[1]]) }}" class="inline-flex w-10 h-10 bg-white border border-gray-lighter text-dark rounded-xl items-center justify-center hover:bg-dark hover:text-white transition-all shadow-sm">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <form action="{{ route('admin.orders.destroy', ['type' => strtolower($order['type']), 'id' => explode('-', $order['id'])[1]]) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 bg-white border border-gray-lighter text-danger rounded-xl flex items-center justify-center hover:bg-danger hover:text-white transition-all shadow-sm" onclick="return confirm('Delete this order?');">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray text-xs">No orders found for this provider.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
