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
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->index(['user_id', 'is_revoked']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('jwt_tokens');
    }
}
