<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatAssistanceService;
use App\Helpers\ApiResponse;
use App\Models\ChatAssistanceSession;
use App\Models\Setting;
use App\Services\ContentSanitizerService;
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
            'message' => 'nullable|string',
            'attachment_url' => 'nullable',
            'attachment' => 'nullable',
            'file' => 'nullable|file|max:10240',
            'type' => 'nullable|in:text,image,document,file,audio,video',
            'call_session_id' => 'nullable|exists:call_sessions,id',
        ]);

        $hasMessage = $request->filled('message');
        $hasAttachment = $request->filled('attachment_url') 
            || $request->filled('attachment') 
            || $request->hasFile('file') 
            || $request->hasFile('attachment') 
            || $request->hasFile('attachment_url');

        if (!$hasMessage && !$hasAttachment) {
            return ApiResponse::error('Either a message or an attachment is required.', 422);
        }

        try {
            $userId = $request->user()->id;
            $cleanMessage = $request->message ? $this->sanitize($request->message) : null;
            $sanitizedMessage = ContentSanitizerService::sanitize($cleanMessage);
            $attachmentUrl = null;

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store("chat-assistance-attachments/{$userId}", 'public');
                $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            } elseif ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store("chat-assistance-attachments/{$userId}", 'public');
                $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            } elseif ($request->hasFile('attachment_url')) {
                $path = $request->file('attachment_url')->store("chat-assistance-attachments/{$userId}", 'public');
                $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            } elseif ($request->filled('attachment_url') && is_string($request->attachment_url)) {
                $attachmentUrl = $request->attachment_url;
            } elseif ($request->filled('attachment') && is_string($request->attachment)) {
                $attachmentUrl = $request->attachment;
            }

            $type = $request->type;
            if (!$type) {
                $type = $attachmentUrl ? 'image' : 'text';
            }

            $messageData = [
                'message' => $sanitizedMessage,
                'attachment_url' => $attachmentUrl,
                'type' => $type,
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
            $sessionId = (int) $sessionId;
            if ($sessionId <= 0) {
                return ApiResponse::error('Invalid or missing chat assistance session ID', 404);
            }

            $userId = $request->user()->id;
            $perPage = min((int) $request->query('per_page', 50), 100);
            $direction = $request->query('direction', 'asc');
            $messages = $this->chatAssistanceService->getMessagesForSession($sessionId, $userId, $perPage, $direction);
            
            $responseData = $messages->toArray();
            $responseData['chat_assistance_session_id'] = (int) $sessionId;

            $session = ChatAssistanceSession::find($sessionId);
            if ($session) {
                $completedChats = \App\Models\ChatSession::where('provider_id', $session->provider_id)->where('status', 'completed')->count();
                $completedCalls = \App\Models\CallSession::where('provider_id', $session->provider_id)->where('status', 'completed')->count();
                $totalOrders = 120 + $completedChats + $completedCalls;

                $responseData['total_orders'] = $totalOrders;
                $responseData['orders_count'] = $totalOrders;
                $responseData['orders_formatted'] = "{$totalOrders}+ orders";
            }

            return ApiResponse::success($responseData, 'Messages retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Chat assistance session not found', 404);
        } catch (Exception $e) {
            $code = $e->getCode() == 403 ? 403 : ($e->getCode() == 404 ? 404 : 500);
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
