@extends('admin.layouts.app')

@section('content')
<div>
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Plan Inventory
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">Edit Plan</h1>
            <p class="text-sm text-gray font-medium mt-2">Update plan details and features.</p>
        </div>
    </div>

    <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST" class="max-w-[900px] bg-white p-8 rounded-[32px] border border-gray-lighter shadow-sm">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-black text-gray uppercase mb-2">Plan Name</label>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" class="w-full border rounded-lg px-4 py-3" required>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray uppercase mb-2">Price (INR)</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $plan->price) }}" class="w-full border rounded-lg px-4 py-3" required>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray uppercase mb-2">Duration (days)</label>
                    <input type="number" name="duration_days" min="1" value="{{ old('duration_days', $plan->duration_days) }}" class="w-full border rounded-lg px-4 py-3" required>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray uppercase mb-2">Status</label>
                    <select name="status" class="w-full border rounded-lg px-4 py-3" required>
                        <option value="active" {{ old('status', $plan->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $plan->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-gray uppercase mb-2">Description</label>
                <textarea name="description" rows="4" class="w-full border rounded-lg px-4 py-3">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-black text-gray uppercase mb-2">Features (comma separated)</label>
                <textarea name="features_text" rows="2" class="w-full border rounded-lg px-4 py-3">{{ old('features_text', implode(', ', $plan->features ?: [])) }}</textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-8 py-3 bg-primary text-white font-black rounded-xl uppercase">Save Changes</button>
                <a href="{{ route('admin.plans.index') }}" class="px-8 py-3 bg-gray text-black font-black rounded-xl uppercase">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
