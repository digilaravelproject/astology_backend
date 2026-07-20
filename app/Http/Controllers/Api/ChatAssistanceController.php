<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatAssistanceService;
use App\Helpers\ApiResponse;
use App\Models\ChatAssistanceSession;
use App\Models\Setting;
use Exception;

class ChatAssistanceController extends Controller
{
    protected $chatAssistanceService;

    public function __construct(ChatAssistanceService $chatAssistanceService)
    {
        $this->chatAssistanceService = $chatAssistanceService;
    }

    /**
     * Sanitize user input to prevent XSS attacks
     */
    protected function sanitize(string $text): string
    {
        return strip_tags($text);
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'call_session_id' => 'nullable|exists:call_sessions,id',
        ]);

        try {
            $consumerId = $request->user()->id;
            
            $session = $this->chatAssistanceService->initiateChat(
                $consumerId,
                $request->provider_id,
                $request->call_session_id
            );

            // Broadcast ChatAssistanceInitiated
            broadcast(new \App\Events\ChatAssistanceInitiated($session, $request->user()));

            return ApiResponse::success(['session' => $session], 'Chat assistance initiated successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required_without:attachment_url|string',
            'attachment_url' => 'nullable|string',
            'type' => 'in:text,image,document,file,audio,video',
            'call_session_id' => 'nullable|exists:call_sessions,id',
        ]);

        try {
            $userId = $request->user()->id;
            $sanitizedMessage = $request->message ? $this->sanitize($request->message) : null;

            $messageData = [
                'message' => $sanitizedMessage,
                'attachment_url' => $request->attachment_url,
                'type' => $request->type ?? 'text',
                'call_session_id' => $request->call_session_id,
            ];

            $message = $this->chatAssistanceService->sendMessage($sessionId, $userId, $messageData);

            return ApiResponse::success(['message' => $message], 'Message sent successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function getMessages(Request $request, $sessionId)
    {
        try {
            $userId = $request->user()->id;
            $messages = $this->chatAssistanceService->getMessagesForSession($sessionId, $userId);
            
            $responseData = $messages->toArray();
            $responseData['chat_assistance_session_id'] = (int) $sessionId;

            return ApiResponse::success($responseData, 'Messages retrieved successfully');
        } catch (Exception $e) {
            $code = $e->getCode() == 403 ? 403 : 500;
            return ApiResponse::error($e->getMessage(), $code);
        }
    }

    public function syncStatus(Request $request, $sessionId)
    {
        $request->validate([
            'status' => 'required|in:delivered,seen',
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:chat_assistance_messages,id',
        ]);

        try {
            $userId = $request->user()->id;
            
            $this->chatAssistanceService->syncMessageStatus(
                $sessionId,
                $userId,
                $request->status,
                $request->message_ids
            );

            return ApiResponse::success(null, 'Status synced successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getAstrologerStatus(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $status = $this->chatAssistanceService->getAstrologerLimitStatus($userId);
            return ApiResponse::success($status, 'Astrologer limits status retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function getSessions(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $perPage = min((int) $request->query('per_page', 15), 50);

            $sessions = $this->chatAssistanceService->getSessions($userId, $perPage);
            return ApiResponse::success($sessions, 'Chat assistance sessions retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
