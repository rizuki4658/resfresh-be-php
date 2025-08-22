<?php

namespace PHPSTORM_META {

    // Inform PHPStorm about auth() and its return type
    override(\auth(0), map([
        'api' => \App\Models\User::class,
        'web' => \App\Models\User::class,
    ]));

    // Inform about specific methods
    override(\Illuminate\Contracts\Auth\Guard::user(0), map([
        '' => \App\Models\User::class,
    ]));
    
    override(\Illuminate\Contracts\Auth\Factory::guard(0), map([
        'api' => \Illuminate\Contracts\Auth\Guard::class,
        'web' => \Illuminate\Contracts\Auth\Guard::class,
    ]));
}