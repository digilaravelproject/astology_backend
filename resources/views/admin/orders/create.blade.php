@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-1">Create Manual Order</h1>
            <p class="text-sm text-gray font-medium">Generate a new call or chat order from the admin panel.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="bg-white border border-gray-lighter text-dark px-4 py-2.5 rounded-xl font-bold hover:bg-light transition-all text-xs">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    @if ($errors->any())
    <div class="mb-6 bg-danger/10 border border-danger/20 text-danger p-4 rounded-2xl">
        <div class="font-black mb-2">Validation Error</div>
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-[32px] shadow-sm border border-gray-lighter p-8">
        <form action="{{ route('admin.orders.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Order Type</label>
                    <select name="type" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
                        <option value="call" {{ old('type') == 'call' ? 'selected' : '' }}>Call</option>
                        <option value="chat" {{ old('type') == 'chat' ? 'selected' : '' }}>Chat</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Customer</label>
                    <select name="consumer_id" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('consumer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Astrologer</label>
                    <select name="provider_id" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
                        <option value="">Select astrologer</option>
                        @foreach($astrologers as $astrologer)
                            <option value="{{ $astrologer->id }}" {{ old('provider_id') == $astrologer->id ? 'selected' : '' }}>{{ $astrologer->name }} ({{ $astrologer->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Status</label>
                    <select name="status" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
                        <option value="initiated" {{ old('status') == 'initiated' ? 'selected' : '' }}>Initiated</option>
                        <option value="accepted" {{ old('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="ongoing" {{ old('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Amount</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all" placeholder="₹0.00">
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Duration (seconds)</label>
                    <input type="number" name="duration_seconds" value="{{ old('duration_seconds', 0) }}" min="0" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all" placeholder="0">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-[10px] font-black text-gray uppercase tracking-widest mb-2 block">Started At</label>
                    <input type="datetime-local" name="started_at" value="{{ old('started_at') }}" class="w-full bg-light/50 border border-gray-lighter px-4 py-3 rounded-2xl text-xs font-bold focus:outline-none focus:border-dark transition-all">
                </div>
            </div>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-end">
                <a href="{{ route('admin.orders.index') }}" class="px-6 py-3 border border-gray-lighter rounded-2xl text-xs font-black uppercase tracking-widest text-gray hover:bg-light transition-all">Cancel</a>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-primary-dark transition-all">Create Order</button>
            </div>
        </form>
    </div>
</div>
@endsection
