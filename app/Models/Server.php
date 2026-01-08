<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'server_id',
        'name',
        'hostname',
        'ip_address',
        'api_key',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(MetricSummary::class);
    }

    public function latestMetric()
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    public function updateStatus(): void
    {
        $lastSeen = $this->last_seen_at;
        
        if (!$lastSeen) {
            $this->status = 'offline';
        } elseif ($lastSeen->diffInMinutes(now()) > 5) {
            $this->status = 'offline';
        } elseif ($lastSeen->diffInMinutes(now()) > 2) {
            $this->status = 'warning';
        } else {
            $this->status = 'online';
        }
        
        $this->save();
    }

    public function getUptimeFormattedAttribute(): string
    {
        $metric = $this->latestMetric;
        if (!$metric) return '-';
        
        $seconds = $metric->uptime_seconds;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}g {$hours}s {$minutes}dk";
        } elseif ($hours > 0) {
            return "{$hours}s {$minutes}dk";
        }
        return "{$minutes}dk";
    }
}
