<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpsertPackageRequest;
use App\Http\Requests\AdminAssignPackageRequest;
use App\Models\Package;
use App\Models\AstrologerPackage;
use App\Models\PackagePurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class AdminPackageController extends Controller
{
    /**
     * Display a listing of global packages and overrides.
     */
    public function index()
    {
        $packages = Package::orderBy('created_at', 'desc')->get();
        $astrologers = User::where('user_type', 'astrologer')->get();
        $astrologerPackages = AstrologerPackage::with('astrologer')->get();
        $purchases = PackagePurchase::with(['user', 'astrologer'])->latest()->paginate(15);
        $globalPackageCommissionRate = \App\Models\Setting::get('global_package_commission_rate', 50.00);

        return view('admin.packages.index', compact('packages', 'astrologers', 'astrologerPackages', 'purchases', 'globalPackageCommissionRate'));
    }

    /**
     * Store a newly created global package.
     */
    public function store(AdminUpsertPackageRequest $request)
    {
        try {
            $data = $request->validated();
            
            // If marking as default, unset other defaults
            if ($request->boolean('is_default')) {
                Package::where('is_default', true)->update(['is_default' => false]);
            }

            Package::create([
                'name' => $data['name'],
                'default_amount' => $data['default_amount'],
                'default_duration' => (int) ($data['default_duration_minutes'] * 60), // Convert to seconds
                'is_default' => $request->boolean('is_default'),
            ]);

            return redirect()->route('admin.packages.index')
                ->with('success', 'Global package created successfully.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create global package: ' . $e->getMessage());
        }
    }

    /**
     * Update a global package.
     */
    public function update(AdminUpsertPackageRequest $request, $id)
    {
        try {
            $package = Package::findOrFail($id);
            $data = $request->validated();

            // If marking as default, unset other defaults
            if ($request->boolean('is_default')) {
                Package::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
            }

            $package->update([
                'name' => $data['name'],
                'default_amount' => $data['default_amount'],
                'default_duration' => (int) ($data['default_duration_minutes'] * 60), // Convert to seconds
                'is_default' => $request->boolean('is_default'),
            ]);

            return redirect()->route('admin.packages.index')
                ->with('success', 'Global package updated successfully.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update global package: ' . $e->getMessage());
        }
    }

    /**
     * Delete a global package.
     */
    public function destroy($id)
    {
        try {
            $package = Package::findOrFail($id);
            if ($package->is_default) {
                throw new Exception("Cannot delete the default system package. Please set another package as default first.");
            }

            $package->delete();

            return redirect()->route('admin.packages.index')
                ->with('success', 'Global package deleted successfully.');

        } catch (Exception $e) {
            return redirect()->route('admin.packages.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Assign/Override specific package details for an astrologer.
     */
    public function assignToAstrologer(AdminAssignPackageRequest $request)
    {
        try {
            $data = $request->validated();

            AstrologerPackage::updateOrCreate(
                ['astrologer_id' => $data['astrologer_id']],
                [
                    'amount' => $data['amount'],
                    'duration' => (int) ($data['duration_minutes'] * 60), // Convert to seconds
                    'commission_percentage' => $data['commission_percentage'],
                ]
            );

            return redirect()->route('admin.packages.index')
                ->with('success', 'Astrologer package parameters updated successfully.');

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to assign package: ' . $e->getMessage());
        }
    }

    /**
     * Remove astrologer custom package overrides.
     */
    public function removeOverride($id)
    {
        try {
            $astrologerPackage = AstrologerPackage::findOrFail($id);
            $astrologerPackage->delete();

            return redirect()->route('admin.packages.index')
                ->with('success', 'Custom package parameters removed. Astrologer will now fall back to default package.');

        } catch (Exception $e) {
            return redirect()->route('admin.packages.index')
                ->with('error', 'Failed to remove override: ' . $e->getMessage());
        }
    }
}
