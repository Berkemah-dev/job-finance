@if(isset($charts))
<section class="dashboard-hero-charts" aria-label="Grafik Analitik Dashboard">
    <div class="chart-hero-top">
        <div class="chart-hero-title-area">
            <span class="chart-live-badge">
                <span class="pulse-indicator"></span>
                {{ $charts['tag'] ?? 'LIVE ANALYTICS' }}
            </span>
            <h2 class="chart-hero-title">{{ $charts['title'] ?? 'Grafik Performa' }}</h2>
            <p class="chart-hero-subtitle">{{ $charts['subtitle'] ?? 'Ringkasan visual real-time' }}</p>
        </div>
        <div class="chart-hero-controls">
            @if(isset($charts['badge']))
            <span class="chart-status-pill">
                <x-icon name="chart"/>
                <span>{{ $charts['badge'] }}</span>
            </span>
            @endif
            <div class="chart-period-badge">
                <x-icon name="calendar"/>
                <span>Periode Berjalan</span>
            </div>
        </div>
    </div>

    <div class="chart-hero-grid">
        <!-- Main Line/Bar Visual Chart -->
        <div class="chart-card-main">
            <div class="chart-card-inner">
                <div class="chart-canvas-wrap">
                    <canvas id="dashboardMainChart" data-chart='@json($charts["main"])'></canvas>
                </div>
            </div>
        </div>

        <!-- Secondary Donut & KPI Stats -->
        <div class="chart-card-side">
            <div class="donut-chart-box">
                <div class="donut-header">
                    <h4>{{ $charts['donut']['title'] ?? 'Distribusi Data' }}</h4>
                    <p>{{ $charts['donut']['subtitle'] ?? 'Proporsi kategori' }}</p>
                </div>
                <div class="donut-canvas-wrap">
                    <canvas id="dashboardDonutChart" data-chart='@json($charts["donut"])'></canvas>
                </div>
            </div>

            @if(isset($charts['stats']) && count($charts['stats']) > 0)
            <div class="chart-kpi-strip">
                @foreach($charts['stats'] as $stat)
                <div class="chart-kpi-item">
                    <span class="kpi-label">{{ $stat['label'] }}</span>
                    <strong class="kpi-val">{{ $stat['val'] }}</strong>
                    <small class="kpi-sub">{{ $stat['sub'] }}</small>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
@endif
