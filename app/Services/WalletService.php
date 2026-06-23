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

    public function deductForCall($userId, $amount, $callSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'call_deduction', 'App\Models\CallSession', $callSessionId);
    }

    public function creditProviderForCall($providerId, $amount, $callSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'call_credit', 'App\Models\CallSession', $callSessionId);
    }
    
    public function deductForChat($userId, $amount, $chatSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'chat_deduction', 'App\Models\ChatSession', $chatSessionId);
    }

    public function creditProviderForChat($providerId, $amount, $chatSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'chat_credit', 'App\Models\ChatSession', $chatSessionId);
    }

    public function deductForSuperChat($userId, $amount, $superChatId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'super_chat_deduction', 'App\Models\SuperChat', $superChatId);
    }

    public function creditAstrologerForSuperChat($astrologerUserId, $amount, $superChatId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($astrologerUserId, $amount, 'super_chat_credit', 'App\Models\SuperChat', $superChatId);
    }

    public function transferForSuperChat(int $userId, int $astrologerUserId, float $amount, \App\Models\SuperChat $superChat): array
    {
        return $this->walletRepo->transferForSuperChat($userId, $astrologerUserId, $amount, $superChat);
    }
}
