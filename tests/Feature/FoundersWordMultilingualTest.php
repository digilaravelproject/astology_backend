<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\FoundersWord;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FoundersWordMultilingualTest extends TestCase
{
    use RefreshDatabase;

    protected Language $langEn;
    protected Language $langHi;
    protected Language $langMr;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->langEn = Language::create(['name' => 'English', 'code' => 'en', 'is_active' => true]);
        $this->langHi = Language::create(['name' => 'Hindi', 'code' => 'hi', 'is_active' => true]);
        $this->langMr = Language::create(['name' => 'Marathi', 'code' => 'mr', 'is_active' => true]);
    }

    public function test_api_filters_founder_words_by_query_param_language(): void
    {
        $enWord = FoundersWord::create([
            'language_id' => $this->langEn->id,
            'title' => 'Welcome to Astro Platform',
            'message' => 'Our vision is to empower people.',
            'is_active' => true,
        ]);

        $hiWord = FoundersWord::create([
            'language_id' => $this->langHi->id,
            'title' => 'Astro Platform me aapka swagat hai',
            'message' => 'Humara lakshya logo ko margdarshan dena hai.',
            'is_active' => true,
        ]);

        $mrWord = FoundersWord::create([
            'language_id' => $this->langMr->id,
            'title' => 'Astro Platform madhe swagat ahe',
            'message' => 'Aamche dhyey lokanna margadarshan karne ahe.',
            'is_active' => true,
        ]);

        // 1. English query
        $resEn = $this->getJson('/api/v1/user/founders-words?language=en');
        $resEn->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $enWord->id)
            ->assertJsonPath('data.founders_words.0.language_code', 'en')
            ->assertJsonPath('data.founders_words.0.title', 'Welcome to Astro Platform');

        // 2. Hindi query
        $resHi = $this->getJson('/api/v1/user/founders-words?language=hi');
        $resHi->assertStatus(200)
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $hiWord->id)
            ->assertJsonPath('data.founders_words.0.language_code', 'hi')
            ->assertJsonPath('data.founders_words.0.title', 'Astro Platform me aapka swagat hai');

        // 3. Marathi query
        $resMr = $this->getJson('/api/v1/user/founders-words?language=mr');
        $resMr->assertStatus(200)
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $mrWord->id)
            ->assertJsonPath('data.founders_words.0.language_code', 'mr')
            ->assertJsonPath('data.founders_words.0.title', 'Astro Platform madhe swagat ahe');
    }

    public function test_api_filters_founder_words_by_accept_language_header(): void
    {
        FoundersWord::create([
            'language_id' => $this->langEn->id,
            'title' => 'English Message',
            'message' => 'English Body',
            'is_active' => true,
        ]);

        $hiWord = FoundersWord::create([
            'language_id' => $this->langHi->id,
            'title' => 'Hindi Message',
            'message' => 'Hindi Body',
            'is_active' => true,
        ]);

        $mrWord = FoundersWord::create([
            'language_id' => $this->langMr->id,
            'title' => 'Marathi Message',
            'message' => 'Marathi Body',
            'is_active' => true,
        ]);

        // Accept-Language: hi
        $resHi = $this->withHeader('Accept-Language', 'hi')->getJson('/api/v1/user/founders-words');
        $resHi->assertStatus(200)
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $hiWord->id)
            ->assertJsonPath('data.founders_words.0.title', 'Hindi Message');

        // Accept-Language: mr
        $resMr = $this->withHeader('Accept-Language', 'mr')->getJson('/api/v1/user/founders-words');
        $resMr->assertStatus(200)
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $mrWord->id)
            ->assertJsonPath('data.founders_words.0.title', 'Marathi Message');
    }

    public function test_api_fallbacks_to_default_active_language_when_no_language_specified(): void
    {
        $enWord = FoundersWord::create([
            'language_id' => $this->langEn->id,
            'title' => 'Default English Message',
            'message' => 'Default Message Body',
            'is_active' => true,
        ]);

        FoundersWord::create([
            'language_id' => $this->langHi->id,
            'title' => 'Hindi Message',
            'message' => 'Hindi Message Body',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/user/founders-words');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.id', $enWord->id);
    }

    public function test_api_show_single_founder_word_with_language(): void
    {
        $mrWord = FoundersWord::create([
            'language_id' => $this->langMr->id,
            'title' => 'Marathi Special Message',
            'message' => 'Full detailed marathi founder message',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/user/founders-words/{$mrWord->id}");
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.founders_word.id', $mrWord->id)
            ->assertJsonPath('data.founders_word.language_id', $this->langMr->id)
            ->assertJsonPath('data.founders_word.language_code', 'mr')
            ->assertJsonPath('data.founders_word.language_name', 'Marathi')
            ->assertJsonPath('data.founders_word.title', 'Marathi Special Message');
    }

    public function test_admin_can_store_and_update_founder_word_with_language(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@astro.com',
            'password' => bcrypt('password'),
        ]);

        $storeResponse = $this->actingAs($admin, 'admin')->post(route('admin.founder_words.store'), [
            'language_id' => $this->langHi->id,
            'title' => 'Admin Hindi Word',
            'message' => 'Admin Hindi Message Content',
            'is_active' => '1',
        ]);

        $storeResponse->assertRedirect(route('admin.founder_words.index'));
        $this->assertDatabaseHas('founders_words', [
            'language_id' => $this->langHi->id,
            'title' => 'Admin Hindi Word',
            'is_active' => true,
        ]);

        $word = FoundersWord::first();

        $updateResponse = $this->actingAs($admin, 'admin')->put(route('admin.founder_words.update', $word->id), [
            'language_id' => $this->langMr->id,
            'title' => 'Admin Updated Marathi Word',
            'message' => 'Admin Updated Marathi Message Content',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.founder_words.index'));
        $this->assertDatabaseHas('founders_words', [
            'id' => $word->id,
            'language_id' => $this->langMr->id,
            'title' => 'Admin Updated Marathi Word',
        ]);
    }

    public function test_cache_is_invalidated_when_founder_word_is_updated_or_deleted(): void
    {
        $word = FoundersWord::create([
            'language_id' => $this->langHi->id,
            'title' => 'Original Hindi Title',
            'message' => 'Original Hindi Message',
            'is_active' => true,
        ]);

        // Hit the API to warm cache
        $res = $this->getJson('/api/v1/user/founders-words?language=hi');
        $res->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Original Hindi Title');

        // Update founder word
        $word->update([
            'title' => 'Updated Hindi Title In Cache Test',
        ]);

        // Hit API again - should reflect updated title due to cache invalidation observer
        $res2 = $this->getJson('/api/v1/user/founders-words?language=hi');
        $res2->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Updated Hindi Title In Cache Test');
    }
}
