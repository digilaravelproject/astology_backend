<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request for standard user (consumer) endpoints.
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
        if ($user->user_type !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. This account is registered as an Astrologer and cannot access Consumer features. Please use the Astrologer app.',
                'error_code' => 'ROLE_MISMATCH_ASTROLOGER'
            ], 403);
        }

        return $next($request);
    }
}
