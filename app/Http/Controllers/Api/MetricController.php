<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\ServerProcess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MetricController extends Controller
{
    /**
     * Metrik verisi al (POST /api/metrics)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'server_id' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'timestamp' => 'nullable|string',
            'uptime' => 'nullable|integer',
            'load_average' => 'nullable|array',
            'cpu' => 'nullable|array',
            'memory' => 'nullable|array',
            'disks' => 'nullable|array',
            'networks' => 'nullable|array',
            'processes' => 'nullable|array',
            'users' => 'nullable|array',
        ]);

        // Sunucuyu bul veya oluştur
        $server = Server::firstOrCreate(
            ['server_id' => $data['server_id']],
            [
                'name' => $data['hostname'] ?? $data['server_id'],
                'hostname' => $data['hostname'] ?? null,
                'ip_address' => $request->ip(),
            ]
        );

        // Sunucu bilgilerini güncelle
        $server->update([
            'hostname' => $data['hostname'] ?? $server->hostname,
            'ip_address' => $request->ip(),
            'last_seen_at' => now(),
            'status' => 'online',
        ]);

        // Timestamp parse
        $recordedAt = now();
        if (!empty($data['timestamp'])) {
            try {
                $recordedAt = Carbon::parse($data['timestamp']);
            } catch (\Exception $e) {
                // Geçersiz timestamp, şimdiki zamanı kullan
            }
        }

        // Metrik kaydet
        $metric = ServerMetric::create([
            'server_id' => $server->id,
            'cpu_percent' => $data['cpu']['percent'] ?? 0,
            'cpu_count' => $data['cpu']['count'] ?? 1,
            'cpu_user' => $data['cpu']['user'] ?? 0,
            'cpu_system' => $data['cpu']['system'] ?? 0,
            'cpu_iowait' => $data['cpu']['iowait'] ?? 0,
            'memory_total' => $data['memory']['total'] ?? 0,
            'memory_used' => $data['memory']['used'] ?? 0,
            'memory_available' => $data['memory']['available'] ?? 0,
            'memory_percent' => $data['memory']['percent'] ?? 0,
            'swap_total' => $data['memory']['swap_total'] ?? 0,
            'swap_used' => $data['memory']['swap_used'] ?? 0,
            'swap_percent' => $data['memory']['swap_percent'] ?? 0,
            'load_1' => $data['load_average']['load_1'] ?? 0,
            'load_5' => $data['load_average']['load_5'] ?? 0,
            'load_15' => $data['load_average']['load_15'] ?? 0,
            'uptime_seconds' => $data['uptime'] ?? 0,
            'disks' => $data['disks'] ?? [],
            'networks' => $data['networks'] ?? [],
            'cpu_per_core' => $data['cpu']['per_cpu'] ?? [],
            'recorded_at' => $recordedAt,
        ]);

        // Process'leri kaydet (top 10)
        if (!empty($data['processes'])) {
            $processes = array_slice($data['processes'], 0, 10);
            foreach ($processes as $proc) {
                ServerProcess::create([
                    'metric_id' => $metric->id,
                    'pid' => $proc['pid'] ?? 0,
                    'username' => $proc['username'] ?? 'unknown',
                    'name' => $proc['name'] ?? null,
                    'cpu_percent' => $proc['cpu_percent'] ?? 0,
                    'memory_percent' => $proc['memory_percent'] ?? 0,
                    'memory_rss' => $proc['memory_rss'] ?? 0,
                    'status' => $proc['status'] ?? null,
                    'command' => $proc['command'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Metrics received',
            'server_id' => $server->id,
            'metric_id' => $metric->id,
        ], 201);
    }

    /**
     * Sunucu listesi (GET /api/servers)
     */
    public function servers(): JsonResponse
    {
        $servers = Server::with('latestMetric')
            ->orderBy('name')
            ->get()
            ->map(function ($server) {
                $server->updateStatus();
                return [
                    'id' => $server->id,
                    'server_id' => $server->server_id,
                    'name' => $server->name,
                    'hostname' => $server->hostname,
                    'ip_address' => $server->ip_address,
                    'status' => $server->status,
                    'last_seen_at' => $server->last_seen_at?->toIso8601String(),
                    'uptime' => $server->uptime_formatted,
                    'latest_metric' => $server->latestMetric ? [
                        'cpu_percent' => $server->latestMetric->cpu_percent,
                        'memory_percent' => $server->latestMetric->memory_percent,
                        'load_1' => $server->latestMetric->load_1,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $servers,
        ]);
    }

    /**
     * Sunucu detayı (GET /api/servers/{id})
     */
    public function show(Server $server): JsonResponse
    {
        $server->load('latestMetric.processes');
        $server->updateStatus();

        return response()->json([
            'success' => true,
            'data' => [
                'server' => $server,
                'latest_metric' => $server->latestMetric,
                'processes' => $server->latestMetric?->processes ?? [],
            ],
        ]);
    }

    /**
     * Sunucu metrikleri (GET /api/servers/{id}/metrics)
     */
    public function metrics(Server $server, Request $request): JsonResponse
    {
        $hours = $request->input('hours', 24);
        $limit = min($request->input('limit', 1440), 2880); // Max 2 gün

        $metrics = $server->metrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($m) {
                return [
                    'recorded_at' => $m->recorded_at->toIso8601String(),
                    'cpu_percent' => $m->cpu_percent,
                    'memory_percent' => $m->memory_percent,
                    'swap_percent' => $m->swap_percent,
                    'load_1' => $m->load_1,
                    'load_5' => $m->load_5,
                    'load_15' => $m->load_15,
                    'disks' => $m->disks,
                    'networks' => $m->networks,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Kullanıcı bazlı özet (GET /api/servers/{id}/users)
     */
    public function users(Server $server): JsonResponse
    {
        $latestMetric = $server->latestMetric;
        
        if (!$latestMetric) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $users = $latestMetric->processes()
            ->selectRaw('username, COUNT(*) as process_count, SUM(cpu_percent) as cpu_total, SUM(memory_percent) as memory_total')
            ->groupBy('username')
            ->orderByDesc('cpu_total')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Health check (GET /api/health)
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
