@extends('layouts.app')

@section('title', 'Sunucu Listesi')

@section('nav-stats')
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--success);">{{ $servers->where('status', 'online')->count() }}</div>
        <div class="nav-stat-label">Online</div>
    </div>
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--warning);">{{ $servers->where('status', 'warning')->count() }}</div>
        <div class="nav-stat-label">Uyarı</div>
    </div>
    <div class="nav-stat">
        <div class="nav-stat-value" style="color: var(--danger);">{{ $servers->where('status', 'offline')->count() }}</div>
        <div class="nav-stat-label">Offline</div>
    </div>
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 class="page-title">Sunucular</h1>
        <div class="refresh-indicator">
            <span class="refresh-dot"></span>
            <span>Otomatik yenileme aktif</span>
        </div>
    </div>

    @if($servers->isEmpty())
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">🖥️</div>
                <h3>Henüz sunucu yok</h3>
                <p>Collector'ı bir sunucuya kurduğunuzda burada görünecek.</p>
            </div>
        </div>
    @else
        <div class="server-grid">
            @foreach($servers as $server)
                <a href="{{ route('servers.show', $server) }}" class="server-card">
                    <div class="card">
                        <div class="card-body">
                            <div class="server-header">
                                <div class="server-icon">🖥️</div>
                                <div class="server-info">
                                    <h3>{{ $server->name ?? $server->server_id }}</h3>
                                    <p>{{ $server->hostname ?? $server->ip_address ?? '-' }}</p>
                                </div>
                                <div class="server-status status-{{ $server->status }}">
                                    <span class="status-dot"></span>
                                    <span>{{ ucfirst($server->status) }}</span>
                                </div>
                            </div>

                            @if($server->latestMetric)
                                <div class="metrics-row">
                                    <div class="metric-item metric-cpu">
                                        <div class="metric-value">{{ number_format($server->latestMetric->cpu_percent, 1) }}%</div>
                                        <div class="metric-label">CPU</div>
                                        <div class="progress-bar">
                                            <div class="progress-fill progress-cpu" style="width: {{ min($server->latestMetric->cpu_percent, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="metric-item metric-memory">
                                        <div class="metric-value">{{ number_format($server->latestMetric->memory_percent, 1) }}%</div>
                                        <div class="metric-label">RAM</div>
                                        <div class="progress-bar">
                                            <div class="progress-fill progress-memory" style="width: {{ min($server->latestMetric->memory_percent, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="metric-item metric-load">
                                        <div class="metric-value">{{ number_format($server->latestMetric->load_1, 2) }}</div>
                                        <div class="metric-label">Load</div>
                                    </div>
                                    <div class="metric-item metric-disk">
                                        @php
                                            $mainDisk = collect($server->latestMetric->disks ?? [])->first();
                                        @endphp
                                        <div class="metric-value">{{ $mainDisk ? number_format($mainDisk['percent'], 0) : '-' }}%</div>
                                        <div class="metric-label">Disk</div>
                                    </div>
                                </div>
                            @else
                                <div style="text-align: center; padding: 1rem; color: var(--text-muted);">
                                    Henüz veri yok
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

@section('scripts')
<script>
    // Her 30 saniyede bir sayfayı yenile
    setTimeout(() => {
        window.location.reload();
    }, 30000);
</script>
@endsection
