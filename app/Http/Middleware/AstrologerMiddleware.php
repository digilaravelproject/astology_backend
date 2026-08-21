<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AstrologerMiddleware
{
    /**
     * Handle an incoming request for astrologer (provider) endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Strict role validation
        if ($user->user_type !== 'astrologer') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. This account is not registered as an Astrologer. Please use the Astology Consumer app.',
                'error_code' => 'ROLE_MISMATCH_USER'
            ], 403);
        }

        $user->loadMissing('astrologer');

        if (!$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Astrologer profile not found for this account.',
                'error_code' => 'ASTROLOGER_PROFILE_NOT_FOUND'
            ], 403);
        }

        return $next($request);
    }
}
