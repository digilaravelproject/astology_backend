<?php

namespace App\Services;

use App\Models\Astrologer;
use App\Models\AstrologerCommunity;
use App\Models\User;
use App\Models\UserBlock;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BlockService
{
    /**
     * Block a target user.
     *
     * @param User $blocker
     * @param User $blocked
     * @param string|null $reason
     * @return UserBlock
     */
    public function block(User $blocker, User $blocked, ?string $reason = null): UserBlock
    {
        return DB::transaction(function () use ($blocker, $blocked, $reason) {
            $userBlock = UserBlock::firstOrCreate(
                [
                    'blocker_id' => $blocker->id,
                    'blocked_id' => $blocked->id,
                ],
                [
                    'reason' => $reason,
                ]
            );

            // Backward compatibility: If a regular user is blocking an astrologer, sync AstrologerCommunity
            if ($blocker->user_type === 'user' && $blocked->user_type === 'astrologer') {
                $astrologer = Astrologer::where('user_id', $blocked->id)->first();
                if ($astrologer) {
                    $community = AstrologerCommunity::firstOrNew([
                        'astrologer_id' => $astrologer->id,
                        'user_id' => $blocker->id,
                    ]);
                    $community->is_liked = false;
                    $community->liked_at = null;
                    $community->is_blocked = true;
                    $community->blocked_at = Carbon::now();
                    if ($reason) {
                        $community->report_reason = $reason;
                        $community->reported_at = Carbon::now();
                    }
                    $community->save();
                }
            }

            return $userBlock;
        });
    }

    /**
     * Unblock a target user.
     *
     * @param User $blocker
     * @param User $blocked
     * @return bool
     */
    public function unblock(User $blocker, User $blocked): bool
    {
        return DB::transaction(function () use ($blocker, $blocked) {
            $deleted = UserBlock::where('blocker_id', $blocker->id)
                ->where('blocked_id', $blocked->id)
                ->delete();

            // Backward compatibility: If a regular user is unblocking an astrologer, sync AstrologerCommunity
            if ($blocker->user_type === 'user' && $blocked->user_type === 'astrologer') {
                $astrologer = Astrologer::where('user_id', $blocked->id)->first();
                if ($astrologer) {
                    $community = AstrologerCommunity::where('astrologer_id', $astrologer->id)
                        ->where('user_id', $blocker->id)
                        ->first();
                    if ($community) {
                        $community->is_blocked = false;
                        $community->blocked_at = null;
                        $community->save();
                    }
                }
            }

            return (bool) $deleted;
        });
    }

    /**
     * Check if a specific user has blocked another user (unidirectional).
     */
    public function hasBlocked(int $blockerId, int $blockedId): bool
    {
        return UserBlock::where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->exists();
    }

    /**
     * Check if either party has blocked the other (bidirectional).
     */
    public function isBlockedBidirectional(int $user1Id, int $user2Id): bool
    {
        return UserBlock::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('blocker_id', $user1Id)->where('blocked_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('blocker_id', $user2Id)->where('blocked_id', $user1Id);
        })->exists();
    }

    /**
     * Get list of user IDs that the given user has blocked.
     *
     * @return array<int>
     */
    public function getBlockedUserIds(int $blockerId): array
    {
        return UserBlock::where('blocker_id', $blockerId)
            ->pluck('blocked_id')
            ->toArray();
    }

    /**
     * Get list of user IDs who have blocked the given user.
     *
     * @return array<int>
     */
    public function getBlockerUserIds(int $blockedId): array
    {
        return UserBlock::where('blocked_id', $blockedId)
            ->pluck('blocker_id')
            ->toArray();
    }

    /**
     * Get all bidirectional blocked user IDs for a given user (both blocked by user and blocked the user).
     *
     * @return array<int>
     */
    public function getAllBidirectionalBlockedUserIds(int $userId): array
    {
        $blockedByUser = $this->getBlockedUserIds($userId);
        $blockedUser = $this->getBlockerUserIds($userId);

        return array_values(array_unique(array_merge($blockedByUser, $blockedUser)));
    }

    /**
     * Fetch paginated list of Astrologers blocked by a regular User.
     */
    public function getBlockedAstrologersForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $blockedUserIds = $this->getBlockedUserIds($user->id);

        return Astrologer::with(['user', 'skill', 'otherDetails'])
            ->whereIn('user_id', $blockedUserIds)
            ->paginate($perPage);
    }

    /**
     * Fetch paginated list of regular Users blocked by an Astrologer.
     */
    public function getBlockedUsersForAstrologer(User $astrologerUser, int $perPage = 15): LengthAwarePaginator
    {
        $blockedUserIds = $this->getBlockedUserIds($astrologerUser->id);

        return User::whereIn('id', $blockedUserIds)
            ->where('user_type', 'user')
            ->paginate($perPage);
    }
}
