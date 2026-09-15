@extends('layouts.app')
@section('title', 'Correction Detail')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock-adjustments.index') }}">Corrections</a></li>
    <li class="breadcrumb-item active">{{ $stockAdjustment->adjustment_number }}</li>
@endsection
@section('content')
@php
$reasonLabels = [
    'initial_load' => 'Initial Load',
    'grn_correction' => 'Receipt Correction',
    'physical_count' => 'Physical Count',
    'damage_loss' => 'Damage / Loss',
    'other' => 'Other',
];
@endphp
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0" style="font-weight:700;">
            <i class="bi bi-pencil-square me-1"></i> Stock Correction: {{ $stockAdjustment->adjustment_number }}
        </h5>
    </div>
    <div class="card-body">
        <div class="info-strip mb-3">
            <div class="info-item">
                <div class="info-label">Godown</div>
                <div class="info-value">{{ $stockAdjustment->godown->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Reason</div>
                <div class="info-value">{{ $reasonLabels[$stockAdjustment->reason] ?? ucwords(str_replace('_',' ',$stockAdjustment->reason)) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Done By</div>
                <div class="info-value">{{ $stockAdjustment->creator->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Date</div>
                <div class="info-value">{{ $stockAdjustment->created_at->format('d M Y, h:i A') }}</div>
            </div>
            @if($stockAdjustment->reference_doc)
            <div class="info-item">
                <div class="info-label">Reference Document</div>
                <div class="info-value">{{ $stockAdjustment->reference_doc }}</div>
            </div>
            @endif
        </div>

        <div class="alert alert-light border mb-3">
            <strong>Reason Notes:</strong> {{ $stockAdjustment->reason_notes }}
        </div>

        <hr class="section-divider">
        <h6 class="mb-3">Products Adjusted</h6>
        <div class="table-responsive">
            <table class="table table-stack table-bordered mb-0">
                <thead><tr><th>#</th><th>Product Code</th><th>Product Name</th><th class="text-end">Change</th><th>Unit</th></tr></thead>
                <tbody>
                    @foreach($stockAdjustment->items as $i => $item)
                    <tr class="{{ $item->quantity < 0 ? 'row-stock-critical' : '' }}" style="{{ $item->quantity >= 0 ? 'background:#F0FFF5;' : '' }}">
                        <td>{{ $i+1 }}</td>
                        <td><code>{{ $item->sku->code }}</code></td>
                        <td>{{ $item->sku->name }}</td>
                        <td class="text-end fw-bold {{ $item->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $item->quantity >= 0 ? '+' : '' }}{{ number_format($item->quantity, 0) }}
                        </td>
                        <td>{{ $item->sku->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
