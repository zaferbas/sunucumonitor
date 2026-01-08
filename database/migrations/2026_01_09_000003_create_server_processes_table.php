<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained('server_metrics')->onDelete('cascade');
            
            $table->unsignedInteger('pid');
            $table->string('username', 64);
            $table->string('name')->nullable();
            $table->decimal('cpu_percent', 6, 2)->default(0);
            $table->decimal('memory_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('memory_rss')->default(0);
            $table->string('status', 10)->nullable();
            $table->text('command')->nullable();
            
            $table->timestamps();
            
            $table->index(['metric_id', 'username']);
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_processes');
    }
};
