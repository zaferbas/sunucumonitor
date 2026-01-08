<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $serverId = $request->header('X-Server-ID');
        
        // API key kontrolü (config'den veya .env'den)
        $validApiKey = config('monitor.api_key');
        
        if ($validApiKey && $apiKey !== $validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }
        
        return $next($request);
    }
}
