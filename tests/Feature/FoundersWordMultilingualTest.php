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

    public function test_api_returns_correct_language_content_based_on_query_param(): void
    {
        $word = FoundersWord::create([
            'title_en' => 'Welcome to Astro Platform',
            'message_en' => 'English Founder Message',
            'title_hi' => 'Astro Platform me aapka swagat hai',
            'message_hi' => 'Hindi Founder Message',
            'title_mr' => 'Astro Platform madhe swagat ahe',
            'message_mr' => 'Marathi Founder Message',
            'is_active' => true,
        ]);

        // 1. English query
        $resEn = $this->getJson('/api/v1/user/founders-words?language=en');
        $resEn->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.founders_words')
            ->assertJsonPath('data.founders_words.0.title', 'Welcome to Astro Platform')
            ->assertJsonPath('data.founders_words.0.message', 'English Founder Message')
            ->assertJsonPath('data.founders_words.0.language_code', 'en');

        // 2. Hindi query
        $resHi = $this->getJson('/api/v1/user/founders-words?language=hi');
        $resHi->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Astro Platform me aapka swagat hai')
            ->assertJsonPath('data.founders_words.0.message', 'Hindi Founder Message')
            ->assertJsonPath('data.founders_words.0.language_code', 'hi');

        // 3. Marathi query
        $resMr = $this->getJson('/api/v1/user/founders-words?language=mr');
        $resMr->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Astro Platform madhe swagat ahe')
            ->assertJsonPath('data.founders_words.0.message', 'Marathi Founder Message')
            ->assertJsonPath('data.founders_words.0.language_code', 'mr');
    }

    public function test_api_returns_correct_language_by_accept_language_header(): void
    {
        FoundersWord::create([
            'title_en' => 'English Title',
            'message_en' => 'English Message',
            'title_hi' => 'Hindi Title',
            'message_hi' => 'Hindi Message',
            'title_mr' => 'Marathi Title',
            'message_mr' => 'Marathi Message',
            'is_active' => true,
        ]);

        // Accept-Language: hi
        $resHi = $this->withHeader('Accept-Language', 'hi')->getJson('/api/v1/user/founders-words');
        $resHi->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Hindi Title')
            ->assertJsonPath('data.founders_words.0.message', 'Hindi Message');

        // Accept-Language: mr
        $resMr = $this->withHeader('Accept-Language', 'mr')->getJson('/api/v1/user/founders-words');
        $resMr->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Marathi Title')
            ->assertJsonPath('data.founders_words.0.message', 'Marathi Message');
    }

    public function test_api_fallbacks_to_english_when_specific_language_is_empty(): void
    {
        FoundersWord::create([
            'title_en' => 'Default English Title',
            'message_en' => 'Default English Message',
            'title_hi' => null, // Empty Hindi translation
            'message_hi' => null,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/user/founders-words?language=hi');
        $response->assertStatus(200)
            ->assertJsonPath('data.founders_words.0.title', 'Default English Title')
            ->assertJsonPath('data.founders_words.0.message', 'Default English Message');
    }

    public function test_admin_can_save_all_languages_simultaneously(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@astro.com',
            'password' => bcrypt('password'),
        ]);

        $storeResponse = $this->actingAs($admin, 'admin')->post(route('admin.founder_words.store'), [
            'title_en' => 'Vinod Mishra EN',
            'message_en' => 'English message content',
            'title_hi' => 'विनोद मिश्रा HI',
            'message_hi' => 'हिन्दी संदेश सामग्री',
            'title_mr' => 'विनोद मिश्रा MR',
            'message_mr' => 'मराठी संदेश मजकूर',
            'is_active' => '1',
        ]);

        $storeResponse->assertRedirect(route('admin.founder_words.index'));
        $this->assertDatabaseHas('founders_words', [
            'title_en' => 'Vinod Mishra EN',
            'title_hi' => 'विनोद मिश्रा HI',
            'title_mr' => 'विनोद मिश्रा MR',
            'is_active' => true,
        ]);

        $word = FoundersWord::first();

        $updateResponse = $this->actingAs($admin, 'admin')->put(route('admin.founder_words.update', $word->id), [
            'title_en' => 'Vinod Mishra EN Updated',
            'message_en' => 'English message content updated',
            'title_hi' => 'विनोद मिश्रा HI Updated',
            'message_hi' => 'हिन्दी संदेश सामग्री updated',
            'title_mr' => 'विनोद मिश्रा MR Updated',
            'message_mr' => 'मराठी संदेश मजकूर updated',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.founder_words.index'));
        $this->assertDatabaseHas('founders_words', [
            'id' => $word->id,
            'title_en' => 'Vinod Mishra EN Updated',
            'title_hi' => 'विनोद मिश्रा HI Updated',
            'title_mr' => 'विनोद मिश्रा MR Updated',
        ]);
    }
}
