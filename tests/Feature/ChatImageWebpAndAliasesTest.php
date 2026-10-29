<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatImageWebpAndAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_image_is_converted_to_webp_and_serialized_with_all_aliases(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['user_type' => 'user']);
        $astrologerUser = User::factory()->create(['user_type' => 'astrologer']);

        $session = ChatSession::create([
            'consumer_id' => $user->id,
            'provider_id' => $astrologerUser->id,
            'status'      => 'ongoing',
        ]);

        // Create a real fake JPEG image using imagecreatetruecolor
        $tempImgPath = tempnam(sys_get_temp_dir(), 'test_img') . '.jpg';
        $im = imagecreatetruecolor(400, 300);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefilledrectangle($im, 0, 0, 399, 299, $red);
        imagejpeg($im, $tempImgPath);
        imagedestroy($im);

        $uploadedFile = new UploadedFile(
            $tempImgPath,
            'my_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        // 1. Upload via /api/v1/chat/upload-attachment
        $uploadResp = $this->actingAs($user, 'sanctum')->postJson('/api/v1/chat/upload-attachment', [
            'file' => $uploadedFile,
        ]);

        $uploadResp->assertStatus(201);
        $uploadResp->assertJsonPath('status', 'success');
        $uploadResp->assertJsonPath('data.is_image', true);
        $uploadResp->assertJsonPath('data.type', 'image');

        // Verify all alias keys exist in upload response
        $this->assertNotNull($uploadResp->json('data.attachment_url'));
        $this->assertNotNull($uploadResp->json('data.attachment'));
        $this->assertNotNull($uploadResp->json('data.image'));
        $this->assertNotNull($uploadResp->json('data.image_url'));
        $this->assertNotNull($uploadResp->json('data.media_url'));
        $this->assertNotNull($uploadResp->json('data.url'));
        $this->assertStringEndsWith('.webp', $uploadResp->json('data.file_path'));

        // 2. Send message with the returned URL
        $msgResp = $this->actingAs($user, 'sanctum')->postJson("/api/v1/chat/{$session->id}/message", [
            'attachment_url' => $uploadResp->json('data.attachment_url'),
            'type' => 'image',
        ]);

        $msgResp->assertStatus(200);
        $msg = $msgResp->json('data.message');

        // Verify message serialization has all Flutter compatibility aliases
        $this->assertEquals('image', $msg['type']);
        $this->assertTrue($msg['is_image']);
        $this->assertNotNull($msg['attachment_url']);
        $this->assertNotNull($msg['attachment']);
        $this->assertNotNull($msg['image']);
        $this->assertNotNull($msg['image_url']);
        $this->assertNotNull($msg['media_url']);
        $this->assertNotNull($msg['url']);
        $this->assertStringEndsWith('.webp', $msg['attachment_url']);

        // 3. Test getMessages returns the same aliases for Flutter history rendering
        $historyResp = $this->actingAs($user, 'sanctum')->getJson("/api/v1/chat/{$session->id}/messages");
        $historyResp->assertStatus(200);
        $historyItems = $historyResp->json('data.data') ?? $historyResp->json('data');
        $this->assertNotEmpty($historyItems);
        $firstItem = $historyItems[0];
        $this->assertEquals('image', $firstItem['type']);
        $this->assertTrue($firstItem['is_image']);
        $this->assertNotNull($firstItem['attachment_url']);
        $this->assertNotNull($firstItem['attachment']);
        $this->assertNotNull($firstItem['image']);

        @unlink($tempImgPath);
    }
}
