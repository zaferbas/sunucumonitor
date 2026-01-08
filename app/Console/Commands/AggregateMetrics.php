<?php

namespace App\Console\Commands;

use App\Models\MetricSummary;
use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateMetrics extends Command
{
    protected $signature = 'metrics:aggregate {--period=hourly : hourly or daily}';
    
    protected $description = 'Metrikleri saatlik veya günlük olarak özetle';

    public function handle(): int
    {
        $period = $this->option('period');
        
        if (!in_array($period, ['hourly', 'daily'])) {
            $this->error('Period must be hourly or daily');
            return 1;
        }

        $this->info("Aggregating {$period} metrics...");

        $servers = Server::all();
        $count = 0;

        foreach ($servers as $server) {
            if ($period === 'hourly') {
                $this->aggregateHourly($server);
            } else {
                $this->aggregateDaily($server);
            }
            $count++;
        }

        $this->info("Aggregated metrics for {$count} servers.");
        return 0;
    }

    protected function aggregateHourly(Server $server): void
    {
        // Son 1 saatin metriklerini al
        $periodStart = now()->subHour()->startOfHour();
        $periodEnd = $periodStart->copy()->endOfHour();

        // Zaten varsa atla
        if (MetricSummary::where('server_id', $server->id)
            ->where('period', 'hourly')
            ->where('period_start', $periodStart)
            ->exists()) {
            return;
        }

        $metrics = ServerMetric::where('server_id', $server->id)
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->get();

        if ($metrics->isEmpty()) {
            return;
        }

        MetricSummary::create([
            'server_id' => $server->id,
            'period' => 'hourly',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'cpu_avg' => $metrics->avg('cpu_percent'),
            'cpu_max' => $metrics->max('cpu_percent'),
            'cpu_min' => $metrics->min('cpu_percent'),
            'memory_avg' => $metrics->avg('memory_percent'),
            'memory_max' => $metrics->max('memory_percent'),
            'memory_min' => $metrics->min('memory_percent'),
            'load_avg' => $metrics->avg('load_1'),
            'load_max' => $metrics->max('load_1'),
            'sample_count' => $metrics->count(),
        ]);

        $this->line("  - {$server->name}: hourly summary created");
    }

    protected function aggregateDaily(Server $server): void
    {
        // Dünün metriklerini özetle
        $periodStart = now()->subDay()->startOfDay();
        $periodEnd = $periodStart->copy()->endOfDay();

        // Zaten varsa atla
        if (MetricSummary::where('server_id', $server->id)
            ->where('period', 'daily')
            ->where('period_start', $periodStart)
            ->exists()) {
            return;
        }

        // Saatlik özetlerden günlük özet oluştur
        $hourlySummaries = MetricSummary::where('server_id', $server->id)
            ->where('period', 'hourly')
            ->whereBetween('period_start', [$periodStart, $periodEnd])
            ->get();

        if ($hourlySummaries->isEmpty()) {
            return;
        }

        MetricSummary::create([
            'server_id' => $server->id,
            'period' => 'daily',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'cpu_avg' => $hourlySummaries->avg('cpu_avg'),
            'cpu_max' => $hourlySummaries->max('cpu_max'),
            'cpu_min' => $hourlySummaries->min('cpu_min'),
            'memory_avg' => $hourlySummaries->avg('memory_avg'),
            'memory_max' => $hourlySummaries->max('memory_max'),
            'memory_min' => $hourlySummaries->min('memory_min'),
            'load_avg' => $hourlySummaries->avg('load_avg'),
            'load_max' => $hourlySummaries->max('load_max'),
            'sample_count' => $hourlySummaries->sum('sample_count'),
        ]);

        $this->line("  - {$server->name}: daily summary created");
    }
}
