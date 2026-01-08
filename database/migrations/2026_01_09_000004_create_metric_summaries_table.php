<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            
            $table->enum('period', ['hourly', 'daily'])->default('hourly');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            
            // CPU averages
            $table->decimal('cpu_avg', 5, 2)->default(0);
            $table->decimal('cpu_max', 5, 2)->default(0);
            $table->decimal('cpu_min', 5, 2)->default(0);
            
            // Memory averages
            $table->decimal('memory_avg', 5, 2)->default(0);
            $table->decimal('memory_max', 5, 2)->default(0);
            $table->decimal('memory_min', 5, 2)->default(0);
            
            // Load averages
            $table->decimal('load_avg', 6, 2)->default(0);
            $table->decimal('load_max', 6, 2)->default(0);
            
            // Sample count
            $table->unsignedInteger('sample_count')->default(0);
            
            $table->timestamps();
            
            $table->unique(['server_id', 'period', 'period_start']);
            $table->index(['server_id', 'period', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_summaries');
    }
};
