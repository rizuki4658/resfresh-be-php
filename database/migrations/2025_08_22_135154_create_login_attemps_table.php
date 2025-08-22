<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginAttempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable(); // Extra info
            $table->timestamps();

            // Indexes untuk query performance
            $table->index('email');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['ip_address', 'created_at']); // Composite index
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_attemps');
    }
}
