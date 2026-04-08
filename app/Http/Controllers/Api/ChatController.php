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
use Exception;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function initiateChat(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id'
        ]);

        try {
            $consumerId = $request->user()->id;
            $session = $this->chatService->initiateChat($consumerId, $request->provider_id);
            
            // Broadcast ChatInitiated with full consumer details
            broadcast(new ChatInitiated($session, $request->user()));
            
            return ApiResponse::success(['session' => $session], 'Chat initiated successfully');
            
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function acceptChat(Request $request, $sessionId)
    {
        try {
            $providerId = $request->user()->id;
            $session = $this->chatService->acceptChat($sessionId, $providerId);
            
            // Broadcast ChatAccepted to the consumer with full provider details
            $session->load(['provider.astrologer', 'consumer']);
            broadcast(new ChatAccepted($session, $session->provider));
            
            return ApiResponse::success(['session' => $session], 'Chat accepted successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function endChat(Request $request, $sessionId)
    {
        try {
            $session = $this->chatService->endChat($sessionId, $request->user()->id);
            broadcast(new ChatEnded($session, $request->user()->id));
            return ApiResponse::success(['session' => $session], 'Chat ended successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    public function rejectChat(Request $request, $sessionId)
    {
        try {
            $session = $this->chatService->endChat($sessionId, $request->user()->id);
            broadcast(new ChatEnded($session, $request->user()->id));
            return ApiResponse::success(null, 'Chat rejected');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required_without:attachment_url|string',
            'attachment_url' => 'nullable|string',
            'type' => 'in:text,image,system'
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

            $message = Message::create([
                'chat_session_id' => $sessionId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $request->message,
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
            $messages = $this->chatService->getMessages($sessionId);
            return ApiResponse::success($messages, 'Messages retrieved');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function markAsRead(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;
            Message::where('chat_session_id', $sessionId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true, 'is_delivered' => true, 'updated_at' => now()]);

            return ApiResponse::success(null, 'Messages marked as read');
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

            $query = Message::where('chat_session_id', $sessionId)
                ->whereIn('id', $messageIds)
                ->where('receiver_id', $userId);

            if ($status === 'delivered') {
                $query->update(['is_delivered' => true, 'updated_at' => now()]);
            } elseif ($status === 'seen') {
                $query->update(['is_read' => true, 'is_delivered' => true, 'updated_at' => now()]);
            }

            return ApiResponse::success(null, 'Status updated');
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
}
