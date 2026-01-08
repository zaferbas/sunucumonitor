<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerMetric extends Model
{
    protected $fillable = [
        'server_id',
        'cpu_percent',
        'cpu_count',
        'cpu_user',
        'cpu_system',
        'cpu_iowait',
        'memory_total',
        'memory_used',
        'memory_available',
        'memory_percent',
        'swap_total',
        'swap_used',
        'swap_percent',
        'load_1',
        'load_5',
        'load_15',
        'uptime_seconds',
        'disks',
        'networks',
        'cpu_per_core',
        'recorded_at',
    ];

    protected $casts = [
        'disks' => 'array',
        'networks' => 'array',
        'cpu_per_core' => 'array',
        'recorded_at' => 'datetime',
        'cpu_percent' => 'float',
        'memory_percent' => 'float',
        'swap_percent' => 'float',
        'load_1' => 'float',
        'load_5' => 'float',
        'load_15' => 'float',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ServerProcess::class, 'metric_id');
    }

    public function getMemoryUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->memory_used);
    }

    public function getMemoryTotalFormattedAttribute(): string
    {
        return $this->formatBytes($this->memory_total);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
