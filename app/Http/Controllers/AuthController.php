<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\JwtToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // Check if user already exists
        if (User::where('email', $data['email'])->exists()) {
            return response()->json([
                'message' => 'The email has been taken'
            ], 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device_name' => 'sometimes|string|max:255' // optional
        ]);

        $token = auth('api')->attempt([
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        if (!$token) {
            return response()->json([
                'message' => 'Incorrect email or password!',
            ], 401);
        }

        // Get token payload to extract jti (JWT ID)
        $payload = auth('api')->payload();

        // Store token information
        JwtToken::create([
            'user_id' => auth('api')->user()->id,
            'token_id' => $payload->get('jti'), // JWT ID from payload
            'token' => $token,
            'device_name' => $data['device_name'] ?? 'Unknown Device',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => Carbon::createFromTimestamp($payload->get('exp')),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ], 200);
    }

    public function user()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        try {
            // Check if user is authenticated
            if (!auth('api')->check()) {
                return response()->json([
                    'message' => 'You are not logged in.'
                ], 401);
            }

            $payload = auth('api')->payload();
            $tokenId = $payload->get('jti');
            
            // Mark token as revoked in database
            JwtToken::where('token_id', $tokenId)
                    ->where('user_id', auth('api')->user()->id)
                    ->update(['is_revoked' => true]);
            
            // Invalidate the JWT token (adds to blacklist)
            auth('api')->logout();
            
            return response()->json([
                'message' => 'Successfully logged out from this device'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logoutAll()
    {
        try {
            $user = auth('api')->user();
            
            // Revoke all tokens for this user
            $user->revokeAllTokens();
            
            // Also invalidate current JWT token
            auth('api')->logout();
            
            // Optional: Clear all JWT blacklist entries for this user
            // This would require custom implementation
            
            return response()->json([
                'message' => 'Successfully logged out from all devices',
                'revoked_count' => $user->jwtTokens()->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout from all devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sessions()
    {
        $user = auth('api')->user();
        
        $sessions = $user->activeTokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'device_name' => $token->device_name,
                'ip_address' => $token->ip_address,
                'user_agent' => $token->user_agent,
                'last_used' => $token->updated_at,
                'expires_at' => $token->expires_at,
            ];
        });
        
        return response()->json([
            'sessions' => $sessions,
            'total' => $sessions->count()
        ]);
    }
}