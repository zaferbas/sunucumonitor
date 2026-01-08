<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('server_id')->unique();
            $table->string('name')->nullable();
            $table->string('hostname')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('api_key')->nullable();
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
