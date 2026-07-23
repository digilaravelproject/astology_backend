<?php

namespace App\Observers;

use App\Models\Astrologer;
use App\Services\PackageService;
use Illuminate\Support\Facades\Log;

class AstrologerObserver
{
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    /**
     * Handle the Astrologer "created" event.
     */
    public function created(Astrologer $astrologer): void
    {
        try {
            $this->packageService->assignDefaultPackage($astrologer->user_id);
        } catch (\Exception $e) {
            Log::error("Failed to assign default package to Astrologer (User ID: {$astrologer->user_id}): " . $e->getMessage());
        }
    }
}
