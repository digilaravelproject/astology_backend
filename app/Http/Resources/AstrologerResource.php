<?php

namespace App\Http\Resources;

use App\Helpers\MediaHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AstrologerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avgRating = $this->avg_rating ?? ($this->reviews_avg_rating ? (float) number_format($this->reviews_avg_rating, 2) : 0.0);
        $totalOrders = (int) ($this->total_orders ?? $this->orders_count ?? 120);

        $isChatEnabled = (bool) ($this->is_chat_enabled ?? $this->chat_enabled ?? false);
        $isCallEnabled = (bool) ($this->is_call_enabled ?? $this->call_enabled ?? false);
        $isVideoCallEnabled = (bool) ($this->is_video_call_enabled ?? $this->video_call_enabled ?? false);

        $isOnline = (bool) ($isChatEnabled || $isCallEnabled || $isVideoCallEnabled || $this->is_online);
        $isBusy = (bool) ($this->is_busy ?? false);
        $availabilityStatus = $this->availability_status ?? ($isBusy ? 'Engaged' : ($isOnline ? 'Online' : 'Offline'));

        $userObj = null;
        if ($this->user) {
            $userObj = [
                'id' => (int) $this->user->id,
                'name' => (string) ($this->user->name ?? 'Astrologer'),
                'gender' => $this->user->gender,
                'profile_photo' => $this->user->profile_photo,
                'profile_photo_url' => MediaHelper::getUrl($this->user->profile_photo),
            ];
        }

        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'name' => $this->user?->name ?? 'Astrologer',
            'profile_photo' => $this->profile_photo,
            'profile_photo_url' => $this->profile_photo_url ?? ($this->user ? MediaHelper::getUrl($this->user->profile_photo) : null),
            'bio' => $this->bio,
            'years_of_experience' => (string) ($this->years_of_experience ?? '0'),
            'experience' => (int) ($this->years_of_experience ?? 0),
            'areas_of_expertise' => $this->areas_of_expertise ?? [],
            'languages' => $this->languages ?? [],
            'status' => $this->status,
            'avg_rating' => (float) $avgRating,
            'reviews_avg_rating' => (float) $avgRating,
            'total_orders' => $totalOrders,
            'orders_count' => $totalOrders,
            'orders_formatted' => "{$totalOrders}+ orders",
            'is_online' => $isOnline,
            'is_busy' => $isBusy,
            'availability_status' => $availabilityStatus,
            'is_chat_enabled' => $isChatEnabled,
            'is_call_enabled' => $isCallEnabled,
            'is_video_call_enabled' => $isVideoCallEnabled,
            'chat_enabled' => $isChatEnabled,
            'call_enabled' => $isCallEnabled,
            'video_call_enabled' => $isVideoCallEnabled,
            'chat_rate_per_minute' => (float) ($this->chat_rate_per_minute ?? 0.0),
            'call_rate_per_minute' => (float) ($this->call_rate_per_minute ?? 0.0),
            'video_call_rate_per_minute' => (float) ($this->video_call_rate_per_minute ?? 0.0),
            'original_chat_rate_per_minute' => (float) ($this->original_chat_rate_per_minute ?? $this->chat_rate_per_minute ?? 0.0),
            'original_call_rate_per_minute' => (float) ($this->original_call_rate_per_minute ?? $this->call_rate_per_minute ?? 0.0),
            'po_at_5_enabled' => (bool) ($this->po_at_5_enabled ?? false),
            'po_at_5_rate_per_minute' => $this->po_at_5_rate_per_minute ? (float) $this->po_at_5_rate_per_minute : null,
            'po_at_5_sessions' => (int) ($this->po_at_5_sessions ?? 0),
            'has_offer' => (bool) ($this->has_offer ?? false),
            'offer_details' => $this->offer_details ?? null,
            'package_details' => $this->package_details ?? null,
            'is_followed' => (bool) ($this->is_followed ?? false),
            'is_blocked' => (bool) ($this->is_blocked ?? false),
            'is_review_eligible' => $this->when(isset($this->is_review_eligible), (bool) ($this->is_review_eligible ?? false)),
            'skill' => $this->whenLoaded('skill', $this->skill),
            'otherDetails' => $this->whenLoaded('otherDetails', $this->otherDetails),
            'user' => $userObj,
            'created_at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->toIso8601String() : $this->created_at,
            'updated_at' => $this->updated_at instanceof \Carbon\Carbon ? $this->updated_at->toIso8601String() : $this->updated_at,
        ];
    }
}
