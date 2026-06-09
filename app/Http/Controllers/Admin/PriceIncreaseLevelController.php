<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceIncreaseLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceIncreaseLevelController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = PriceIncreaseLevel::ordered();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                if ($request->input('status') === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->input('status') === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            $levels = $query->paginate(20)->appends($request->all());

            return view('admin.price_increase_levels.index', compact('levels'));
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to load price increase levels.');
        }
    }

    public function create()
    {
        try {
            return view('admin.price_increase_levels.form');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::create error: ' . $e->getMessage());
            return redirect()->route('admin.price-increase-levels.index')
                ->with('error', 'Failed to load create form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'level_number' => 'required|integer|min:1|unique:price_increase_levels,level_number',
                'required_busy_minutes' => 'required|integer|min:0',
                'max_increase_amount' => 'required|numeric|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $validated['is_active'] = $request->boolean('is_active');

            PriceIncreaseLevel::create($validated);

            return redirect()->route('admin.price-increase-levels.index')
                ->with('success', 'Price increase level created successfully.');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create price increase level. ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $level = PriceIncreaseLevel::findOrFail($id);

            return view('admin.price_increase_levels.form', compact('level'));
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::edit error: ' . $e->getMessage(), [
                'level_id' => $id,
            ]);
            return redirect()->route('admin.price-increase-levels.index')
                ->with('error', 'Price increase level not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $level = PriceIncreaseLevel::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'level_number' => 'required|integer|min:1|unique:price_increase_levels,level_number,' . $id,
                'required_busy_minutes' => 'required|integer|min:0',
                'max_increase_amount' => 'required|numeric|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $validated['is_active'] = $request->boolean('is_active');

            $level->update($validated);

            return redirect()->route('admin.price-increase-levels.index')
                ->with('success', 'Price increase level updated successfully.');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::update error: ' . $e->getMessage(), [
                'level_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update price increase level. ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $level = PriceIncreaseLevel::findOrFail($id);
            $level->update(['is_active' => !$level->is_active]);

            $status = $level->is_active ? 'activated' : 'deactivated';

            return redirect()->route('admin.price-increase-levels.index')
                ->with('success', "Level {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::toggleStatus error: ' . $e->getMessage(), [
                'level_id' => $id,
            ]);
            return redirect()->back()->with('error', 'Failed to toggle level status.');
        }
    }

    public function destroy($id)
    {
        try {
            $level = PriceIncreaseLevel::findOrFail($id);

            if ($level->requests()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete level with associated requests.');
            }

            $level->delete();

            return redirect()->route('admin.price-increase-levels.index')
                ->with('success', 'Price increase level deleted successfully.');
        } catch (\Exception $e) {
            Log::error('PriceIncreaseLevelController::destroy error: ' . $e->getMessage(), [
                'level_id' => $id,
            ]);
            return redirect()->back()->with('error', 'Failed to delete price increase level.');
        }
    }
}
