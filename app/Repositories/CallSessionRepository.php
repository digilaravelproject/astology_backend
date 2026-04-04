<?php

namespace App\Repositories;

use App\Models\CallSession;

class CallSessionRepository
{
    public function findById($id)
    {
        return CallSession::with(['consumer', 'provider'])->find($id);
    }

    public function create(array $data)
    {
        return CallSession::create($data);
    }

    public function update($id, array $data)
    {
        return CallSession::where('id', $id)->update($data);
    }
    
    public function getActiveCallsByProvider($providerId)
    {
        return CallSession::where('provider_id', $providerId)
            ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
            ->get();
    }
}
