<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSummary extends Model
{
    protected $fillable = [
        'server_id',
        'period',
        'period_start',
        'period_end',
        'cpu_avg',
        'cpu_max',
        'cpu_min',
        'memory_avg',
        'memory_max',
        'memory_min',
        'load_avg',
        'load_max',
        'sample_count',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'cpu_avg' => 'float',
        'cpu_max' => 'float',
        'cpu_min' => 'float',
        'memory_avg' => 'float',
        'memory_max' => 'float',
        'memory_min' => 'float',
        'load_avg' => 'float',
        'load_max' => 'float',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
