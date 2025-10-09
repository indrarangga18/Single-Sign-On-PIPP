<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sso_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('service_name'); // sahbandar, spb, shti, epit
            $table->string('service_url');
            $table->string('client_ip');
            $table->string('user_agent');
            $table->json('session_data')->nullable();
            $table->timestamp('last_activity');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'service_name']);
            $table->index(['session_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_sessions');
    }
};