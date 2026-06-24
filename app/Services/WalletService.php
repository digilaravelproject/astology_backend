<?php

namespace App\Services;

use App\Models\SuperChat;
use App\Models\WalletTransaction;
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
     * @param  int  $userId
     */
    public function getBalance($userId): mixed
    {
        $wallet = $this->walletRepo->findByUserId($userId);

        return $wallet ? $wallet->balance : 0;
    }

    /**
     * @param  int  $userId
     * @param  float  $amount
     * @param  int  $callSessionId
     */
    public function deductForCall($userId, $amount, $callSessionId): WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'call_deduction', 'App\Models\CallSession', $callSessionId);
    }

    /**
     * @param  int  $providerId
     * @param  float  $amount
     * @param  int  $callSessionId
     */
    public function creditProviderForCall($providerId, $amount, $callSessionId): WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'call_credit', 'App\Models\CallSession', $callSessionId);
    }

    /**
     * @param  int  $userId
     * @param  float  $amount
     * @param  int  $chatSessionId
     */
    public function deductForChat($userId, $amount, $chatSessionId): WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'chat_deduction', 'App\Models\ChatSession', $chatSessionId);
    }

    /**
     * @param  int  $providerId
     * @param  float  $amount
     * @param  int  $chatSessionId
     */
    public function creditProviderForChat($providerId, $amount, $chatSessionId): WalletTransaction
    {
        return $this->walletRepo->credit($providerId, $amount, 'chat_credit', 'App\Models\ChatSession', $chatSessionId);
    }

    /**
     * @param  int  $userId
     * @param  float  $amount
     * @param  int  $superChatId
     */
    public function deductForSuperChat($userId, $amount, $superChatId): WalletTransaction
    {
        return $this->walletRepo->debit($userId, $amount, 'super_chat_deduction', 'App\Models\SuperChat', $superChatId);
    }

    /**
     * @param  int  $astrologerUserId
     * @param  float  $amount
     * @param  int  $superChatId
     */
    public function creditAstrologerForSuperChat($astrologerUserId, $amount, $superChatId): WalletTransaction
    {
        return $this->walletRepo->credit($astrologerUserId, $amount, 'super_chat_credit', 'App\Models\SuperChat', $superChatId);
    }

    public function transferForSuperChat(int $userId, int $astrologerUserId, float $amount, SuperChat $superChat): array
    {
        return $this->walletRepo->transferForSuperChat($userId, $astrologerUserId, $amount, $superChat);
    }

    /**
     * @param  int  $userId
     * @param  float  $amount
     */
    public function debitBalanceOnly($userId, $amount): bool
    {
        return $this->walletRepo->debitBalanceOnly($userId, $amount);
    }

    /**
     * @param  int  $userId
     * @param  float  $amount
     */
    public function creditBalanceOnly($userId, $amount): bool
    {
        return $this->walletRepo->creditBalanceOnly($userId, $amount);
    }

    /**
     * Create a debit transaction log without changing the wallet balance (used for consolidation).
     *
     * @param  int  $userId
     * @param  float  $amount
     * @param  string  $description
     */
    public function logDebitOnly($userId, $amount, $description, $referenceType = null, $referenceId = null): WalletTransaction
    {
        return $this->walletRepo->logDebitOnly($userId, $amount, $description, $referenceType, $referenceId);
    }

    /**
     * Create a credit transaction log without changing the wallet balance (used for consolidation).
     *
     * @param  int  $userId
     * @param  float  $amount
     * @param  string  $description
     */
    public function logCreditOnly($userId, $amount, $description, $referenceType = null, $referenceId = null): WalletTransaction
    {
        return $this->walletRepo->logCreditOnly($userId, $amount, $description, $referenceType, $referenceId);
    }
}
