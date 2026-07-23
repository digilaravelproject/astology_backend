<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AstrologerOfferController extends Controller
{
    /**
     * Retrieve all currently active offers with dynamic rate calculations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json([
                'success' => false,
                'message' => 'Astrologer profile not found.'
            ], 404);
        }

        $astrologer = $user->astrologer;

        // Get the active offer ID for this astrologer
        $activeOfferId = $astrologer->offers()
            ->wherePivot('status', 'active')
            ->first()?->id;

        // Load active, unexpired offers
        $offers = Offer::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->get();

        $formattedOffers = $offers->map(function ($offer) use ($astrologer, $activeOfferId) {
            $discountPct = (float) $offer->discount_percentage;
            
            $chatBase = (float) $astrologer->chat_rate_per_minute;
            $chatDiscounted = round($chatBase * (1 - ($discountPct / 100)), 2);
            $chatAstroShare = (float) $offer->chat_astrologer_share;
            $chatAdminShare = (float) $offer->chat_admin_share;

            $callBase = (float) $astrologer->call_rate_per_minute;
            $callDiscounted = round($callBase * (1 - ($discountPct / 100)), 2);
            $callAstroShare = (float) $offer->call_astrologer_share;
            $callAdminShare = (float) $offer->call_admin_share;

            return [
                'id' => $offer->id,
                'name' => $offer->name,
                'discount_percentage' => $discountPct,
                'is_currently_active' => $offer->id === $activeOfferId,
                'chat' => [
                    'original_price_per_minute' => $chatBase,
                    'discounted_price_per_minute' => $chatDiscounted,
                    'astrologer_share_percentage' => $chatAstroShare,
                    'astrologer_payout_per_minute' => round(($chatDiscounted * $chatAstroShare) / 100, 2),
                    'admin_share_percentage' => $chatAdminShare,
                    'admin_revenue_per_minute' => round(($chatDiscounted * $chatAdminShare) / 100, 2)
                ],
                'call' => [
                    'original_price_per_minute' => $callBase,
                    'discounted_price_per_minute' => $callDiscounted,
                    'astrologer_share_percentage' => $callAstroShare,
                    'astrologer_payout_per_minute' => round(($callDiscounted * $callAstroShare) / 100, 2),
                    'admin_share_percentage' => $callAdminShare,
                    'admin_revenue_per_minute' => round(($callDiscounted * $callAdminShare) / 100, 2)
                ],
                'expires_at' => $offer->expires_at ? $offer->expires_at->toDateTimeString() : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOffers
        ]);
    }

    /**
     * Activate/Deactivate an offer (Only one active offer at a time).
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json([
                'success' => false,
                'message' => 'Astrologer profile not found.'
            ], 404);
        }

        $astrologer = $user->astrologer;

        // Fetch offer
        $offer = Offer::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Find current active offer
            $currentActiveOffer = $astrologer->offers()
                ->wherePivot('status', 'active')
                ->first();

            if ($currentActiveOffer) {
                // If it is the same offer, deactivate it (standard toggle back to base price)
                if ($currentActiveOffer->id == $offer->id) {
                    $astrologer->offers()->updateExistingPivot($offer->id, [
                        'status' => 'complete',
                        'deactivated_at' => Carbon::now()
                    ]);

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Offer deactivated successfully. Returned to base pricing.'
                    ]);
                }

                // Otherwise, deactivate the old one
                $astrologer->offers()->updateExistingPivot($currentActiveOffer->id, [
                    'status' => 'complete',
                    'deactivated_at' => Carbon::now()
                ]);
            }

            // Activate new offer
            $astrologer->offers()->attach($offer->id, [
                'status' => 'active',
                'activated_at' => Carbon::now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer activated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle offer activation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve activation history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->astrologer) {
            return response()->json([
                'success' => false,
                'message' => 'Astrologer profile not found.'
            ], 404);
        }

        $astrologer = $user->astrologer;

        $history = $astrologer->offers()
            ->orderBy('astrologer_offers.id', 'desc')
            ->get();

        $formattedHistory = $history->map(function ($offer) {
            $activated = Carbon::parse($offer->pivot->activated_at);
            $deactivated = $offer->pivot->deactivated_at ? Carbon::parse($offer->pivot->deactivated_at) : null;

            return [
                'id' => $offer->pivot->id,
                'offer_name' => $offer->name,
                'status' => $offer->pivot->status,
                'start_time' => $activated->format('d M Y , h:i A'),
                'end_time' => $deactivated ? $deactivated->format('d M Y , h:i A') : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedHistory
        ]);
    }
}
