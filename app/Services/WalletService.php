<?php

namespace App\Services;

use App\Repositories\WalletRepository;

class WalletService
{
    protected $walletRepo;

    public function __construct(WalletRepository $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    public function getBalance($userId): mixed
    {
        $wallet = $this->walletRepo->findByUserId($userId);
        return $wallet ? $wallet->balance : 0;
    }

    public function deductForCall($userId, $amount, $callSessionId): bool
    {
        return $this->walletRepo->debit($userId, $amount, 'call_deduction', 'App\Models\CallSession', $callSessionId);
    }

    public function creditProviderForCall($providerId, $amount, $callSessionId): bool
    {
        return $this->walletRepo->credit($providerId, $amount, 'call_credit', 'App\Models\CallSession', $callSessionId);
    }
    
    public function deductForChat($userId, $amount, $chatSessionId): bool
    {
        return $this->walletRepo->debit($userId, $amount, 'chat_deduction', 'App\Models\ChatSession', $chatSessionId);
    }

    public function creditProviderForChat($providerId, $amount, $chatSessionId): bool
    {
        return $this->walletRepo->credit($providerId, $amount, 'chat_credit', 'App\Models\ChatSession', $chatSessionId);
    }
}
