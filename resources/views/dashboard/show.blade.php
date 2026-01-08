@extends('layouts.app')

@section('title', $server->name . ' - Sunucu Detayı')

@section('nav-stats')
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--chart-cpu);">{{ number_format($server->latestMetric?->cpu_percent ?? 0, 1) }}%</div>
        <div class="nav-stat-label">CPU</div>
    </div>
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--chart-memory);">{{ number_format($server->latestMetric?->memory_percent ?? 0, 1) }}%</div>
        <div class="nav-stat-label">RAM</div>
    </div>
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--chart-load);">{{ number_format($server->latestMetric?->load_1 ?? 0, 2) }}</div>
        <div class="nav-stat-label">Load</div>
    </div>
@endsection

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Sunucular</a>
        <span class="breadcrumb-separator">›</span>
        <span class="breadcrumb-current">{{ $server->name }}</span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="server-icon" style="width: 56px; height: 56px; font-size: 1.75rem;">🖥️</div>
            <div>
                <h1 class="page-title" style="margin-bottom: 0;">{{ $server->name }}</h1>
                <p style="color: var(--text-muted);">{{ $server->hostname }} • {{ $server->ip_address }}</p>
            </div>
        </div>
        <div class="server-status status-{{ $server->status }}" style="font-size: 0.875rem;">
            <span class="status-dot"></span>
            <span>{{ ucfirst($server->status) }}</span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid-4" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-card-value" style="color: var(--chart-cpu);">{{ number_format($server->latestMetric?->cpu_percent ?? 0, 1) }}%</div>
            <div class="stat-card-label">CPU Kullanımı</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value" style="color: var(--chart-memory);">{{ number_format($server->latestMetric?->memory_percent ?? 0, 1) }}%</div>
            <div class="stat-card-label">RAM Kullanımı</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value" style="color: var(--chart-load);">{{ number_format($server->latestMetric?->load_1 ?? 0, 2) }}</div>
            <div class="stat-card-label">Load Average (1m)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value" style="color: var(--text-primary);">{{ $server->uptime_formatted }}</div>
            <div class="stat-card-label">Uptime</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2" style="margin-bottom: 1.5rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">CPU & RAM Kullanımı</h3>
                <select id="timeRange" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                    <option value="1">Son 1 Saat</option>
                    <option value="6">Son 6 Saat</option>
                    <option value="24" selected>Son 24 Saat</option>
                </select>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="cpuMemoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Load Average</h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="loadChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom: 1.5rem;">
        <!-- Disk Kullanımı -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Disk Kullanımı</h3>
            </div>
            <div class="card-body">
                @forelse($server->latestMetric?->disks ?? [] as $disk)
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-weight: 500;">{{ $disk['mountpoint'] }}</span>
                            <span style="color: var(--text-muted);">{{ number_format($disk['percent'], 1) }}%</span>
                        </div>
                        <div class="progress-bar" style="height: 8px;">
                            <div class="progress-fill" style="width: {{ $disk['percent'] }}%; background: {{ $disk['percent'] > 90 ? 'var(--danger)' : ($disk['percent'] > 75 ? 'var(--warning)' : 'var(--success)') }};"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                            <span>{{ formatBytes($disk['used']) }} kullanıldı</span>
                            <span>{{ formatBytes($disk['total']) }} toplam</span>
                        </div>
                    </div>
                @empty
                    <p style="color: var(--text-muted);">Disk bilgisi yok</p>
                @endforelse
            </div>
        </div>

        <!-- Kullanıcı Özeti -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Kullanıcı Bazlı Kaynak Kullanımı</h3>
            </div>
            <div class="card-body table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Kullanıcı</th>
                            <th class="text-right">İşlem</th>
                            <th class="text-right">CPU %</th>
                            <th class="text-right">RAM %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userSummary as $user)
                            <tr>
                                <td><strong>{{ $user->username }}</strong></td>
                                <td class="text-right">{{ $user->process_count }}</td>
                                <td class="text-right" style="color: var(--chart-cpu);">{{ number_format($user->cpu_total, 1) }}%</td>
                                <td class="text-right" style="color: var(--chart-memory);">{{ number_format($user->memory_total, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Veri yok</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Process Listesi -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Aktif İşlemler (Top 10)</h3>
        </div>
        <div class="card-body table-container">
            <table>
                <thead>
                    <tr>
                        <th>PID</th>
                        <th>Kullanıcı</th>
                        <th>İsim</th>
                        <th class="text-right">CPU %</th>
                        <th class="text-right">RAM %</th>
                        <th class="text-right">RAM</th>
                        <th>Durum</th>
                        <th>Komut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($server->latestMetric?->processes ?? [] as $process)
                        <tr>
                            <td><code>{{ $process->pid }}</code></td>
                            <td><strong>{{ $process->username }}</strong></td>
                            <td>{{ $process->name }}</td>
                            <td class="text-right" style="color: var(--chart-cpu);">{{ number_format($process->cpu_percent, 1) }}%</td>
                            <td class="text-right" style="color: var(--chart-memory);">{{ number_format($process->memory_percent, 1) }}%</td>
                            <td class="text-right">{{ $process->memory_formatted }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'running' => 'success',
                                        'sleeping' => 'warning',
                                        'zombie' => 'danger',
                                    ];
                                    $color = $statusColors[strtolower($process->status)] ?? 'warning';
                                @endphp
                                <span class="badge badge-{{ $color }}">{{ $process->status }}</span>
                            </td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $process->command }}">
                                <code style="font-size: 0.75rem;">{{ Str::limit($process->command, 40) }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted);">İşlem verisi yok</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const chartColors = {
        cpu: '#6366f1',
        memory: '#22c55e',
        load: '#f59e0b',
        swap: '#ef4444',
        grid: '#27273a',
        text: '#71717a'
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: chartColors.text,
                    usePointStyle: true,
                    pointStyle: 'circle',
                }
            }
        },
        scales: {
            x: {
                grid: {
                    color: chartColors.grid,
                },
                ticks: {
                    color: chartColors.text,
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 12
                }
            },
            y: {
                grid: {
                    color: chartColors.grid,
                },
                ticks: {
                    color: chartColors.text,
                },
                min: 0,
                max: 100
            }
        }
    };

    let cpuMemoryChart, loadChart;

    async function loadChartData(hours = 24) {
        const response = await fetch(`{{ route('servers.chart-data', $server) }}?hours=${hours}`);
        const data = await response.json();
        return data;
    }

    async function initCharts() {
        const data = await loadChartData();

        // CPU & Memory Chart
        const cpuMemoryCtx = document.getElementById('cpuMemoryChart').getContext('2d');
        cpuMemoryChart = new Chart(cpuMemoryCtx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'CPU %',
                        data: data.cpu,
                        borderColor: chartColors.cpu,
                        backgroundColor: chartColors.cpu + '20',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 2
                    },
                    {
                        label: 'RAM %',
                        data: data.memory,
                        borderColor: chartColors.memory,
                        backgroundColor: chartColors.memory + '20',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 2
                    }
                ]
            },
            options: chartOptions
        });

        // Load Chart
        const loadCtx = document.getElementById('loadChart').getContext('2d');
        loadChart = new Chart(loadCtx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Load 1m',
                        data: data.load,
                        borderColor: chartColors.load,
                        backgroundColor: chartColors.load + '20',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                ...chartOptions,
                scales: {
                    ...chartOptions.scales,
                    y: {
                        ...chartOptions.scales.y,
                        max: undefined,
                        suggestedMax: {{ $server->latestMetric?->cpu_count ?? 4 }}
                    }
                }
            }
        });
    }

    document.getElementById('timeRange').addEventListener('change', async function() {
        const hours = parseInt(this.value);
        const data = await loadChartData(hours);

        cpuMemoryChart.data.labels = data.labels;
        cpuMemoryChart.data.datasets[0].data = data.cpu;
        cpuMemoryChart.data.datasets[1].data = data.memory;
        cpuMemoryChart.update();

        loadChart.data.labels = data.labels;
        loadChart.data.datasets[0].data = data.load;
        loadChart.update();
    });

    initCharts();

    // Her 60 saniyede bir yenile
    setTimeout(() => {
        window.location.reload();
    }, 60000);
</script>
@endsection

@php
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}
@endphp
