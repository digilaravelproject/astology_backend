<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    /**
     * Get all static pages (public endpoint).
     */
    public function index(): JsonResponse
    {
        $pages = StaticPage::where('is_active', true)
            ->select('id', 'type', 'title', 'content', 'created_at', 'updated_at')
            ->orderBy('type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['pages' => $pages],
        ], 200);
    }

    /**
     * Get a specific static page by type (faqs, privacy_policy, terms_and_conditions, payment_policy).
     */
    public function show($type): JsonResponse
    {
        $page = StaticPage::where('type', $type)
            ->where('is_active', true)
            ->select('id', 'type', 'title', 'content', 'created_at', 'updated_at')
            ->first();

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Static page not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['page' => $page],
        ], 200);
    }

    /**
     * Get FAQs specifically.
     */
    public function getFaqs(): JsonResponse
    {
        return $this->show('faq');
    }

    /**
     * Get privacy policy.
     */
    public function getPrivacyPolicy(): JsonResponse
    {
        return $this->show('privacy_policy');
    }

    /**
     * Get terms and conditions.
     */
    public function getTermsAndConditions(): JsonResponse
    {
        return $this->show('terms_and_conditions');
    }

    /**
     * Get payment policy.
     */
    public function getPaymentPolicy(): JsonResponse
    {
        return $this->show('payment_policy');
    }

    /**
     * Get about us.
     */
    public function getAboutUs(): JsonResponse
    {
        return $this->show('about_us');
    }

    /**
     * Get customer support.
     */
    public function getCustomerSupport(): JsonResponse
    {
        return $this->show('customer_support');
    }
}
