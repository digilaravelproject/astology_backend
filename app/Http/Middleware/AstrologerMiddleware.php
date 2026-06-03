<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AstrologerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'astrologer' || !$user->astrologer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. This account is not registered as an astrologer.'
            ], 403);
        }

        return $next($request);
    }
}
