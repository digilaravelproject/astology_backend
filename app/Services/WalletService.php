<?php

namespace App\Services;

use App\Repositories\WalletRepository;

class WalletService
{
    /**
     * @var WalletRepository
     */
    protected $walletRepo;

    public function __construct(WalletRepository $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public function getBalance($userId): mixed
    {
        $wallet = $this->walletRepo->findByUserId($userId);
        return $wallet ? $wallet->balance : 0;
    }

    /**
     * @param int $userId
     * @param float $amount
     * @param int $callSessionId
     * @return \App\Models\WalletTransaction
     */
    public function deductForCall($userId, $amount, $callSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'call_deduction', 'App\Models\CallSession', $callSessionId);
    }

    /**
     * @param int $providerId
     * @param float $amount
     * @param int $callSessionId
     * @return \App\Models\WalletTransaction
     */
    public function creditProviderForCall($providerId, $amount, $callSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'call_credit', 'App\Models\CallSession', $callSessionId);
    }
    
    /**
     * @param int $userId
     * @param float $amount
     * @param int $chatSessionId
     * @return \App\Models\WalletTransaction
     */
    public function deductForChat($userId, $amount, $chatSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'chat_deduction', 'App\Models\ChatSession', $chatSessionId);
    }

    /**
     * @param int $providerId
     * @param float $amount
     * @param int $chatSessionId
     * @return \App\Models\WalletTransaction
     */
    public function creditProviderForChat($providerId, $amount, $chatSessionId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'chat_credit', 'App\Models\ChatSession', $chatSessionId);
    }

    /**
     * @param int $userId
     * @param float $amount
     * @param int $superChatId
     * @return \App\Models\WalletTransaction
     */
    public function deductForSuperChat($userId, $amount, $superChatId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'super_chat_deduction', 'App\Models\SuperChat', $superChatId);
    }

    /**
     * @param int $astrologerUserId
     * @param float $amount
     * @param int $superChatId
     * @return \App\Models\WalletTransaction
     */
    public function creditAstrologerForSuperChat($astrologerUserId, $amount, $superChatId): \App\Models\WalletTransaction
    {
        return $this->walletRepo->credit($astrologerUserId, $amount, 'super_chat_credit', 'App\Models\SuperChat', $superChatId);
    }

    /**
     * @param int $userId
     * @param int $astrologerUserId
     * @param float $amount
     * @param \App\Models\SuperChat $superChat
     * @return array
     */
    public function transferForSuperChat(int $userId, int $astrologerUserId, float $amount, \App\Models\SuperChat $superChat): array
    {
        return $this->walletRepo->transferForSuperChat($userId, $astrologerUserId, $amount, $superChat);
    }
}
