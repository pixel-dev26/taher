@extends('layouts.app')
@section('title', 'Stock Receipt Detail')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">Receive</a></li>
    <li class="breadcrumb-item active">{{ $grn->grn_number }}</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="font-weight:700;">
            <i class="bi bi-inbox me-1 text-success"></i> Stock Receipt: {{ $grn->grn_number }}
        </h5>
        <span class="badge badge-completed"><i class="bi bi-check-circle me-1"></i>Received</span>
    </div>
    <div class="card-body">
        <div class="info-strip mb-3">
            <div class="info-item">
                <div class="info-label">Godown</div>
                <div class="info-value">{{ $grn->godown->code }} - {{ $grn->godown->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Date Received</div>
                <div class="info-value">{{ $grn->receipt_date->format('d M Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Recorded By</div>
                <div class="info-value">{{ $grn->creator->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Challan / Invoice</div>
                <div class="info-value">{{ $grn->challan_no ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Supplier</div>
                <div class="info-value">{{ $grn->supplier_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Recorded On</div>
                <div class="info-value">{{ $grn->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        @if($grn->notes)
        <div class="alert alert-light border mb-3">
            <strong>Notes:</strong> {{ $grn->notes }}
        </div>
        @endif

        <hr class="section-divider">
        <h6 class="mb-3">Products Received</h6>
        <div class="table-responsive">
            <table class="table table-stack table-bordered mb-0">
                <thead>
                    <tr><th>#</th><th>Product Code</th><th>Product Name</th><th class="text-end">Quantity</th><th>Unit</th></tr>
                </thead>
                <tbody>
                    @foreach($grn->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code>{{ $item->sku->code }}</code></td>
                        <td>{{ $item->sku->name }}</td>
                        <td class="text-end fw-bold text-success">+{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 3) }}</td>
                        <td>{{ $item->sku->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#F0FFF5;">
                        <td colspan="3" class="fw-bold">Total Received</td>
                        <td class="text-end fw-bold text-success">+{{ number_format($grn->items->sum('quantity'), 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
