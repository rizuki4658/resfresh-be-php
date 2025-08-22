<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\UserLockout;
use Illuminate\Http\Request;

class LoginSecurityService
{
    protected $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Check if login is allowed for current request
     * @param string $email
     * @return array ['allowed' => bool, 'message' => string, 'retry_after' => int]
     */
    public function checkLoginAllowed($email)
    {
        $ip = $this->request->ip();
        
        // Check IP-based lockout
        if (config('auth_security.brute_force.track_by_ip')) {
            $ipLockout = $this->checkLockout($ip, 'ip');
            if (!$ipLockout['allowed']) {
                return $ipLockout;
            }
        }
        
        // Check email-based lockout
        if (config('auth_security.brute_force.track_by_email')) {
            $emailLockout = $this->checkLockout($email, 'email');
            if (!$emailLockout['allowed']) {
                return $emailLockout;
            }
        }
        
        // Check global threshold for IP
        $globalAttempts = LoginAttempt::recentByIp($ip, 60)->failed()->count();
        if ($globalAttempts >= config('auth_security.brute_force.global_threshold')) {
            return [
                'allowed' => false,
                'message' => 'Too many failed attempts from this IP address',
                'retry_after' => 3600 // 1 hour
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check lockout status for identifier
     */
    protected function checkLockout($identifier, $type)
    {
        $lockout = UserLockout::firstOrCreate(
            ['identifier' => $identifier, 'type' => $type],
            ['attempts' => 0]
        );
        
        if ($lockout->isLocked()) {
            return [
                'allowed' => false,
                'message' => 'Account is temporarily locked due to too many failed attempts',
                'retry_after' => $lockout->getRemainingLockoutTime()
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Log login attempt
     */
    public function logAttempt($email, $successful, $failureReason = null)
    {
        // Create audit log
        $attempt = LoginAttempt::create([
            'email' => $email,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'metadata' => [
                'method' => $this->request->method(),
                'url' => $this->request->fullUrl(),
                'referer' => $this->request->header('referer'),
                'session_id' => session()->getId(),
            ]
        ]);
        
        // Handle failed attempt
        if (!$successful) {
            $this->handleFailedAttempt($email);
        } else {
            $this->handleSuccessfulAttempt($email);
        }
        
        return $attempt;
    }
    
    /**
     * Handle failed login attempt
     */
    protected function handleFailedAttempt($email)
    {
        $ip = $this->request->ip();
        $config = config('auth_security.rate_limiting.login');
        
        // Update IP lockout
        if (config('auth_security.brute_force.track_by_ip')) {
            $ipLockout = UserLockout::firstOrCreate(
                ['identifier' => $ip, 'type' => 'ip']
            );
            $ipLockout->incrementAttempts(
                $config['max_attempts'],
                $config['lockout_minutes']
            );
        }
        
        // Update email lockout
        if (config('auth_security.brute_force.track_by_email')) {
            $emailLockout = UserLockout::firstOrCreate(
                ['identifier' => $email, 'type' => 'email']
            );
            $emailLockout->incrementAttempts(
                $config['max_attempts'],
                $config['lockout_minutes']
            );
            
            // Check if alert needed
            if ($emailLockout->attempts >= config('auth_security.brute_force.alert_threshold')) {
                $this->sendSecurityAlert($email, $emailLockout->attempts);
            }
        }
    }
    
    /**
     * Handle successful login
     */
    protected function handleSuccessfulAttempt($email)
    {
        $ip = $this->request->ip();
        
        // Reset IP lockout
        $ipLockout = UserLockout::where('identifier', $ip)
                   ->where('type', 'ip')
                   ->first();
        if ($ipLockout) {
            $ipLockout->resetAttempts();
        }
        
        // Reset email lockout
        $emailLockout = UserLockout::where('identifier', $email)
                   ->where('type', 'email')
                   ->first();
        if ($emailLockout) {
            $emailLockout->resetAttempts();
        }
    }
    
    /**
     * Send security alert (implement based on your notification system)
     */
    protected function sendSecurityAlert($email, $attempts)
    {
        // TODO: Implement email/slack notification
        \Log::warning("Security Alert: Multiple failed login attempts", [
            'email' => $email,
            'attempts' => $attempts,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent()
        ]);
    }
}