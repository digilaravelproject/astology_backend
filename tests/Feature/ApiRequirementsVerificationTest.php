<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Astrologer;
use App\Models\ChatSession;
use App\Models\CallSession;
use App\Models\ChatAssistanceSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ApiRequirementsVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Review Eligibility & Restriction
     */
    public function test_review_eligibility_key_and_restriction(): void
    {
        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $astrologerUser->id,
            'experience' => 5,
            'chat_rate_per_minute' => 10,
            'call_rate_per_minute' => 15,
        ]);

        // 1a. Uneligible check (No completed sessions)
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/user/astrologers/{$astrologer->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'astrologer' => [
                    'id' => $astrologer->id,
                    'is_review_eligible' => false,
                ]
            ]
        ]);

        // Post review without completed session -> 403 Forbidden
        $reviewResp = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/reviews', [
            'astrologer_id' => $astrologer->id,
            'rating' => 5,
            'review' => 'Great astrologer!',
        ]);
        $reviewResp->assertStatus(403);
        $reviewResp->assertJson([
            'status' => 'error',
            'message' => 'Only users who have had a chart consultation or call with an astrologer can submit a review.'
        ]);

        // 1b. Create a completed chat session
        ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $astrologerUser->id,
            'status' => 'completed',
            'rate_per_minute' => 10,
            'total_cost' => 50,
        ]);

        // Re-check GET astrologer details -> is_review_eligible must be true
        $eligibleResp = $this->actingAs($user, 'sanctum')->getJson("/api/v1/user/astrologers/{$astrologer->id}");
        $eligibleResp->assertStatus(200);
        $eligibleResp->assertJson([
            'status' => 'success',
            'data' => [
                'astrologer' => [
                    'id' => $astrologer->id,
                    'is_review_eligible' => true,
                ]
            ]
        ]);

        // Post review with completed session -> 201 Created
        $successReviewResp = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/reviews', [
            'astrologer_id' => $astrologer->id,
            'rating' => 5,
            'review' => 'Excellent consultation experience!',
        ]);
        $successReviewResp->assertStatus(201);
        $successReviewResp->assertJson([
            'status' => 'success',
            'message' => 'Review posted successfully.'
        ]);
    }

    /**
     * Test 2: Astrologer Orders Display ID Offset
     */
    public function test_astrologer_orders_display_id_offset(): void
    {
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);
        $astrologerUser->astrologer()->create([
            'status' => 'approved',
            'chat_rate_per_minute' => 10,
            'call_rate_per_minute' => 10,
        ]);
        $consumer = User::factory()->create(['user_type' => 'user']);

        // Create 2 chat sessions
        $chat1 = ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $astrologerUser->id,
            'status' => 'completed',
            'rate_per_minute' => 10,
            'total_cost' => 30,
        ]);

        $chat2 = ChatSession::create([
            'consumer_id' => $consumer->id,
            'provider_id' => $astrologerUser->id,
            'status' => 'completed',
            'rate_per_minute' => 10,
            'total_cost' => 40,
        ]);

        $response = $this->actingAs($astrologerUser, 'sanctum')->getJson('/api/v1/astrologer/orders');
        $response->assertStatus(200);

        $orders = $response->json('data.orders');
        $this->assertCount(2, $orders);

        // Page 1 order_id starts from 120 and increases sequentially (120, 121...)
        $this->assertEquals(120, $orders[0]['order_id']);
        $this->assertEquals(121, $orders[1]['order_id']);

        // Session IDs remain intact
        $this->assertNotNull($orders[0]['session_id']);
        $this->assertNotNull($orders[1]['session_id']);
    }

    /**
     * Test 3: Assistant Chat Attachment Fix
     */
    public function test_chat_assistance_message_with_attachment_only(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);

        $session = ChatAssistanceSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $astrologerUser->id,
        ]);

        // 3a. Send attachment_url string without message
        $respUrl = $this->actingAs($user, 'sanctum')->postJson("/api/v1/chat-assistance/{$session->id}/message", [
            'attachment_url' => 'https://example.com/sample_image.png',
        ]);
        $respUrl->assertStatus(200);
        $respUrl->assertJson(['status' => 'success']);
        $this->assertNotNull($respUrl->json('data.message.attachment_url'));
        $this->assertEquals('image', $respUrl->json('data.message.type'));

        // 3b. Send image file upload without text message
        $imageFile = UploadedFile::fake()->image('chart.jpg');
        $respFile = $this->actingAs($user, 'sanctum')->postJson("/api/v1/chat-assistance/{$session->id}/message", [
            'file' => $imageFile,
        ]);
        $respFile->assertStatus(200);
        $respFile->assertJson(['status' => 'success']);
        $this->assertNotNull($respFile->json('data.message.attachment_url'));
        $this->assertEquals('image', $respFile->json('data.message.type'));
    }
}
