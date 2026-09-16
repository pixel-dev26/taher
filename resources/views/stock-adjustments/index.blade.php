@extends('layouts.app')
@section('title', 'Corrections')
@section('breadcrumb')
    <li class="breadcrumb-item active">Corrections</li>
@endsection
@section('content')
<x-page-header title="Corrections" subtitle="Adjust stock for damage, counting errors, or initial loading">
    <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i> New Correction
    </a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3" data-autosubmit-filters>
            <div class="col-sm-3">
                <label class="form-label small text-muted mb-1">Godown</label>
                <select name="godown_id" class="form-select">
                    <option value="">All Godowns</option>
                    @foreach($godowns as $g)
                    <option value="{{ $g->id }}" {{ request('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small text-muted mb-1">Reason</label>
                <select name="reason" class="form-select">
                    <option value="">All Reasons</option>
                    @foreach(['initial_load' => 'Initial Load', 'grn_correction' => 'Receipt Correction', 'physical_count' => 'Physical Count', 'damage_loss' => 'Damage / Loss', 'other' => 'Other'] as $val => $label)
                    <option value="{{ $val }}" {{ request('reason') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
            </div>
            @if(request('godown_id') || request('reason'))
            <div class="col-sm-2 d-flex align-items-end">
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>

        <div class="table-responsive d-desktop-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Correction #</th>
                        <th>Godown</th>
                        <th>Reason</th>
                        <th>Products</th>
                        <th>Done By</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    @php
                    $reasonLabels = [
                        'initial_load' => ['label' => 'Initial Load', 'bg' => '#E4F0FC', 'color' => '#1F5F96'],
                        'grn_correction' => ['label' => 'Receipt Correction', 'bg' => '#FBF1DD', 'color' => '#8A6318'],
                        'physical_count' => ['label' => 'Physical Count', 'bg' => '#FBE8DE', 'color' => '#B2502F'],
                        'damage_loss' => ['label' => 'Damage / Loss', 'bg' => '#FCE6E5', 'color' => '#A93330'],
                        'other' => ['label' => 'Other', 'bg' => '#F0F1F3', 'color' => '#5B6470'],
                    ];
                    $r = $reasonLabels[$adj->reason] ?? $reasonLabels['other'];
                    @endphp
                    <tr>
                        <td><a href="{{ route('stock-adjustments.show', $adj) }}" class="fw-semibold">{{ $adj->adjustment_number }}</a></td>
                        <td>{{ $adj->godown->name }}</td>
                        <td><span class="badge" style="background:{{ $r['bg'] }};color:{{ $r['color'] }};border:1px solid {{ $r['color'] }}20;">{{ $r['label'] }}</span></td>
                        <td><span class="badge bg-light text-dark border">{{ $adj->items->count() }} items</span></td>
                        <td class="small text-muted">{{ $adj->creator->name }}</td>
                        <td class="small">{{ $adj->created_at->format('d M Y') }}</td>
                        <td><a href="{{ route('stock-adjustments.show', $adj) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state icon="bi-pencil-square" text="No stock corrections yet">Stock corrections will appear here when adjustments are made</x-empty-state>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards">
            @forelse($adjustments as $adj)
            @php
            $reasonLabels = [
                'initial_load' => ['label' => 'Initial Load', 'bg' => '#E3F4F8', 'color' => '#0A5566'],
                'grn_correction' => ['label' => 'Receipt Correction', 'bg' => '#FDF4E5', 'color' => '#8A4308'],
                'physical_count' => ['label' => 'Physical Count', 'bg' => '#FBE8DE', 'color' => '#B2502F'],
                'damage_loss' => ['label' => 'Damage / Loss', 'bg' => '#FDECEC', 'color' => '#A81E1E'],
                'other' => ['label' => 'Other', 'bg' => '#F0F1F3', 'color' => '#5B6470'],
            ];
            $r = $reasonLabels[$adj->reason] ?? $reasonLabels['other'];
            @endphp
            <a href="{{ route('stock-adjustments.show', $adj) }}" class="card-list-item d-block text-decoration-none text-reset">
                <div class="cl-row mb-2">
                    <strong>{{ $adj->adjustment_number }}</strong>
                    <span class="badge" style="background:{{ $r['bg'] }};color:{{ $r['color'] }};">{{ $r['label'] }}</span>
                </div>
                <div class="cl-row"><span class="cl-label">Godown</span><span class="cl-value">{{ $adj->godown->name }}</span></div>
                <div class="cl-row"><span class="cl-label">Products</span><span class="cl-value">{{ $adj->items->count() }}</span></div>
                <div class="cl-row"><span class="cl-label">Done by</span><span class="cl-value">{{ $adj->creator->name }}</span></div>
                <div class="cl-row"><span class="cl-label">Date</span><span class="cl-value">{{ $adj->created_at->format('d M Y') }}</span></div>
            </a>
            @empty
            <x-empty-state icon="bi-pencil-square" text="No stock corrections yet">Stock corrections will appear here when adjustments are made</x-empty-state>
            @endforelse
        </div>
        {{ $adjustments->links() }}
    </div>
</div>
@endsection
