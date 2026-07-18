<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatService;
use App\Helpers\ApiResponse;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\ChatInitiated;
use App\Events\ChatEnded;
use App\Events\ChatAccepted;
use App\Events\ChatQueueUpdated;
use App\Events\MessageStatusUpdated;
use Illuminate\Support\Facades\Storage;
use Exception;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Sanitize user input to prevent XSS attacks
     */
    protected function sanitize(string $text): string
    {
        return strip_tags($text);
    }

    public function initiateChat(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'question' => 'nullable|string',
        ]);

        try {
            $consumerId = $request->user()->id;
            $session = $this->chatService->initiateChat(
                $consumerId,
                $request->provider_id,
                $request->input('question')
            );
            
            // Broadcast ChatInitiated with full consumer details
            broadcast(new ChatInitiated($session, $request->user()));
            broadcast(new ChatQueueUpdated($session->provider_id, $session, 'initiated'));
            
            return ApiResponse::success(['session' => $session], 'Chat initiated successfully');
            
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function acceptChat(Request $request, $sessionId)
    {
        try {
            $providerId = $request->user()->id;
            $acceptData = $this->chatService->acceptChat($sessionId, $providerId);
            $session = $acceptData['session'];
            
            // Broadcast ChatAccepted to the consumer with full provider details
            $session->load(['provider.astrologer', 'consumer']);
            broadcast(new ChatAccepted($session, $session->provider));
            broadcast(new ChatQueueUpdated($session->provider_id, $session, 'accepted'));

            if ($acceptData['system_message']) {
                broadcast(new MessageSent($acceptData['system_message'], $session->provider_id));
            }

            if ($acceptData['default_message']) {
                broadcast(new MessageSent($acceptData['default_message'], $session->consumer_id));
            }
            
            return ApiResponse::success([
                'session' => $session,
                'default_message' => $acceptData['default_message'],
            ], 'Chat accepted successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function endChat(Request $request, $sessionId)
    {
        try {
            $session = $this->chatService->endChat($sessionId, $request->user()->id);
            broadcast(new ChatEnded($session, $request->user()->id));
            if ($session) {
                broadcast(new ChatQueueUpdated($session->provider_id, $session, 'ended'));
            }
            
            // Calculate billing and duration breakdown details
            $durationSeconds = (int) ($session->duration_seconds ?? 0);
            $totalCost = (float) ($session->total_cost ?? 0.00);

            // Calculate astrologer share based on active offer or global fallback
            $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
            $pricing = $pricingCalculator->calculate($session->provider->astrologer, 'chat');
            $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
            $astrologerEarning = round(($totalCost * $astrologerSharePct) / 100, 2);

            $billingDetails = [
                'duration_seconds' => $durationSeconds,
                'user_details' => [
                    'duration_seconds' => $durationSeconds,
                    'amount_deducted' => (float) $totalCost,
                ],
                'astrologer_details' => [
                    'duration_seconds' => $durationSeconds,
                    'amount_added' => (float) $astrologerEarning,
                ],
            ];

            return ApiResponse::success([
                'session' => $session,
                'billing' => $billingDetails
            ], 'Chat ended successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    public function rejectChat(Request $request, $sessionId)
    {
        try {
            $session = $this->chatService->rejectChat($sessionId, $request->user()->id);
            broadcast(new \App\Events\ChatDismissed($session, $request->user()->id, 'rejected'));
            broadcast(new ChatQueueUpdated($session->provider_id, $session, 'rejected'));
            return ApiResponse::success(null, 'Chat rejected');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required_without:attachment_url|string',
            'attachment_url' => 'nullable|string',
            'type' => 'in:text,image,system,document,file,audio,video'
        ]);

        try {
            $userId = $request->user()->id;
            $session = $this->chatService->getSession($sessionId);

            if (!$session || !in_array($session->status, ['initiated', 'accepted', 'ongoing'])) {
                return ApiResponse::error('Invalid or expired session', 400);
            }

            // Security: Determine receiver and verify participation
            if ($session->consumer_id == $userId) {
                $receiverId = $session->provider_id;
            } elseif ($session->provider_id == $userId) {
                $receiverId = $session->consumer_id;
            } else {
                return ApiResponse::error('Unauthorized participation in this session', 403);
            }

            // Sanitize message to prevent XSS
            $sanitizedMessage = $request->message ? $this->sanitize($request->message) : null;

            $message = Message::create([
                'chat_session_id' => $sessionId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $sanitizedMessage,
                'attachment_url' => $request->attachment_url,
                'type' => $request->type ?? 'text',
            ]);

            broadcast(new MessageSent($message, $receiverId));

            return ApiResponse::success(['message' => $message], 'Message sent');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getMessages(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;
            $messages = $this->chatService->getMessagesForSession($sessionId, $userId);
            return ApiResponse::success($messages, 'Messages retrieved');
        } catch (Exception $e) {
            $code = $e->getCode() == 403 ? 403 : 500;
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function markAsRead(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;

            // Verify user is a participant of this session
            $session = $this->chatService->getSession($sessionId);
            if (!$session || ($session->consumer_id != $userId && $session->provider_id != $userId)) {
                return ApiResponse::error('You are not authorized to access this session', 403);
            }

            // Collect unread message IDs BEFORE marking them (to avoid stale queries)
            $unreadMessageIds = Message::where('chat_session_id', $sessionId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->pluck('id')
                ->toArray();

            // If nothing to mark, return early without broadcasting
            if (empty($unreadMessageIds)) {
                return ApiResponse::success(null, 'No unread messages to mark');
            }

            // Mark messages as read
            $readAt = now();
            Message::whereIn('id', $unreadMessageIds)
                ->update(['is_read' => true, 'is_delivered' => true, 'updated_at' => $readAt]);

            // Determine the sender to notify (the other participant)
            $senderToNotify = ($session->consumer_id == $userId) ? $session->provider_id : $session->consumer_id;

            // Broadcast enriched read receipt event to the sender
            broadcast(new MessageStatusUpdated(
                $unreadMessageIds,
                'seen',
                $senderToNotify,
                (int) $sessionId,
                $userId,
                $readAt->toIso8601String()
            ));

            return ApiResponse::success([
                'marked_count' => count($unreadMessageIds),
                'message_ids' => $unreadMessageIds,
            ], 'Messages marked as read');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function syncStatus(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;
            $status = $request->input('status'); // 'delivered' or 'seen'
            $messageIds = $request->input('message_ids', []);

            if (empty($messageIds)) {
                return ApiResponse::error('No message IDs provided', 400);
            }

            // Verify user is a participant of this session
            $session = $this->chatService->getSession($sessionId);
            if (!$session || ($session->consumer_id != $userId && $session->provider_id != $userId)) {
                return ApiResponse::error('You are not authorized to access this session', 403);
            }

            $query = Message::where('chat_session_id', $sessionId)
                ->whereIn('id', $messageIds)
                ->where('receiver_id', $userId);

            $syncedAt = now();

            if ($status === 'delivered') {
                $query->update(['is_delivered' => true, 'updated_at' => $syncedAt]);
            } elseif ($status === 'seen') {
                $query->update(['is_read' => true, 'is_delivered' => true, 'updated_at' => $syncedAt]);
            }

            $senderToNotify = ($session->consumer_id == $userId) ? $session->provider_id : $session->consumer_id;
            broadcast(new MessageStatusUpdated(
                $messageIds,
                $status,
                $senderToNotify,
                (int) $sessionId,
                $userId,
                $syncedAt->toIso8601String()
            ));

            return ApiResponse::success(null, 'Status updated');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getUserSessions(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $sessions = $this->chatService->getUserSessions($userId);
            return ApiResponse::success($sessions, 'User sessions retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getAstrologerSessions(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $sessions = $this->chatService->getAstrologerSessions($userId);
            return ApiResponse::success($sessions, 'Astrologer sessions retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getSessions(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $sessions = $this->chatService->getSessions($userId);
            return ApiResponse::success($sessions, 'Sessions retrieved');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Limit to 10MB
        ]);

        try {
            $userId = $request->user()->id;
            $file = $request->file('file');
            
            // Store file inside public disk under chat-attachments/{userId}
            $path = $file->store("chat-attachments/{$userId}", 'public');
            
            // Generate full public URL
            $url = Storage::disk('public')->url($path);

            return ApiResponse::success([
                'file_path' => $path,
                'attachment_url' => $url,
            ], 'File uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getCurrentSession(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $session = $this->chatService->getActiveSession($userId);
            
            return ApiResponse::success([
                'session' => $session
            ], 'Current active session retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getCurrentAcceptedSession(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $user->id;
            $userType = $user->user_type;

            $session = \App\Models\ChatSession::with(['consumer', 'provider.astrologer', 'latestMessage'])
                ->withCount(['messages as unread_count' => function ($query) use ($userId) {
                    $query->where('receiver_id', $userId)->where('is_read', false);
                }])
                ->where(function ($query) use ($userId, $userType) {
                    if ($userType === 'user') {
                        $query->where('consumer_id', $userId);
                    } else {
                        $query->where('provider_id', $userId);
                    }
                })
                ->whereIn('status', ['accepted', 'ongoing', 'active'])
                ->orderBy('accepted_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'No current chat session found',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Current chat session retrieved successfully',
                'data' => $session
            ], 200);

        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function cancelChat(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;
            $session = $this->chatService->cancelChat($sessionId, $userId);
            
            // Broadcast ChatDismissed to notify the other participant (provider/astrologer)
            broadcast(new \App\Events\ChatDismissed($session, $userId, 'cancelled'));
            broadcast(new ChatQueueUpdated($session->provider_id, $session, 'cancelled'));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Chat cancelled successfully.',
            ], 200);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() === 403 ? 403 : 400);
        }
    }
}
