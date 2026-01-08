<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerProcess extends Model
{
    protected $fillable = [
        'metric_id',
        'pid',
        'username',
        'name',
        'cpu_percent',
        'memory_percent',
        'memory_rss',
        'status',
        'command',
    ];

    protected $casts = [
        'cpu_percent' => 'float',
        'memory_percent' => 'float',
    ];

    public function metric(): BelongsTo
    {
        return $this->belongsTo(ServerMetric::class, 'metric_id');
    }

    public function getMemoryFormattedAttribute(): string
    {
        $bytes = $this->memory_rss;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
