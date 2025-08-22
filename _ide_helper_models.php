<?php

/**
 * IDE Helper file for better autocomplete
 * This file is not included in runtime
 */

namespace App\Models {
    
    /**
     * App\Models\User
     *
     * @method \Illuminate\Database\Eloquent\Relations\HasMany jwtTokens()
     * @method \Illuminate\Database\Eloquent\Relations\HasMany activeTokens()
     * @method void revokeAllTokens()
     */
    class User {}
}

namespace {
    /**
     * @method \App\Models\User|null user()
     */
    class AuthGuard {}
}