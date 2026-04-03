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
            
            // Broadcast ChatInitiated
            broadcast(new ChatInitiated($session, [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'profile_photo' => $request->user()->profile_photo
            ]));
            
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
}
