<?php

namespace App\Services;

use App\Models\CallSession;
use App\Models\ChatAssistanceSession;
use App\Models\ChatSession;
use App\Models\Kundli;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KundliService
{
    /**
     * Create a new Kundli record for a user.
     *
     * @param array $data
     * @param int $userId
     * @return Kundli
     */
    public function createKundli(array $data, int $userId): Kundli
    {
        $data['user_id'] = $userId;
        return Kundli::create($data);
    }

    /**
     * Get paginated Kundlis for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserKundlis(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Kundli::where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a user's own Kundli by ID.
     *
     * @param int $id
     * @param int $userId
     * @return Kundli|null
     */
    public function findUserKundli(int $id, int $userId): ?Kundli
    {
        return Kundli::where('user_id', $userId)->find($id);
    }

    /**
     * Update a Kundli record.
     *
     * @param Kundli $kundli
     * @param array $data
     * @return Kundli
     */
    public function updateKundli(Kundli $kundli, array $data): Kundli
    {
        $kundli->update($data);
        return $kundli;
    }

    /**
     * Delete a Kundli for a regular consumer user (ownership verified).
     *
     * @param int $id
     * @param int $userId
     * @return array [bool $success, int $statusCode, string $message]
     */
    public function deleteByUser(int $id, int $userId): array
    {
        $kundli = $this->findUserKundli($id, $userId);

        if (! $kundli) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Kundli not found',
            ];
        }

        $kundli->delete();

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Kundli deleted successfully',
        ];
    }

    /**
     * Delete a Kundli for an Astrologer with consultation relationship verification.
     *
     * @param int $id
     * @param int $astroUserId
     * @return array [bool $success, int $statusCode, string $message]
     */
    public function deleteByAstrologer(int $id, int $astroUserId): array
    {
        $kundli = Kundli::find($id);

        if (! $kundli) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Kundli not found',
            ];
        }

        $clientUserId = $kundli->user_id;

        // Check if astrologer is authorized to manage this client's kundli
        if (! $this->isAstrologerAuthorizedForClient($astroUserId, $clientUserId)) {
            return [
                'success' => false,
                'status_code' => 403,
                'message' => 'Unauthorized. You can only delete Kundlis of clients you have consulted with.',
            ];
        }

        $kundli->delete();

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Kundli deleted successfully',
        ];
    }

    /**
     * Check if an astrologer has an active or past consultation with the client user.
     *
     * @param int $astroUserId
     * @param int|null $clientUserId
     * @return bool
     */
    public function isAstrologerAuthorizedForClient(int $astroUserId, ?int $clientUserId): bool
    {
        if (! $clientUserId) {
            return false;
        }

        // Astrologer deleting their own generated/saved kundli
        if ($clientUserId === $astroUserId) {
            return true;
        }

        // Check consultation sessions: ChatSession, CallSession, or ChatAssistanceSession
        $hasChat = ChatSession::where('provider_id', $astroUserId)
            ->where('consumer_id', $clientUserId)
            ->exists();

        if ($hasChat) {
            return true;
        }

        $hasCall = CallSession::where('provider_id', $astroUserId)
            ->where('consumer_id', $clientUserId)
            ->exists();

        if ($hasCall) {
            return true;
        }

        $hasAssistance = ChatAssistanceSession::where('astrologer_id', $astroUserId)
            ->where('user_id', $clientUserId)
            ->exists();

        return $hasAssistance;
    }
}
