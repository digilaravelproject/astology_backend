<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PresenceService;
use App\Helpers\ApiResponse;
use App\Helpers\RtcHelper;
use App\Events\PresenceUpdated;

class PresenceController extends Controller
{
    protected $presenceService;

    public function __construct(PresenceService $presenceService)
    {
        $this->presenceService = $presenceService;
    }

    public function pulse(Request $request)
    {
        $this->presenceService->setOnline($request->user()->id);
        
        $presenceData = RtcHelper::formatUserPresence($request->user());
        broadcast(new PresenceUpdated($presenceData));

        return ApiResponse::success(null, 'Presence updated');
    }

    public function offline(Request $request)
    {
        $this->presenceService->setOffline($request->user()->id);
        
        $presenceData = RtcHelper::formatUserPresence($request->user());
        broadcast(new PresenceUpdated($presenceData));

        return ApiResponse::success(null, 'User is now offline');
    }
}
