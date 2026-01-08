<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\MetricSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $servers = Server::with('latestMetric')->get();
        
        // Her sunucunun durumunu güncelle
        foreach ($servers as $server) {
            $server->updateStatus();
        }

        return view('dashboard.index', [
            'servers' => $servers,
        ]);
    }

    public function show(Server $server): View
    {
        $server->load('latestMetric.processes');
        $server->updateStatus();

        // Son 24 saatlik metrikler (grafik için)
        $metrics = $server->metrics()
            ->where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->get();

        // Kullanıcı bazlı özet
        $userSummary = [];
        if ($server->latestMetric) {
            $userSummary = $server->latestMetric->processes()
                ->selectRaw('username, COUNT(*) as process_count, SUM(cpu_percent) as cpu_total, SUM(memory_percent) as memory_total')
                ->groupBy('username')
                ->orderByDesc('cpu_total')
                ->get();
        }

        return view('dashboard.show', [
            'server' => $server,
            'metrics' => $metrics,
            'userSummary' => $userSummary,
        ]);
    }

    public function chartData(Server $server, Request $request)
    {
        $hours = $request->input('hours', 24);
        
        $metrics = $server->metrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'cpu_percent', 'memory_percent', 'load_1', 'swap_percent']);

        return response()->json([
            'labels' => $metrics->pluck('recorded_at')->map(fn($d) => $d->format('H:i')),
            'cpu' => $metrics->pluck('cpu_percent'),
            'memory' => $metrics->pluck('memory_percent'),
            'load' => $metrics->pluck('load_1'),
            'swap' => $metrics->pluck('swap_percent'),
        ]);
    }
}
