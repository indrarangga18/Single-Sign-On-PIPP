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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('nip')->nullable()->unique(); // Nomor Induk Pegawai
            $table->string('position')->nullable(); // Jabatan
            $table->string('department')->nullable(); // Departemen
            $table->string('office_location')->nullable(); // Lokasi Kantor
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->json('preferences')->nullable(); // User preferences
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['email', 'status']);
            $table->index(['username', 'status']);
            $table->index('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};