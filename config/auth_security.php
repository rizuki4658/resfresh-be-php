<?php
  return [
    'rate_limiting' => [
        // Login attempts configuration
        'login' => [
            'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('LOGIN_DECAY_MINUTES', 1),
            'lockout_minutes' => env('LOGIN_LOCKOUT_MINUTES', 15),
        ],
        
        // Different limits for different endpoints
        'endpoints' => [
            'login' => '5,1',      // 5 attempts per 1 minute
            'register' => '3,10',   // 3 registrations per 10 minutes
            'password_reset' => '3,60', // 3 reset requests per hour
            'refresh_token' => '10,1',  // 10 refresh per minute
        ],
    ],
    
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_successful' => env('AUDIT_LOG_SUCCESS', true),
        'log_failed' => env('AUDIT_LOG_FAILED', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    ],
    
    'brute_force' => [
        'track_by_ip' => env('TRACK_BY_IP', true),
        'track_by_email' => env('TRACK_BY_EMAIL', true),
        'global_threshold' => env('GLOBAL_THRESHOLD', 20), // Total attempts from single IP
        'alert_threshold' => env('ALERT_THRESHOLD', 10),   // Send alert after X failures
    ],
    
    'email_verification' => [
        'required' => env('EMAIL_VERIFICATION_REQUIRED', false),
        'expire_hours' => env('EMAIL_VERIFICATION_EXPIRE', 24),
    ],
  ];