<?php

namespace App\Services;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\ServerError;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayService
{
    protected $api;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('razorpay.key_id');
        $this->keySecret = config('razorpay.key_secret');

        try {
            $this->api = new Api($this->keyId, $this->keySecret);
        } catch (Exception $e) {
            Log::error('Razorpay API initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a Razorpay order for payment
     *
     * @param int $amount Amount in paise (e.g., 1000 paise = Rs. 10)
     * @param string $currency Currency code (default: INR)
     * @param string $receipt Receipt number (optional)
     * @param array $notes Additional notes (optional)
     * @return array Order details or error
     */
    public function createOrder($amount, $currency = 'INR', $receipt = null, $notes = [])
    {
        try {
            $data = [
                'amount' => $amount,
                'currency' => $currency,
                'receipt' => $receipt ?? uniqid('rcpt_'),
                'notes' => $notes,
            ];

            $order = $this->api->order->create($data);

            Log::info('Razorpay order created', [
                'order_id' => $order['id'],
                'amount' => $amount,
            ]);

            return [
                'status' => 'success',
                'data' => $order->toArray(),
            ];
        } catch (BadRequestError $e) {
            Log::error('Razorpay BadRequestError: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        } catch (ServerError $e) {
            Log::error('Razorpay ServerError: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Payment gateway error. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Razorpay Exception: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment signature
     *
     * @param string $orderId Razorpay order ID
     * @param string $paymentId Razorpay payment ID
     * @param string $signature Razorpay signature
     * @return bool True if signature is valid
     */
    public function verifySignature($orderId, $paymentId, $signature)
    {
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            Log::info('Razorpay signature verified', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Razorpay signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment details from Razorpay
     *
     * @param string $paymentId Razorpay payment ID
     * @return array Payment details or error
     */
    public function getPayment($paymentId)
    {
        try {
            $payment = $this->api->payment->fetch($paymentId);

            return [
                'status' => 'success',
                'data' => $payment->toArray(),
            ];
        } catch (Exception $e) {
            Log::error('Razorpay payment fetch failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order details from Razorpay
     *
     * @param string $orderId Razorpay order ID
     * @return array Order details or error
     */
    public function getOrder($orderId)
    {
        try {
            $order = $this->api->order->fetch($orderId);

            return [
                'status' => 'success',
                'data' => $order->toArray(),
            ];
        } catch (Exception $e) {
            Log::error('Razorpay order fetch failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get API instance
     */
    public function getApi()
    {
        return $this->api;
    }
}
