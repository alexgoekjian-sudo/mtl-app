<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        $token = null;

        // Check Authorization: Bearer <token>
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Fallback to X-API-TOKEN header
        if (!$token) {
            $token = $request->header('X-API-TOKEN');
        }

        if ($token) {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                // attach user to request
                $request->attributes->set('auth_user', $user);
                return $next($request);
            }
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
