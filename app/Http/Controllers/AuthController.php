<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\JwtToken;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\LoginSecurityService;

class AuthController extends Controller
{
    protected $securityService;

    public function __construct(LoginSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    private function createAccessToken($user)
    {
        $customClaims = [
            'jti' => Str::uuid()->toString(),
            'type' => 'access'
        ];
        
        return JWTAuth::customClaims($customClaims)->fromUser($user);
    }

    private function createRefreshToken($user)
    {
        $customClaims = [
            'jti' => Str::uuid()->toString(),
            'type' => 'refresh'
        ];
        
        // Set longer TTL for refresh token
        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        
        // Reset to default TTL
        JWTAuth::factory()->setTTL(config('jwt.ttl'));
        
        return $token;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8', // |confirmed Added confirmed rule
        ]);

        // Check if email already exists
        $isUserExist = User::where('email', $data['email'])->first();
        if ($isUserExist) {
            return response()->json([
                'message' => 'Email is already registered',
                'errors' => [
                    'email' => ['The email has already been taken.']
                ]
            ], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        
        // Send verification email if required
        if (config('auth_security.email_verification.required')) {
            $this->sendVerificationEmail($user);
            
            return response()->json([
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 201);
        }
        
        // Generate token if email verification not required
        $token = $this->createAccessToken($user);
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
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

        $loginCheck = $this->securityService->checkLoginAllowed($data['email']);

        if (!$loginCheck['allowed']) {
            $this->securityService->logAttempt(
                $data['email'],
                false,
                'account_locked'
            );
            
            return response()->json([
                'error' => 'Account Locked',
                'message' => $loginCheck['message'],
                'retry_after' => $loginCheck['retry_after']
            ], 429);
        }

        // Find user
        $user = User::where('email', $data['email'])->first();
        
        // Check credentials
        if (!$user || !Hash::check($data['password'], $user->password)) {
            // Log failed attempt
            $this->securityService->logAttempt(
                $data['email'],
                false,
                $user ? 'invalid_password' : 'user_not_found'
            );
            
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if email is verified (if required)
        if (config('auth_security.email_verification.required') && !$user->email_verified_at) {
            $this->securityService->logAttempt(
                $data['email'],
                false,
                'email_not_verified'
            );
            
            return response()->json([
                'message' => 'Please verify your email address first'
            ], 403);
        }

        // Log successful attempt
        $this->securityService->logAttempt(
            $data['email'],
            true
        );

        // Generate tokens (using previous JWT implementation)
        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);
        $payload = JWTAuth::setToken($accessToken)->getPayload();

        JwtToken::create([
            'user_id' => $user->id,  // Use $user->id directly, not auth()
            'token_id' => $payload->get('jti'), // JWT ID from payload
            'token' => $accessToken,
            'device_name' => $data['device_name'] ?? 'Unknown Device',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => Carbon::createFromTimestamp($payload->get('exp')),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'user_id' => $user->id,
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    public function user()
    {
        return response()->json(auth('api')->user());
    }

    public function users()
    {
        $users = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users,
            'total' => $users->count()
        ]);
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
            
            if (!$token = JWTAuth::getToken()) {
                return response()->json(['message' => 'Missing bearer token'], 401);
            }


            $payload = JWTAuth::setToken($token)->getPayload();
            $tokenId = $payload->get('jti');
            
            // Mark token as revoked in database
            JwtToken::where('token_id', $tokenId)
                    ->where('user_id', auth('api')->user()->id)
                    ->update(['is_revoked' => true]);
            
            // Invalidate the JWT token (adds to blacklist)
            auth('api')->logout();
            
            return response()->json([
                'message' => 'Successfully logged out from this device'
            ], 201);
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
            /** @var \App\Models\User $user */
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
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout from all devices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sessions()
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $sessions = $user->activeTokens()->get()->map(function ($token) {
                return [
                    'id' => $token->id,
                    'device_name' => $token->device_name,
                    'ip_address' => $token->ip_address,
                    'user_agent' => $token->user_agent,
                    'last_used' => $token->updated_at,
                    'expires_at' => $token->expires_at,
                    'is_current' => request()->header('Authorization') && 
                                   str_contains($token->token, substr(request()->header('Authorization'), 7))
                ];
            });
            
            return response()->json([
                'sessions' => $sessions,
                'total' => $sessions->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revokeSession($sessionId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $token = $user->jwtTokens()->where('id', $sessionId)->first();
            
            if (!$token) {
                return response()->json([
                    'message' => 'Session not found'
                ], 404);
            }
            
            // Don't allow revoking current session through this endpoint
            $currentToken = request()->header('Authorization');
            if ($currentToken && str_contains($token->token, substr($currentToken, 7))) {
                return response()->json([
                    'message' => 'Cannot revoke current session. Use logout instead.'
                ], 400);
            }
            
            $token->update(['is_revoked' => true]);
            
            return response()->json([
                'message' => 'Session revoked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to revoke session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function loginHistory(Request $request)
    {
        $user = auth('api')->user();
        
        $history = LoginAttempt::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($attempt) {
                return [
                    'date' => $attempt->created_at->toDateTimeString(),
                    'ip_address' => $attempt->ip_address,
                    'user_agent' => $attempt->user_agent,
                    'successful' => $attempt->successful,
                    'failure_reason' => $attempt->failure_reason,
                ];
            });
        
        return response()->json([
            'history' => $history
        ]);
    }
}