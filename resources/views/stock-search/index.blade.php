@extends('layouts.app')
@section('title', 'Stock')
@section('breadcrumb')
    <li class="breadcrumb-item active">Stock</li>
@endsection
@section('content')
<x-page-header
    title="Stock"
    icon="bi-box-seam-fill"
    :subtitle="$isToday ? 'What\'s available right now across all godowns' : 'Position as at ' . \Carbon\Carbon::parse($date)->format('d M Y')" />

{{-- Search, filter, and the as-on-date control that used to be its own screen --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="search">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="{{ request('search') }}" placeholder="Product name or code...">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="category">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="godown_id">Godown</label>
                <select class="form-select" id="godown_id" name="godown_id">
                    <option value="">All</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ (string) $godownId === (string) $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="date">As at</label>
                <input type="date" class="form-control" id="date" name="date"
                       value="{{ $date }}" max="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show</button>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" id="hide_zero" name="hide_zero" {{ $hideZero ? 'checked' : '' }}>
                    <label class="form-check-label" for="hide_zero">Hide products with no stock</label>
                </div>
                @if(request()->hasAny(['search', 'category', 'godown_id', 'date', 'hide_zero']))
                    <a href="{{ route('stock-search') }}" class="btn btn-link p-0">Clear</a>
                @endif
                <a href="{{ route('reports.stock-as-on-date.export.excel', ['date' => $date, 'godown_id' => $godownId]) }}"
                   class="btn btn-outline-secondary ms-auto d-none d-md-inline-flex">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                </a>
            </div>
        </form>
    </div>
</div>

@unless($isToday)
    <div class="alert alert-info border-0 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-clock-history"></i>
        <div>Showing the closing position for <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>. Reservations only apply to today, so they read as zero here.</div>
    </div>
@endunless

<div class="card">
    <div class="card-body p-0">
        @if($skus->count() > 0)
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>SKU Code</th>
                        <th>Product Name</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">On-hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end" title="Average purchase price per unit, weighted by the quantity in each stock receipt">Avg. Price</th>
                        <th>Category</th>
                        <th>Unit</th>
                        @foreach($columns as $g)
                            <th class="text-end">{{ $g->code }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($skus as $sku)
                        @php
                            $perGodown = $figures[$sku->id] ?? [];
                            $onHand = $reserved = $avail = 0;
                            foreach ($columns as $g) {
                                $f = $perGodown[$g->id] ?? null;
                                $onHand  += $f['on_hand']   ?? 0;
                                $reserved += $f['reserved'] ?? 0;
                                $avail   += $f['available'] ?? 0;
                            }
                            $threshold = $sku->low_stock_threshold;
                            $tone = $avail <= 0 ? 'stock-zero'
                                : ($avail <= $threshold ? 'stock-critical'
                                : ($avail <= $threshold * 2 ? 'stock-low' : 'stock-ok'));
                            $rowClass = $avail <= 0 ? 'row-stock-zero' : ($avail <= $threshold ? 'row-stock-critical' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td><code>{{ $sku->code }}</code></td>
                            <td>{{ $sku->name }}</td>
                            <td class="text-end fw-bold {{ $tone }}">{{ number_format($avail, 0) }}</td>
                            <td class="text-end">{{ number_format($onHand, 0) }}</td>
                            <td class="text-end">{{ number_format($reserved, 0) }}</td>
                            <td class="text-end {{ isset($prices[$sku->id]) ? '' : 'text-muted' }}">{{ \App\Support\Money::inr($prices[$sku->id]['average'] ?? null) }}</td>
                            <td>{{ $sku->category }}</td>
                            <td>{{ $sku->unit_of_measure }}</td>
                            @foreach($columns as $g)
                                @php $cell = $perGodown[$g->id]['available'] ?? 0; @endphp
                                <td class="text-end {{ $cell <= 0 ? 'stock-zero' : ($cell <= $threshold ? 'stock-low' : '') }}">{{ number_format($cell, 0) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile: lead with the number you came for. Keeping the per-godown
             split in a disclosure means the layout no longer widens as godowns
             are added. --}}
        <div class="d-mobile-cards p-3">
            @foreach($skus as $sku)
                @php
                    $perGodown = $figures[$sku->id] ?? [];
                    $onHand = $reserved = $avail = 0;
                    foreach ($columns as $g) {
                        $f = $perGodown[$g->id] ?? null;
                        $onHand  += $f['on_hand']   ?? 0;
                        $reserved += $f['reserved'] ?? 0;
                        $avail   += $f['available'] ?? 0;
                    }
                    $threshold = $sku->low_stock_threshold;
                    $tone = $avail <= 0 ? 'stock-zero'
                        : ($avail <= $threshold ? 'stock-critical'
                        : ($avail <= $threshold * 2 ? 'stock-low' : 'stock-ok'));
                @endphp
                <div class="card-list-item">
                    <div class="cl-row align-items-start">
                        <div class="pe-2">
                            <code>{{ $sku->code }}</code>
                            <div class="fw-semibold">{{ $sku->name }}</div>
                            <div class="cl-label mt-1">{{ $sku->category }}</div>
                            @isset($prices[$sku->id])
                                <div class="cl-label mt-1">Avg. price <span class="cl-value">{{ \App\Support\Money::inr($prices[$sku->id]['average']) }}</span></div>
                            @endisset
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="cl-hero {{ $tone }}">{{ number_format($avail, 0) }}</div>
                            <div class="cl-label">{{ $sku->unit_of_measure }} free</div>
                        </div>
                    </div>
                    <details class="mt-2">
                        <summary class="cl-label" style="cursor:pointer;">Breakdown</summary>
                        <div class="cl-row mt-2"><span class="cl-label">On-hand</span><span class="cl-value">{{ number_format($onHand, 0) }}</span></div>
                        <div class="cl-row"><span class="cl-label">Reserved</span><span class="cl-value">{{ number_format($reserved, 0) }}</span></div>
                        @foreach($columns as $g)
                            @php $cell = $perGodown[$g->id]['available'] ?? 0; @endphp
                            <div class="cl-row">
                                <span class="cl-label">{{ $g->code }} · {{ $g->name }}</span>
                                <span class="cl-value {{ $cell <= 0 ? 'stock-zero' : ($cell <= $threshold ? 'stock-low' : '') }}">{{ number_format($cell, 0) }}</span>
                            </div>
                        @endforeach
                    </details>
                </div>
            @endforeach
        </div>
        @else
        <x-empty-state icon="bi-search" text="No products found">Try a different search term, or clear the filters.</x-empty-state>
        @endif
    </div>
</div>

@if($skus->count() > 0)
    <div class="mt-3">{{ $skus->links() }}</div>
@endif
@endsection
