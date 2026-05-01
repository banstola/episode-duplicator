<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-API-KEY')) {
            return \response('MISSING_API_KEY', Response::HTTP_UNAUTHORIZED);
        }

        $apiKey = $request->header('X-API-KEY');

        if ($apiKey !== config('auth.api_key')) {
            return \response('UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);

    }
}
