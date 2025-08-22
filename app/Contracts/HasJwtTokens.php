<?php

namespace App\Contracts;

interface HasJwtTokens
{
    /**
     * Get all JWT tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jwtTokens();

    /**
     * Get active JWT tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeTokens();

    /**
     * Revoke all user's JWT tokens.
     *
     * @return void
     */
    public function revokeAllTokens();
}