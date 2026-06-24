<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Offer;
use App\Models\Astrologer;
use App\Models\Setting;
use App\Services\PricingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PricingCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $pricingCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingCalculator = app(PricingCalculatorService::class);
    }

    /** @test */
    public function it_calculates_correct_fallback_pricing_when_no_active_offer_exists()
    {
        // 1. Arrange settings
        Setting::updateOrCreate(['key' => 'global_admin_commission_rate'], ['value' => '25.00']);

        // 2. Create astrologer
        $user = User::factory()->create();
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'chat_rate_per_minute' => 100.00,
            'call_rate_per_minute' => 200.00,
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);

        // 3. Act
        $chatPricing = $this->pricingCalculator->calculate($astrologer, 'chat');
        $callPricing = $this->pricingCalculator->calculate($astrologer, 'call');

        // 4. Assert Chat Fallback (25% admin commission)
        $this->assertFalse($chatPricing['has_offer']);
        $this->assertEquals(100.00, $chatPricing['customer_rate']);
        $this->assertEquals(75.00, $chatPricing['astrologer_payout']);
        $this->assertEquals(25.00, $chatPricing['admin_revenue']);

        // Assert Call Fallback (25% admin commission)
        $this->assertFalse($callPricing['has_offer']);
        $this->assertEquals(200.00, $callPricing['customer_rate']);
        $this->assertEquals(150.00, $callPricing['astrologer_payout']);
        $this->assertEquals(50.00, $callPricing['admin_revenue']);
    }

    /** @test */
    public function it_calculates_correct_pricing_when_an_offer_is_active()
    {
        // 1. Create astrologer
        $user = User::factory()->create();
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'chat_rate_per_minute' => 100.00,
            'call_rate_per_minute' => 200.00,
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);

        // 2. Create and associate offer (20% off)
        // Chat split: Astro 70%, Admin 30%
        // Call split: Astro 60%, Admin 40%
        $offer = Offer::create([
            'name' => 'Test 20% OFF',
            'discount_percentage' => 20.00,
            'chat_astrologer_share' => 70.00,
            'chat_admin_share' => 30.00,
            'call_astrologer_share' => 60.00,
            'call_admin_share' => 40.00,
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        $astrologer->offers()->attach($offer->id, [
            'status' => 'active',
            'activated_at' => Carbon::now(),
        ]);

        // 3. Act
        $chatPricing = $this->pricingCalculator->calculate($astrologer, 'chat');
        $callPricing = $this->pricingCalculator->calculate($astrologer, 'call');

        // 4. Assert Chat (Original rate: 100, Discounted user rate: 80)
        // Astro share: 70% of 80 = 56
        // Admin share: 30% of 80 = 24
        $this->assertTrue($chatPricing['has_offer']);
        $this->assertEquals($offer->id, $chatPricing['offer_id']);
        $this->assertEquals(80.00, $chatPricing['customer_rate']);
        $this->assertEquals(56.00, $chatPricing['astrologer_payout']);
        $this->assertEquals(24.00, $chatPricing['admin_revenue']);

        // Assert Call (Original rate: 200, Discounted user rate: 160)
        // Astro share: 60% of 160 = 96
        // Admin share: 40% of 160 = 64
        $this->assertTrue($callPricing['has_offer']);
        $this->assertEquals($offer->id, $callPricing['offer_id']);
        $this->assertEquals(160.00, $callPricing['customer_rate']);
        $this->assertEquals(96.00, $callPricing['astrologer_payout']);
        $this->assertEquals(64.00, $callPricing['admin_revenue']);
    }

    /** @test */
    public function it_falls_back_when_an_offer_is_expired_or_inactive()
    {
        // 1. Arrange settings
        Setting::updateOrCreate(['key' => 'global_admin_commission_rate'], ['value' => '20.00']);

        // 2. Create astrologer
        $user = User::factory()->create();
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'chat_rate_per_minute' => 100.00,
            'call_rate_per_minute' => 200.00,
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);

        // 3. Create expired offer
        $expiredOffer = Offer::create([
            'name' => 'Expired Offer',
            'discount_percentage' => 50.00,
            'chat_astrologer_share' => 90.00,
            'chat_admin_share' => 10.00,
            'call_astrologer_share' => 90.00,
            'call_admin_share' => 10.00,
            'is_active' => true,
            'expires_at' => Carbon::now()->subMinutes(5), // expired
        ]);

        $astrologer->offers()->attach($expiredOffer->id, [
            'status' => 'active',
            'activated_at' => Carbon::now()->subHours(2),
        ]);

        // 4. Act
        $chatPricing = $this->pricingCalculator->calculate($astrologer, 'chat');

        // 5. Assert (Falls back to base rate since offer is expired)
        $this->assertFalse($chatPricing['has_offer']);
        $this->assertEquals(100.00, $chatPricing['customer_rate']);
        $this->assertEquals(80.00, $chatPricing['astrologer_payout']); // Astro share 80% (100 - 20% commission)
        $this->assertEquals(20.00, $chatPricing['admin_revenue']);
    }

    /** @test */
    public function it_returns_offer_pricing_in_astrologers_listing_and_details_apis()
    {
        // 1. Create astrologer
        $user = User::factory()->create(['name' => 'Offer Astrologer']);
        $astrologer = Astrologer::create([
            'user_id' => $user->id,
            'chat_rate_per_minute' => 100.00,
            'call_rate_per_minute' => 200.00,
            'chat_enabled' => true,
            'call_enabled' => true,
        ]);

        // 2. Create and associate offer (20% off)
        $offer = Offer::create([
            'name' => 'Big Discount 20%',
            'discount_percentage' => 20.00,
            'chat_astrologer_share' => 70.00,
            'chat_admin_share' => 30.00,
            'call_astrologer_share' => 60.00,
            'call_admin_share' => 40.00,
            'is_active' => true,
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        $astrologer->offers()->attach($offer->id, [
            'status' => 'active',
            'activated_at' => Carbon::now(),
        ]);

        // 3. Request listing API
        $response = $this->getJson('/api/v1/user/astrologers');
        $response->assertStatus(200);
        $data = $response->json('data.astrologers');
        
        $item = collect($data)->firstWhere('id', $astrologer->id);
        $this->assertNotNull($item);
        $this->assertEquals(80.00, $item['chat_rate_per_minute']);
        $this->assertEquals(160.00, $item['call_rate_per_minute']);
        $this->assertEquals(100.00, $item['original_chat_rate_per_minute']);
        $this->assertEquals(200.00, $item['original_call_rate_per_minute']);
        $this->assertTrue($item['has_offer']);
        $this->assertEquals('Big Discount 20%', $item['offer_details']['name']);

        // 4. Request show API
        $responseShow = $this->getJson("/api/v1/user/astrologers/{$astrologer->id}");
        $responseShow->assertStatus(200);
        $details = $responseShow->json('data.astrologer');
        
        $this->assertEquals(80.00, $details['chat_rate_per_minute']);
        $this->assertEquals(160.00, $details['call_rate_per_minute']);
        $this->assertEquals(100.00, $details['original_chat_rate_per_minute']);
        $this->assertEquals(200.00, $details['original_call_rate_per_minute']);
        $this->assertTrue($details['has_offer']);
        $this->assertEquals('Big Discount 20%', $details['offer_details']['name']);
    }
}
