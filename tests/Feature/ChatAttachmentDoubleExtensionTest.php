<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatAttachmentDoubleExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_extension_image_is_saved_as_image_and_returns_image_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);

        $session = ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $astrologerUser->id,
            'status'      => 'ongoing',
        ]);

        // Create a fake file with double extension .jpg.jpeg
        $doubleExtFile = UploadedFile::fake()->create(
            'Screenshot_2026-10-27-13-25-17-763_com.suryapath.astrologer.jpg.jpeg',
            500,
            'image/jpeg'
        );

        // 1. Upload via upload-attachment
        $uploadResp = $this->actingAs($user, 'sanctum')->postJson('/api/v1/chat/upload-attachment', [
            'file' => $doubleExtFile,
        ]);

        $uploadResp->assertStatus(201);
        $uploadResp->assertJsonPath('status', 'success');
        $uploadResp->assertJsonPath('data.is_image', true);
        $uploadResp->assertJsonPath('data.type', 'image');
        $this->assertStringEndsWith('.jpeg', $uploadResp->json('data.file_path'));

        // 2. Send message using the uploaded attachment URL
        $msgResp = $this->actingAs($user, 'sanctum')->postJson("/api/v1/chat/{$session->id}/message", [
            'attachment_url' => $uploadResp->json('data.attachment_url'),
        ]);

        $msgResp->assertStatus(200);
        $msgResp->assertJsonPath('data.message.type', 'image');
        $this->assertNotNull($msgResp->json('data.message.attachment_url'));
        $this->assertStringEndsWith('.jpeg', $msgResp->json('data.message.attachment_url'));
    }

    public function test_direct_double_extension_in_send_message_forces_image_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);

        $session = ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $astrologerUser->id,
            'status'      => 'ongoing',
        ]);

        $doubleExtFile = UploadedFile::fake()->create(
            'photo.jpg.jpeg',
            500,
            'image/jpeg'
        );

        $resp = $this->actingAs($user, 'sanctum')->postJson("/api/v1/chat/{$session->id}/message", [
            'file' => $doubleExtFile,
            'type' => 'file', // Even if client accidentally sent type: file!
        ]);

        $resp->assertStatus(200);
        $resp->assertJsonPath('data.message.type', 'image');
        $this->assertNotNull($resp->json('data.message.attachment_url'));
        $this->assertStringEndsWith('.jpeg', $resp->json('data.message.attachment_url'));
    }
}
