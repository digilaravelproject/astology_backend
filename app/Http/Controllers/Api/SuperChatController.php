<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\LiveSession;
use App\Models\LiveComment;
use App\Models\SuperChat;
use App\Services\WalletService;
use App\Events\NewLiveComment;
use App\Events\SuperChatReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
            $sessions = LiveSession::with('astrologer.user:id,name,profile_photo')
                ->where('status', 'ongoing')
                ->where('session_type', 'public')
                ->latest('started_at')
                ->get()
                ->map(function ($session) {
                    $astrologerUser = $session->astrologer?->user;
                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'astrologer' => $astrologerUser ? [
                            'id' => $astrologerUser->id,
                            'name' => $astrologerUser->name,
                            'profile_photo' => $astrologerUser->profile_photo_url,
                        ] : null,
                        'viewer_count' => $session->viewer_count,
                        'started_at' => $session->started_at?->toISOString(),
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
                'astrologer.user:id,name,profile_photo,gender,date_of_birth',
                'astrologer.skill',
            ])->findOrFail($id);

            $astrologerUser = $session->astrologer?->user;

            return ApiResponse::success([
                'id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'session_type' => $session->session_type,
                'status' => $session->status,
                'stream_url' => $session->stream_url,
                'viewer_count' => $session->viewer_count,
                'started_at' => $session->started_at?->toISOString(),
                'astrologer' => $astrologerUser ? [
                    'id' => $astrologerUser->id,
                    'name' => $astrologerUser->name,
                    'profile_photo' => $astrologerUser->profile_photo_url,
                    'gender' => $astrologerUser->gender,
                    'date_of_birth' => $astrologerUser->date_of_birth,
                ] : null,
            ], 'Live session retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error('Live session not found', 404);
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
            }

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
                'user_avatar' => $user->profile_photo_url,
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
            'amount'  => 'required|numeric|min:1',
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

            $user = $request->user();
            $amount = (float) $request->amount;
            $astrologerUserId = $session->astrologer->user_id;

            $superChat = DB::transaction(function () use ($session, $user, $amount, $astrologerUserId, $request) {
                $userWallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$userWallet || $userWallet->balance < $amount) {
                    throw new Exception('Insufficient balance in your wallet.');
                }

                $astrologerWallet = \App\Models\Wallet::where('user_id', $astrologerUserId)->lockForUpdate()->first();

                $superChat = SuperChat::create([
                    'live_session_id'    => $session->id,
                    'user_id'            => $user->id,
                    'astrologer_id'       => $session->astrologer_id,
                    'amount'             => $amount,
                    'message'            => $request->message,
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
                'user_avatar' => $user->profile_photo_url,
                'amount'     => $amount,
                'message'    => $superChat->message ?? '',
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
                    'user_avatar' => $comment->user->profile_photo_url,
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
