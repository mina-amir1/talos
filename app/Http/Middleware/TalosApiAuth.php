<?php

namespace App\Http\Middleware;

use App\Models\TalosApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TalosApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Talos-Token')
            ?? $request->query('token');

        if (! $token) {
            return response()->json(['error' => 'Unauthenticated. Provide a Bearer token.'], 401);
        }

        $apiToken = TalosApiToken::where('token', hash('sha256', $token))->first();

        if (! $apiToken || $apiToken->isExpired()) {
            return response()->json(['error' => 'Invalid or expired API token.'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);

        $request->attributes->set('talos_token', $apiToken);

        return $next($request);
    }
}
