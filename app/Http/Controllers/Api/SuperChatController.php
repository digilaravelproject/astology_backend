<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveComment;
use App\Models\SuperChat;
use App\Services\LiveKitService;
use App\Services\WalletService;
use App\Events\NewLiveComment;
use App\Events\UserJoinedLiveSession;
use App\Events\UserLeftLiveSession;
use App\Events\SuperChatReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class SuperChatController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function nowStreaming(Request $request)
    {
        try {
            $sessions = LiveSession::with('astrologer.user:id,name')
                ->where('status', 'ongoing')
                ->where('session_type', 'public')
                ->latest('id')
                ->get()
                ->map(function ($session) {
                    $astrologer = $session->astrologer;
                    $astrologerUser = $astrologer?->user;
                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'astrologer' => $astrologer ? [
                            'id' => $astrologer->user_id,
                            'name' => $astrologerUser?->name,
                            'profile_photo' => $astrologerUser?->profile_photo ?? $astrologer?->profile_photo,
                        ] : null,
                        'is_broadcasting' => $session->is_broadcasting,
                        'viewer_count' => $session->viewer_count,
                    ];
                });

            return ApiResponse::success($sessions, 'Live sessions retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $session = LiveSession::with([
                'astrologer.user:id,name,gender',
                'astrologer.skill',
            ])->findOrFail($id);

            $astrologer = $session->astrologer;
            $astrologerUser = $astrologer?->user;

            return ApiResponse::success([
                'id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'session_type' => $session->session_type,
                'status' => $session->status,
                'is_broadcasting' => $session->is_broadcasting,
                'viewer_count' => $session->viewer_count,
                'astrologer' => $astrologer ? [
                    'id' => $astrologer->user_id,
                    'name' => $astrologerUser?->name,
                    'profile_photo' => $astrologerUser?->profile_photo ?? $astrologer?->profile_photo,
                    'gender' => $astrologerUser?->gender,
                    'date_of_birth' => $astrologer->date_of_birth?->format('Y-m-d'),
                ] : null,
            ], 'Live session retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error('Live session not found', 404);
        }
    }

    /**
     * Get LiveKit subscriber token for watching video
     * POST /api/v1/user/live/{id}/watch
     */
    public function watch(LiveKitService $liveKit, $id)
    {
        try {
            $session = LiveSession::findOrFail($id);

            if ($session->status !== 'ongoing') {
                return ApiResponse::error('Live session is not currently active', 400);
            }

            if (!$session->is_broadcasting || !$session->room_uuid) {
                return ApiResponse::error('Broadcast has not started yet', 400);
            }

            $user = auth()->user();

            $identity = 'user_' . $user->id;

            LiveSessionParticipant::updateOrCreate(
                [
                    'live_session_id' => $session->id,
                    'user_id' => $user->id,
                    'role' => 'viewer',
                ],
                [
                    'livekit_identity' => $identity,
                    'joined_at' => now(),
                    'left_at' => null,
                ]
            );

            $token = $liveKit->generateToken(
                $session->room_uuid,
                $identity,
                canPublish: false,
                canSubscribe: true
            );

            return ApiResponse::success([
                'livekit_ws_url' => $liveKit->getWsUrl(),
                'room_uuid' => $session->room_uuid,
                'token' => $token,
            ], 'Watch token generated successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function join($id)
    {
        try {
            $session = LiveSession::findOrFail($id);

            if ($session->status !== 'ongoing') {
                return ApiResponse::error('Live session is not currently active', 400);
            }

            $session->increment('viewer_count');
            $session->refresh();

            broadcast(new \App\Events\ViewerCountUpdated($session->id, $session->viewer_count));

            $user = auth()->user();
            broadcast(new UserJoinedLiveSession($session->id, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar' => $user->profile_photo,
                'joined_at' => now()->toISOString(),
            ]));

            return ApiResponse::success(null, 'Joined live session successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function leave($id)
    {
        try {
            $session = LiveSession::findOrFail($id);

            if ($session->viewer_count > 0) {
                $session->decrement('viewer_count');
                $session->refresh();
            }

            LiveSessionParticipant::where('live_session_id', $session->id)
                ->where('user_id', auth()->id())
                ->whereNull('left_at')
                ->update(['left_at' => now()]);

            broadcast(new \App\Events\ViewerCountUpdated($session->id, $session->viewer_count));

            $user = auth()->user();
            broadcast(new UserLeftLiveSession($session->id, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_avatar' => $user->profile_photo,
                'left_at' => now()->toISOString(),
            ]));

            return ApiResponse::success(null, 'Left live session successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function comment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        try {
            $session = LiveSession::findOrFail($id);

            if ($session->status !== 'ongoing') {
                return ApiResponse::error('Live session is not currently active', 400);
            }

            $user = $request->user();

            $comment = LiveComment::create([
                'live_session_id' => $session->id,
                'user_id' => $user->id,
                'message' => $request->message,
            ]);

            broadcast(new NewLiveComment($session->id, [
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_avatar' => $user->profile_photo,
                'message'    => $comment->message,
                'created_at' => $comment->created_at->toISOString(),
            ]));

            return ApiResponse::success([
                'id' => $comment->id,
                'message' => $comment->message,
                'created_at' => $comment->created_at->toISOString(),
            ], 'Comment sent successfully', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function sendSuperChat(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|integer|exists:gifts,id',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        try {
            $session = LiveSession::findOrFail($id);

            if ($session->status !== 'ongoing') {
                return ApiResponse::error('Live session is not currently active', 400);
            }

            $gift = \App\Models\Gift::findOrFail($request->gift_id);
            if (!$gift->is_active) {
                return ApiResponse::error('Selected gift is not available.', 422);
            }

            $user = $request->user();
            $amount = (float) $gift->price;
            $astrologerUserId = $session->astrologer->user_id;

            $giftMessage = "[Gift: {$gift->title}]" . ($request->message ? ' ' . $request->message : '');

            $superChat = DB::transaction(function () use ($session, $user, $amount, $astrologerUserId, $request, $giftMessage) {
                // To prevent deadlocks, lock the wallets in a deterministic order based on user ID
                $firstUserId = min($user->id, $astrologerUserId);
                $secondUserId = max($user->id, $astrologerUserId);

                if ($firstUserId === $secondUserId) {
                    $userWallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    $astrologerWallet = $userWallet;
                } else {
                    $firstWallet = \App\Models\Wallet::where('user_id', $firstUserId)->lockForUpdate()->first();
                    $secondWallet = \App\Models\Wallet::where('user_id', $secondUserId)->lockForUpdate()->first();

                    $userWallet = $user->id === $firstUserId ? $firstWallet : $secondWallet;
                    $astrologerWallet = $user->id === $firstUserId ? $secondWallet : $firstWallet;
                }

                if (!$userWallet || $userWallet->balance < $amount) {
                    throw new Exception('Insufficient balance in your wallet.');
                }

                $superChat = SuperChat::create([
                    'live_session_id'    => $session->id,
                    'user_id'            => $user->id,
                    'astrologer_id'       => $session->astrologer_id,
                    'amount'             => $amount,
                    'message'            => $giftMessage,
                    'transaction_status' => 'pending',
                ]);

                $txn = $this->walletService->deductForSuperChat($user->id, $amount, $superChat->id);
                $this->walletService->creditAstrologerForSuperChat($astrologerUserId, $amount, $superChat->id);

                $superChat->update([
                    'transaction_status'  => 'completed',
                    'wallet_transaction_id' => $txn->id,
                ]);

                return $superChat->fresh();
            }, 3);

            broadcast(new SuperChatReceived($session->id, [
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_avatar' => $user->profile_photo,
                'amount'     => $amount,
                'message'    => $superChat->message ?? '',
                'gift'       => [
                    'id'       => $gift->id,
                    'title'    => $gift->title,
                    'icon_url' => $gift->icon_url,
                ],
                'created_at' => $superChat->created_at->toISOString(),
            ]));

            return ApiResponse::success([
                'id' => $superChat->id,
                'amount' => $superChat->amount,
                'message' => $superChat->message,
                'created_at' => $superChat->created_at->toISOString(),
            ], 'Super Chat sent successfully', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getMessage() === 'Insufficient balance in your wallet.' ? 402 : 500);
        }
    }

    public function comments(Request $request, $id)
    {
        try {
            $session = LiveSession::findOrFail($id);
            $perPage = min((int) $request->query('per_page', 50), 100);

            $comments = LiveComment::with('user:id,name,profile_photo')
                ->where('live_session_id', $session->id)
                ->latest()
                ->paginate($perPage);

            $data = $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name ?? 'Unknown',
                    'user_avatar' => $comment->user->profile_photo,
                    'message' => $comment->message,
                    'created_at' => $comment->created_at->toISOString(),
                ];
            });

            return ApiResponse::success([
                'data' => $data->values(),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'total_pages' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                ],
            ], 'Comments retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
