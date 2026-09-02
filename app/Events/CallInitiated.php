<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $callerData;

    /**
     * Create a new event instance.
     */
    public function __construct($session, $callerData)
    {
        $this->session = $session;
        $this->callerData = $callerData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->provider_id),
            new PrivateChannel('call.' . $this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallInitiated';
    }

    /**
     * Explicit payload so the frontend knows the call status and receives full consumer birth details
     * ('initiated' = direct ring, 'waiting' = queued behind busy astrologer).
     */
    public function broadcastWith(): array
    {
        $consumer = null;
        if ($this->callerData instanceof \App\Models\User) {
            $user = $this->callerData;
            $consumer = [
                'id'                  => (int) $user->id,
                'name'                => $user->name,
                'phone'               => $user->phone,
                'gender'              => $user->gender,
                'date_of_birth'       => $user->date_of_birth ? ($user->date_of_birth instanceof \Carbon\Carbon ? $user->date_of_birth->toISOString() : $user->date_of_birth) : null,
                'time_of_birth'       => $user->time_of_birth,
                'place_of_birth'      => $user->place_of_birth,
                'latitude'            => $user->latitude ? (float) $user->latitude : null,
                'longitude'           => $user->longitude ? (float) $user->longitude : null,
                'city'                => $user->city,
                'country'             => $user->country,
                'languages'           => $user->languages ?? [],
                'profile_photo'       => $user->profile_photo,
                'profile_photo_url'   => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'profile_completed'   => (bool) $user->profile_completed,
            ];
        } elseif (is_array($this->callerData)) {
            $consumer = $this->callerData;
            if (isset($consumer['profile_photo']) && !isset($consumer['profile_photo_url'])) {
                $consumer['profile_photo_url'] = \App\Helpers\MediaHelper::getUrl($consumer['profile_photo']);
            }
        }

        if (!$consumer && $this->session && $this->session->consumer) {
            $user = $this->session->consumer;
            $consumer = [
                'id'                  => (int) $user->id,
                'name'                => $user->name,
                'phone'               => $user->phone,
                'gender'              => $user->gender,
                'date_of_birth'       => $user->date_of_birth ? ($user->date_of_birth instanceof \Carbon\Carbon ? $user->date_of_birth->toISOString() : $user->date_of_birth) : null,
                'time_of_birth'       => $user->time_of_birth,
                'place_of_birth'      => $user->place_of_birth,
                'latitude'            => $user->latitude ? (float) $user->latitude : null,
                'longitude'           => $user->longitude ? (float) $user->longitude : null,
                'city'                => $user->city,
                'country'             => $user->country,
                'languages'           => $user->languages ?? [],
                'profile_photo'       => $user->profile_photo,
                'profile_photo_url'   => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'profile_completed'   => (bool) $user->profile_completed,
            ];
        }

        $subSession = \App\Models\PackageSubSession::where('call_session_id', $this->session->id)->first();
        $isPackage = !is_null($subSession) || (float) $this->session->rate_per_minute <= 0;

        return [
            'session' => [
                'id'              => (int) $this->session->id,
                'consumer_id'     => (int) $this->session->consumer_id,
                'provider_id'     => (int) $this->session->provider_id,
                'status'          => $this->session->status, // 'initiated' or 'waiting'
                'rate_per_minute' => (float) $this->session->rate_per_minute,
                'call_type'       => $this->session->call_type ?? 'audio',
                'is_package'      => $isPackage,
                'is_prepaid'      => $isPackage,
                'sub_session_id'  => $subSession?->id,
                'created_at'      => optional($this->session->created_at)?->toISOString(),
                'consumer'        => $consumer,
            ],
            'callerData'     => $consumer,
            'user'           => $consumer,
            'is_package'     => $isPackage,
            'is_prepaid'     => $isPackage,
            'sub_session_id' => $subSession?->id,
        ];
    }
}
