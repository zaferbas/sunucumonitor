<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            
            // CPU
            $table->decimal('cpu_percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('cpu_count')->default(1);
            $table->decimal('cpu_user', 5, 2)->default(0);
            $table->decimal('cpu_system', 5, 2)->default(0);
            $table->decimal('cpu_iowait', 5, 2)->default(0);
            
            // Memory
            $table->unsignedBigInteger('memory_total')->default(0);
            $table->unsignedBigInteger('memory_used')->default(0);
            $table->unsignedBigInteger('memory_available')->default(0);
            $table->decimal('memory_percent', 5, 2)->default(0);
            
            // Swap
            $table->unsignedBigInteger('swap_total')->default(0);
            $table->unsignedBigInteger('swap_used')->default(0);
            $table->decimal('swap_percent', 5, 2)->default(0);
            
            // Load Average
            $table->decimal('load_1', 6, 2)->default(0);
            $table->decimal('load_5', 6, 2)->default(0);
            $table->decimal('load_15', 6, 2)->default(0);
            
            // Uptime
            $table->unsignedBigInteger('uptime_seconds')->default(0);
            
            // JSON alanları
            $table->json('disks')->nullable();
            $table->json('networks')->nullable();
            $table->json('cpu_per_core')->nullable();
            
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['server_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
