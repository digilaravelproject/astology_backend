<?php

namespace App\Services;

use App\Models\Astrologer;
use App\Models\PriceIncreaseLevel;
use App\Models\PriceIncreaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PriceIncreaseService
{
    /**
     * Get the current eligible level and next level for an astrologer.
     */
    public function getStatus(Astrologer $astrologer): array
    {
        try {
            $totalBusyMinutes = $astrologer->total_busy_minutes;

            $currentLevel = PriceIncreaseLevel::active()
                ->where('required_busy_minutes', '<=', $totalBusyMinutes)
                ->orderBy('level_number', 'desc')
                ->first();

            $nextLevel = PriceIncreaseLevel::active()
                ->where('required_busy_minutes', '>', $totalBusyMinutes)
                ->orderBy('level_number', 'asc')
                ->first();

            $currentChatRate = (float) $astrologer->chat_rate_per_minute;
            $currentCallRate = (float) $astrologer->call_rate_per_minute;

            $pendingRequest = PriceIncreaseRequest::pending()
                ->byAstrologer($astrologer->id)
                ->latest()
                ->first();

            return [
                'total_busy_minutes' => round($totalBusyMinutes, 2),
                'current_level' => $currentLevel ? [
                    'id' => $currentLevel->id,
                    'name' => $currentLevel->name,
                    'level_number' => $currentLevel->level_number,
                    'required_busy_minutes' => $currentLevel->required_busy_minutes,
                    'max_increase_amount' => (float) $currentLevel->max_increase_amount,
                ] : null,
                'next_level' => $nextLevel ? [
                    'id' => $nextLevel->id,
                    'name' => $nextLevel->name,
                    'level_number' => $nextLevel->level_number,
                    'required_busy_minutes' => $nextLevel->required_busy_minutes,
                    'max_increase_amount' => (float) $nextLevel->max_increase_amount,
                ] : null,
                'current_rates' => [
                    'chat_rate_per_minute' => $currentChatRate,
                    'call_rate_per_minute' => $currentCallRate,
                ],
                'pending_request' => $pendingRequest ? [
                    'id' => $pendingRequest->id,
                    'price_type' => $pendingRequest->price_type,
                    'old_price' => (float) $pendingRequest->old_price,
                    'new_price' => (float) $pendingRequest->new_price,
                    'increase_amount' => (float) $pendingRequest->increase_amount,
                    'level_name' => $pendingRequest->level?->name,
                    'created_at' => $pendingRequest->created_at->toDateTimeString(),
                ] : null,
                'can_request' => $currentLevel !== null && $pendingRequest === null,
            ];
        } catch (\Exception $e) {
            Log::error('PriceIncreaseService::getStatus error: ' . $e->getMessage(), [
                'astrologer_id' => $astrologer->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit a price increase request.
     */
    public function requestIncrease(Astrologer $astrologer, string $priceType): PriceIncreaseRequest
    {
        try {
            $totalBusyMinutes = $astrologer->total_busy_minutes;

            $currentLevel = PriceIncreaseLevel::active()
                ->where('required_busy_minutes', '<=', $totalBusyMinutes)
                ->orderBy('level_number', 'desc')
                ->first();

            if (!$currentLevel) {
                throw new \RuntimeException('You are not eligible for any price increase level yet.');
            }

            $pendingExists = PriceIncreaseRequest::pending()
                ->byAstrologer($astrologer->id)
                ->exists();

            if ($pendingExists) {
                throw new \RuntimeException('You already have a pending price increase request.');
            }

            if (!in_array($priceType, ['call', 'chat'])) {
                throw new \InvalidArgumentException('Invalid price type. Must be call or chat.');
            }

            $oldPrice = $priceType === 'chat'
                ? (float) $astrologer->chat_rate_per_minute
                : (float) $astrologer->call_rate_per_minute;

            $increaseAmount = min(
                $currentLevel->max_increase_amount,
                $oldPrice * 0.20
            );

            $newPrice = $oldPrice + $increaseAmount;

            return DB::transaction(function () use ($astrologer, $currentLevel, $priceType, $oldPrice, $newPrice, $increaseAmount) {
                return PriceIncreaseRequest::create([
                    'astrologer_id' => $astrologer->id,
                    'level_id' => $currentLevel->id,
                    'price_type' => $priceType,
                    'old_price' => $oldPrice,
                    'new_price' => round($newPrice, 2),
                    'increase_amount' => round($increaseAmount, 2),
                    'status' => 'pending',
                ]);
            });
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PriceIncreaseService::requestIncrease error: ' . $e->getMessage(), [
                'astrologer_id' => $astrologer->id,
                'price_type' => $priceType,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to submit price increase request. Please try again.');
        }
    }

    /**
     * Get request history for an astrologer.
     */
    public function getHistory(Astrologer $astrologer): array
    {
        try {
            return PriceIncreaseRequest::byAstrologer($astrologer->id)
                ->with('level')
                ->latest()
                ->get()
                ->map(fn ($req) => [
                    'id' => $req->id,
                    'level_name' => $req->level?->name,
                    'price_type' => $req->price_type,
                    'old_price' => (float) $req->old_price,
                    'new_price' => (float) $req->new_price,
                    'increase_amount' => (float) $req->increase_amount,
                    'status' => $req->status,
                    'admin_remark' => $req->admin_remark,
                    'created_at' => $req->created_at->toDateTimeString(),
                    'approved_at' => $req->approved_at?->toDateTimeString(),
                    'rejected_at' => $req->rejected_at?->toDateTimeString(),
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('PriceIncreaseService::getHistory error: ' . $e->getMessage(), [
                'astrologer_id' => $astrologer->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve a price increase request and update astrologer's rate.
     */
    public function approveRequest(PriceIncreaseRequest $request, ?string $adminRemark = null): PriceIncreaseRequest
    {
        try {
            if ($request->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be approved.');
            }

            return DB::transaction(function () use ($request, $adminRemark) {
                $astrologer = $request->astrologer;

                if ($request->price_type === 'chat') {
                    $astrologer->chat_rate_per_minute = $request->new_price;
                } else {
                    $astrologer->call_rate_per_minute = $request->new_price;
                }
                $astrologer->save();

                $request->update([
                    'status' => 'approved',
                    'admin_remark' => $adminRemark,
                    'approved_at' => now(),
                ]);

                return $request->fresh()->load('astrologer', 'level');
            });
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PriceIncreaseService::approveRequest error: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to approve request. Please try again.');
        }
    }

    /**
     * Reject a price increase request.
     */
    public function rejectRequest(PriceIncreaseRequest $request, ?string $adminRemark = null): PriceIncreaseRequest
    {
        try {
            if ($request->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be rejected.');
            }

            return DB::transaction(function () use ($request, $adminRemark) {
                $request->update([
                    'status' => 'rejected',
                    'admin_remark' => $adminRemark,
                    'rejected_at' => now(),
                ]);

                return $request->fresh()->load('astrologer', 'level');
            });
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PriceIncreaseService::rejectRequest error: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to reject request. Please try again.');
        }
    }
}
