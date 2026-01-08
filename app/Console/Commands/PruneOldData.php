<?php

namespace App\Console\Commands;

use App\Models\MetricSummary;
use App\Models\ServerMetric;
use App\Models\ServerProcess;
use Illuminate\Console\Command;

class PruneOldData extends Command
{
    protected $signature = 'metrics:prune {--dry-run : Silmeden sadece sayıları göster}';
    
    protected $description = 'Eski metrikleri ve process verilerini sil';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $config = config('monitor.retention');

        $this->info('Pruning old data...');
        if ($dryRun) {
            $this->warn('DRY RUN - No data will be deleted');
        }

        // Ham metrikler
        $rawDate = now()->subDays($config['raw_metrics']);
        $rawCount = ServerMetric::where('recorded_at', '<', $rawDate)->count();
        $this->line("  Raw metrics older than {$config['raw_metrics']} days: {$rawCount}");
        
        if (!$dryRun && $rawCount > 0) {
            // Önce ilişkili process'leri sil
            $metricIds = ServerMetric::where('recorded_at', '<', $rawDate)->pluck('id');
            ServerProcess::whereIn('metric_id', $metricIds)->delete();
            ServerMetric::where('recorded_at', '<', $rawDate)->delete();
            $this->info("  Deleted {$rawCount} raw metrics");
        }

        // Saatlik özetler
        $hourlyDate = now()->subDays($config['hourly_summary']);
        $hourlyCount = MetricSummary::where('period', 'hourly')
            ->where('period_start', '<', $hourlyDate)
            ->count();
        $this->line("  Hourly summaries older than {$config['hourly_summary']} days: {$hourlyCount}");
        
        if (!$dryRun && $hourlyCount > 0) {
            MetricSummary::where('period', 'hourly')
                ->where('period_start', '<', $hourlyDate)
                ->delete();
            $this->info("  Deleted {$hourlyCount} hourly summaries");
        }

        // Günlük özetler
        $dailyDate = now()->subDays($config['daily_summary']);
        $dailyCount = MetricSummary::where('period', 'daily')
            ->where('period_start', '<', $dailyDate)
            ->count();
        $this->line("  Daily summaries older than {$config['daily_summary']} days: {$dailyCount}");
        
        if (!$dryRun && $dailyCount > 0) {
            MetricSummary::where('period', 'daily')
                ->where('period_start', '<', $dailyDate)
                ->delete();
            $this->info("  Deleted {$dailyCount} daily summaries");
        }

        $this->info('Prune complete.');
        return 0;
    }
}
