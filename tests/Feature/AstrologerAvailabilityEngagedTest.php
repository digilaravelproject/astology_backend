<?php

namespace Tests\Feature;

use App\Events\AstrologerAvailabilityUpdated;
use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\User;
use App\Services\AstrologerService;
use App\Services\PresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AstrologerAvailabilityEngagedTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $astrologerUser;
    protected Astrologer $astrologer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'user_type' => 'user',
            'is_online' => true,
        ]);

        $this->astrologerUser = User::factory()->create([
            'user_type' => 'astrologer',
            'is_online' => true,
            'is_busy'   => false,
        ]);

        $this->astrologer = Astrologer::create([
            'user_id'              => $this->astrologerUser->id,
            'display_name'         => 'Pt. Rahul Shastri',
            'is_online'            => true,
            'is_chat_enabled'      => true,
            'is_call_enabled'      => true,
            'chat_rate_per_minute' => 15.00,
            'call_rate_per_minute' => 20.00,
            'status'               => 'approved',
        ]);
    }

    /** @test */
    public function astrologer_availability_status_shows_online_when_idle()
    {
        $service = app(AstrologerService::class);
        $details = $service->getAstrologerDetails($this->astrologer->id, $this->user);

        $this->assertFalse((bool) $details->is_busy);
        $this->assertEquals('Online', $details->availability_status);

        $list = $service->listAstrologers([], $this->user);
        $matched = collect($list['astrologers'] ?? [])->firstWhere('id', $this->astrologer->id);
        $this->assertNotNull($matched);
        $this->assertFalse((bool) $matched->is_busy);
        $this->assertEquals('Online', $matched->availability_status);
    }

    /** @test */
    public function setting_busy_dispatches_real_time_event_and_sets_status_to_engaged()
    {
        Event::fake([AstrologerAvailabilityUpdated::class]);

        $presenceService = app(PresenceService::class);
        $presenceService->setBusy($this->astrologerUser->id, 999, 'call');

        // Verify Event Dispatched with 'Engaged' status
        Event::assertDispatched(AstrologerAvailabilityUpdated::class, function ($event) {
            return $event->userId === $this->astrologerUser->id
                && $event->astrologerId === $this->astrologer->id
                && $event->isBusy === true
                && $event->availabilityStatus === 'Engaged';
        });

        // Verify Database state
        $this->astrologerUser->refresh();
        $this->assertTrue((bool) $this->astrologerUser->is_busy);

        // Verify Active Session dynamic calculation
        CallSession::create([
            'consumer_id'     => $this->user->id,
            'provider_id'     => $this->astrologerUser->id,
            'status'          => 'ongoing',
            'rate_per_minute' => 20.00,
        ]);

        $service = app(AstrologerService::class);
        $details = $service->getAstrologerDetails($this->astrologer->id, $this->user);
        $this->assertTrue((bool) $details->is_busy);
        $this->assertEquals('Engaged', $details->availability_status);
    }

    /** @test */
    public function setting_free_dispatches_real_time_event_and_restores_online_status()
    {
        Event::fake([AstrologerAvailabilityUpdated::class]);

        $presenceService = app(PresenceService::class);
        $presenceService->setBusy($this->astrologerUser->id, 999, 'chat');
        $presenceService->setFree($this->astrologerUser->id);

        Event::assertDispatched(AstrologerAvailabilityUpdated::class, function ($event) {
            return $event->userId === $this->astrologerUser->id
                && $event->isBusy === false
                && $event->availabilityStatus === 'Online';
        });

        $this->astrologerUser->refresh();
        $this->assertFalse((bool) $this->astrologerUser->is_busy);

        $service = app(AstrologerService::class);
        $details = $service->getAstrologerDetails($this->astrologer->id, $this->user);
        $this->assertFalse((bool) $details->is_busy);
        $this->assertEquals('Online', $details->availability_status);
    }
}
