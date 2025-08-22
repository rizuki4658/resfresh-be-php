<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\JwtToken;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtTokenCheck
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if token exists and is valid
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            $tokenId = $payload->get('jti');
            
            // Check if token is revoked in database
            $jwtToken = JwtToken::where('token_id', $tokenId)->first();
            
            if (!$jwtToken || $jwtToken->is_revoked) {
                return response()->json([
                    'message' => 'Token has been revoked'
                ], 401);
            }
            
            // Update last used timestamp
            $jwtToken->touch();
            
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token is invalid'
            ], 401);
        }
        
        return $next($request);
    }
}
