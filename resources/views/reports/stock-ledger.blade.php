@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('breadcrumb')
    <li class="breadcrumb-item">Reports</li>
    <li class="breadcrumb-item active">Stock Ledger</li>
@endsection
@section('content')
<div class="page-header"><h4><i class="bi bi-book"></i> Stock Movement Ledger</h4></div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end" data-autosubmit-filters>
            <div class="col-sm-2">
                <label class="form-label">From *</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" required>
            </div>
            <div class="col-sm-2">
                <label class="form-label">To *</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" required>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Godown</label>
                <select name="godown_id" class="form-select">
                    <option value="">All</option>
                    @foreach($godowns as $g)
                    <option value="{{ $g->id }}" {{ request('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Type</label>
                <select name="movement_type" class="form-select">
                    <option value="">All</option>
                    @foreach(['stock_in','reserved','reserve_released','dispatch_out','transfer_out','transfer_in','transfer_rejected','adjustment'] as $type)
                    <option value="{{ $type }}" {{ request('movement_type') === $type ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            @if($entries instanceof \Illuminate\Pagination\LengthAwarePaginator && $entries->count() > 0)
            <div class="col-sm-2 text-end">
                <a href="{{ route('reports.stock-ledger.export.excel', request()->all()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            </div>
            @endif
        </form>
    </div>
</div>

@php
    $ledgerBadge = fn($type) => match($type) {
        'stock_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)'],
        'reserved' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)'],
        'reserve_released' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)'],
        'dispatch_out' => ['bg' => 'var(--coral-soft)', 'color' => 'var(--coral-dark)'],
        'transfer_out' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)'],
        'transfer_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)'],
        'transfer_rejected' => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
        'adjustment' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)'],
        default => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
    };
@endphp
@if($entries instanceof \Illuminate\Pagination\LengthAwarePaginator && $entries->count() > 0)
<div class="card d-desktop-table">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead>
                    <tr><th>Date/Time</th><th>Type</th><th>SKU</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Balance</th><th>Godown</th><th>Reference</th><th>By</th></tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                    @php $b = $ledgerBadge($entry->movement_type); @endphp
                    <tr>
                        <td class="small">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ str_replace('_',' ',$entry->movement_type) }}</span></td>
                        <td><code>{{ $entry->sku->code ?? '-' }}</code></td>
                        <td class="small">{{ $entry->sku->name ?? '-' }}</td>
                        <td class="text-end {{ $entry->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0) }}
                        </td>
                        <td class="text-end">{{ number_format($entry->balance_after, 0) }}</td>
                        <td>{{ $entry->godown->code ?? '-' }}</td>
                        <td class="small">{{ ucfirst($entry->reference_type) }} #{{ $entry->reference_id }}</td>
                        <td class="small">{{ $entry->performer->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Mobile: card list --}}
<div class="d-mobile-cards">
    @foreach($entries as $entry)
    @php $b = $ledgerBadge($entry->movement_type); @endphp
    <div class="card-list-item">
        <div class="cl-row mb-1">
            <span class="badge" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ str_replace('_',' ',$entry->movement_type) }}</span>
            <span class="fw-bold {{ $entry->quantity > 0 ? 'text-success' : 'text-danger' }}" style="font-size:0.9rem;">{{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0) }}</span>
        </div>
        <div class="mb-1"><code>{{ $entry->sku->code ?? '-' }}</code> <span style="font-size:0.82rem;">{{ $entry->sku->name ?? '-' }}</span></div>
        <div class="cl-row"><span class="cl-label">Godown</span><span class="cl-value">{{ $entry->godown->code ?? '-' }}</span></div>
        <div class="cl-row"><span class="cl-label">Balance After</span><span class="cl-value">{{ number_format($entry->balance_after, 0) }}</span></div>
        <div class="cl-row"><span class="cl-label">Reference</span><span class="cl-value">{{ ucfirst($entry->reference_type) }} #{{ $entry->reference_id }}</span></div>
        <div class="cl-row"><span class="cl-label">When / By</span><span class="cl-value">{{ $entry->created_at->format('d/m/Y H:i') }} &middot; {{ $entry->performer->name ?? '-' }}</span></div>
    </div>
    @endforeach
</div>

{{ $entries->links() }}
@elseif(request()->has('date_from'))
<div class="text-center text-muted py-4">No ledger entries found.</div>
@endif
@endsection
