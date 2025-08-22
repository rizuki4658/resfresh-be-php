<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJwtTokensTable extends Migration
{
    public function up()
    {
        Schema::create('jwt_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token_id')->unique(); // JWT ID (jti claim)
            $table->text('token');
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable(); // Support IPv6
            $table->text('user_agent')->nullable(); // Changed to text for long user agents
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->index(['user_id', 'is_revoked']);
            $table->index('token_id');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jwt_tokens');
    }
}
