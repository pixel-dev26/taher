@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Home</li>
@endsection
@section('content')

@php
    $totalHealth = array_sum($stockHealth) ?: 1;
    $healthPct = [
        'good' => round($stockHealth['good'] / $totalHealth * 100),
        'low' => round($stockHealth['low'] / $totalHealth * 100),
        'critical' => round($stockHealth['critical'] / $totalHealth * 100),
    ];
    $c1 = $healthPct['good'];
    $c2 = $c1 + $healthPct['low'];
    $c3 = $c2 + $healthPct['critical'];

    $chartW = 560; $chartH = 160; $padL = 6; $padR = 6; $padT = 10; $padB = 6;
    $maxVal = max(1, collect($stockTrend)->flatMap(fn($d) => [$d['in'], $d['out']])->max());
    $stepX = ($chartW - $padL - $padR) / max(1, count($stockTrend) - 1);
    $scaleY = fn($v) => $chartH - $padB - ($v / $maxVal) * ($chartH - $padT - $padB);

    $pointsIn = collect($stockTrend)->values()->map(fn($d, $i) => [$padL + $i * $stepX, $scaleY($d['in'])]);
    $pointsOut = collect($stockTrend)->values()->map(fn($d, $i) => [$padL + $i * $stepX, $scaleY($d['out'])]);
    $polyIn = $pointsIn->map(fn($p) => round($p[0], 1) . ',' . round($p[1], 1))->implode(' ');
    $polyOut = $pointsOut->map(fn($p) => round($p[0], 1) . ',' . round($p[1], 1))->implode(' ');
@endphp

<x-page-header
    :title="'Welcome back, ' . auth()->user()->name"
    subtitle="Here's what's happening across your godowns" />

{{-- Quick Actions --}}
<div class="row g-3 mb-1">
    <div class="col-6 col-md-3">
        <a href="{{ route('grn.create') }}" class="action-card action-card-green">
            <div class="action-icon"><i class="bi bi-box-arrow-in-down"></i></div>
            <div class="action-label">Receive Stock</div>
            <div class="action-hint">Record incoming goods</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('dispatch-sheets.create') }}" class="action-card action-card-blue">
            <div class="action-icon"><i class="bi bi-plus-circle"></i></div>
            <div class="action-label">New Dispatch</div>
            <div class="action-hint">Send stock out</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('fulfillment.index') }}" class="action-card action-card-orange" style="position: relative;">
            <div class="action-icon"><i class="bi bi-truck"></i></div>
            <div class="action-label">Send Out Stock</div>
            <div class="action-hint">Process pending</div>
            @if($pendingDispatchCount > 0)
                <span class="badge badge-today" style="position: absolute; top: 10px; right: 10px;">{{ $pendingDispatchCount }}</span>
            @endif
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('stock-transfers.create') }}" class="action-card action-card-purple">
            <div class="action-icon"><i class="bi bi-arrow-left-right"></i></div>
            <div class="action-label">Transfer Stock</div>
            <div class="action-hint">Between godowns</div>
        </a>
    </div>
</div>

{{-- What's on the shelves right now, valued at each product's average price
     (see StockService::averagePrices — same figure as the Stock screen). --}}
<div class="row g-3 mt-1">
    <div class="col-6 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--brand-soft); color:var(--brand-dark);"><i class="bi bi-currency-rupee"></i></div>
                <div class="stat-number stat-number-money" title="{{ \App\Support\Money::inr($totalStockValue) }} exact">{{ \App\Support\Money::inr($totalStockValue, decimals: 0) }}</div>
                <div class="stat-label">
                    Stock Value
                    @if($unpricedStockSkuCount > 0)
                        <i class="bi bi-info-circle" title="{{ $unpricedStockSkuCount }} product(s) with stock have no price yet, so their value isn't included — it's an estimate"></i>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--info-soft); color:var(--info-dark);"><i class="bi bi-boxes"></i></div>
                <div class="stat-number">{{ number_format($totalStockQty, 0) }}</div>
                <div class="stat-label">Items in Stock</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--brand-soft); color:var(--brand-dark);"><i class="bi bi-box-arrow-in-down"></i></div>
                <div class="stat-number">{{ $todayGrns }}</div>
                <div class="stat-label">Received Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--warning-soft); color:var(--warning-dark);"><i class="bi bi-truck"></i></div>
                <div class="stat-number">{{ $todayDispatches }}</div>
                <div class="stat-label">Sent Out Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--info-soft); color:var(--info-dark);"><i class="bi bi-box-seam-fill"></i></div>
                <div class="stat-number">{{ $totalSkus }}</div>
                <div class="stat-label">Active Products</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-icon" style="background:var(--coral-soft); color:var(--coral-dark);"><i class="bi bi-building-fill"></i></div>
                <div class="stat-number">{{ $totalGodowns }}</div>
                <div class="stat-label">Godowns</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    {{-- Pending Dispatches --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Waiting to be Sent Out</h6>
                @if($pendingDispatchCount > 0)<span class="badge badge-pending">{{ $pendingDispatchCount }}</span>@endif
            </div>
            <div class="card-body p-0">
                @forelse($pendingDispatches as $sheet)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 gap-2" style="border-bottom:1px solid var(--border-soft);">
                        <div style="min-width:0;">
                            <div style="font-size:0.82rem; font-weight:600;">{{ $sheet->ds_number }}</div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">{{ $sheet->godown->code ?? '-' }} &middot; {{ $sheet->customer_name ?? 'No customer' }}</div>
                        </div>
                        <a href="{{ route('fulfillment.show', $sheet) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">Process</a>
                    </div>
                @empty
                    <x-empty-state icon="bi-check-circle" text="All caught up!">Nothing waiting to be sent out.</x-empty-state>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Incoming Transfers --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Transfers Awaiting Acceptance</h6>
                @if($incomingTransfers->count() > 0)<span class="badge badge-pending">{{ $incomingTransfers->count() }}</span>@endif
            </div>
            <div class="card-body p-0">
                @forelse($incomingTransfers as $t)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 gap-2" style="border-bottom:1px solid var(--border-soft);">
                        <div style="min-width:0;">
                            <div style="font-size:0.82rem; font-weight:600;">{{ $t->transfer_number }}</div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">{{ $t->sourceGodown->code ?? '-' }} &rarr; {{ $t->destGodown->code ?? '-' }}</div>
                        </div>
                        <a href="{{ route('stock-transfers.show', $t) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">View</a>
                    </div>
                @empty
                    <x-empty-state icon="bi-arrow-left-right" text="No incoming transfers">Transfers between godowns will show up here.</x-empty-state>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($lowStockSkus->isNotEmpty())
<h6 class="fw-bold mt-4 mb-3"><i class="bi bi-exclamation-triangle-fill me-1" style="color:var(--warning-dark);"></i> Low Stock Alerts</h6>
<div class="row g-3">
    @foreach($lowStockSkus as $entry)
        @php $isCritical = $entry['available'] <= 0; @endphp
        <div class="col-sm-6 col-md-4">
            <div class="card" style="background: {{ $isCritical ? '#FCE6E5' : '#FBF1DD' }};">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="min-width:0;">
                            <div class="fw-bold" style="font-size:0.9rem;">{{ \Illuminate\Support\Str::limit($entry['sku']->name, 28) }}</div>
                            <div style="font-size:0.82rem; color:var(--text-muted);">{{ $entry['sku']->code }}</div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div style="font-size:1.4rem; font-weight:800; color: {{ $isCritical ? 'var(--critical-dark)' : 'var(--warning-dark)' }};">{{ number_format($entry['available'], 0) }}</div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">{{ $entry['sku']->unit_of_measure }} left</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="row g-3 mt-1">
    {{-- Stock Trend chart --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">Stock Movement</h6>
                        <small class="text-muted">Last 7 days</small>
                    </div>
                    <div class="d-flex gap-3">
                        <span style="font-size:0.82rem; color:var(--text-muted);"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--brand);margin-right:4px;"></span>Stock In</span>
                        <span style="font-size:0.82rem; color:var(--text-muted);"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--coral);margin-right:4px;"></span>Stock Out</span>
                    </div>
                </div>
                <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" style="width:100%; height:170px;" preserveAspectRatio="none">
                    @for ($g = 0; $g < 4; $g++)
                        @php $gy = $padT + $g * ($chartH - $padT - $padB) / 3; @endphp
                        <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $chartW - $padR }}" y2="{{ $gy }}" stroke="#EDF2EF" stroke-width="1" />
                    @endfor
                    <polyline points="{{ $polyOut }}" fill="none" stroke="#D9704F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    <polyline points="{{ $polyIn }}" fill="none" stroke="#15803D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($pointsIn as $i => $p)
                        <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3.5" fill="#15803D"><title>{{ $stockTrend[$i]['label'] }}: {{ (int) $stockTrend[$i]['in'] }} in</title></circle>
                    @endforeach
                    @foreach ($pointsOut as $i => $p)
                        <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3.5" fill="#D9704F"><title>{{ $stockTrend[$i]['label'] }}: {{ (int) $stockTrend[$i]['out'] }} out</title></circle>
                    @endforeach
                </svg>
                <div class="d-flex justify-content-between px-1" style="font-size:0.72rem; color:var(--text-muted);">
                    @foreach ($stockTrend as $d)
                        <span>{{ $d['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    {{-- Stock Health donut --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center text-center">
                <h6 class="fw-bold align-self-start mb-3">Stock Health</h6>
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--brand) 0% {{ $c1 }}%, var(--warning) {{ $c1 }}% {{ $c2 }}%, var(--critical) {{ $c2 }}% {{ $c3 }}%, #DDE3E0 {{ $c3 }}% 100%); display:flex;align-items:center;justify-content:center;">
                    <div style="width:80px;height:80px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <div style="font-size:1.3rem;font-weight:700;color:var(--text-dark);">{{ $healthPct['good'] }}%</div>
                        <div style="font-size:0.72rem;color:var(--text-muted);">Healthy</div>
                    </div>
                </div>
                <div class="w-100 mt-3 text-start" style="font-size:0.82rem;">
                    <div class="d-flex justify-content-between mb-1">
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--brand);margin-right:6px;"></span>Good</span>
                        <span class="fw-semibold">{{ $stockHealth['good'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--warning);margin-right:6px;"></span>Low</span>
                        <span class="fw-semibold">{{ $stockHealth['low'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--critical);margin-right:6px;"></span>Critical</span>
                        <span class="fw-semibold">{{ $stockHealth['critical'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#DDE3E0;margin-right:6px;"></span>Zero Stock</span>
                        <span class="fw-semibold">{{ $stockHealth['zero'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<h6 class="fw-bold mt-4 mb-3"><i class="bi bi-building-fill me-1" style="color:var(--brand);"></i> Stock Overview by Godown</h6>
<div class="row g-3">
    @foreach($stockSummary as $summary)
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="fw-bold mb-2" style="font-size:0.9rem;">{{ $summary['godown']->code }} — {{ $summary['godown']->name }}</div>
                <div class="row text-center g-2">
                    <div class="col-4 px-0">
                        <div class="fw-bold" style="color:var(--text-dark); font-size:1.1rem; white-space:nowrap;">{{ number_format($summary['on_hand'], 0) }}</div>
                        <small class="text-muted fw-semibold d-block" style="font-size:0.72rem; white-space:nowrap;">Total</small>
                    </div>
                    <div class="col-4 px-0">
                        <div class="fw-bold" style="color:var(--warning-dark); font-size:1.1rem; white-space:nowrap;">{{ number_format($summary['reserved'], 0) }}</div>
                        <small class="text-muted fw-semibold d-block" style="font-size:0.72rem; white-space:nowrap;">Reserved</small>
                    </div>
                    <div class="col-4 px-0">
                        <div class="fw-bold" style="color:var(--brand-dark); font-size:1.1rem; white-space:nowrap;">{{ number_format($summary['available'], 0) }}</div>
                        <small class="text-muted fw-semibold d-block" style="font-size:0.72rem; white-space:nowrap;">Available</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                @forelse($recentActivity as $entry)
                    @php
                        $badgeMap = [
                            'stock_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)', 'label' => 'Received'],
                            'reserved' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)', 'label' => 'Reserved'],
                            'reserve_released' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)', 'label' => 'Released'],
                            'dispatch_out' => ['bg' => 'var(--coral-soft)', 'color' => 'var(--coral-dark)', 'label' => 'Dispatched'],
                            'transfer_out' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)', 'label' => 'Sent Out'],
                            'transfer_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)', 'label' => 'Transfer In'],
                            'transfer_rejected' => ['bg' => '#F0F1F3', 'color' => '#5B6470', 'label' => 'Rejected'],
                            'adjustment' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)', 'label' => 'Corrected'],
                        ];
                        $b = $badgeMap[$entry->movement_type] ?? ['bg' => '#F0F1F3', 'color' => '#5B6470', 'label' => str_replace('_', ' ', $entry->movement_type)];
                    @endphp
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 gap-2" style="border-bottom:1px solid var(--border-soft);">
                        <div class="d-flex align-items-center gap-2" style="min-width:0; flex:1;">
                            <span class="badge flex-shrink-0" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ $b['label'] }}</span>
                            <div style="min-width:0; overflow:hidden;">
                                <div style="font-size:0.82rem; font-weight:600;">{{ $entry->sku->code ?? '-' }}</div>
                                <div style="font-size:0.72rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $entry->sku->name ?? '' }}</div>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold" style="font-size:0.82rem; color:{{ $entry->quantity > 0 ? 'var(--brand-dark)' : 'var(--coral-dark)' }};">
                                {{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0) }}
                            </div>
                            <div style="font-size:0.72rem; color:var(--text-muted); white-space:nowrap;">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="bi-clock-history" text="No recent activity">Stock movements will appear here as they happen</x-empty-state>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Top Moving Products</h6>
            </div>
            <div class="card-body p-0">
                @forelse($topMovers as $mover)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 gap-2" style="border-bottom:1px solid var(--border-soft);">
                        <div class="d-flex align-items-center gap-2" style="min-width:0; flex:1;">
                            <div style="width:34px;height:34px;border-radius:10px;background:var(--brand-soft);display:flex;align-items:center;justify-content:center;color:var(--brand-dark);flex-shrink:0;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div style="min-width:0; overflow:hidden;">
                                <div style="font-size:0.82rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $mover->sku->name ?? 'Unknown' }}</div>
                                <div style="font-size:0.72rem; color:var(--text-muted);">{{ $mover->sku->code ?? '-' }}</div>
                            </div>
                        </div>
                        <span class="fw-bold flex-shrink-0" style="font-size:0.82rem;">{{ number_format($mover->total_movement, 0) }}</span>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-graph-up"></i>
                        <div class="empty-text">No movement yet</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
