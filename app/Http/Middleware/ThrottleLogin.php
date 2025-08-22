<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleLogin
{
    protected $limiter;
    
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }
    
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
    
    protected function resolveRequestSignature(Request $request)
    {
        // Create unique key based on IP + endpoint
        return sha1(
            $request->ip() . '|' . $request->path() . '|' . $request->method()
        );
    }
    
    protected function buildResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        return response()->json([
            'error' => 'Too Many Attempts',
            'message' => 'You have exceeded the maximum number of attempts. Please try again later.',
            'retry_after' => $retryAfter,
            'retry_after_readable' => $this->humanReadableTime($retryAfter)
        ], Response::HTTP_TOO_MANY_REQUESTS)
        ->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }
    
    protected function addHeaders($response, $maxAttempts, $remainingAttempts)
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
    }
    
    protected function calculateRemainingAttempts($key, $maxAttempts)
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }
    
    protected function humanReadableTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds > 0) {
            return $minutes . ' minutes and ' . $remainingSeconds . ' seconds';
        }
        
        return $minutes . ' minutes';
    }
}
