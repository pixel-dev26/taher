@extends('layouts.app')
@section('title', 'SKU Detail')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('skus.index') }}">Products</a></li>
    <li class="breadcrumb-item active">{{ $sku->code }}</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ $sku->code }} — {{ $sku->name }}</h5></div>
            <div class="card-body">
                <table class="table table-stack table-sm">
                    <tr><th width="40%">Code</th><td><code>{{ $sku->code }}</code></td></tr>
                    <tr><th>Name</th><td>{{ $sku->name }}</td></tr>
                    <tr><th>Category</th><td>{{ $sku->category }}</td></tr>
                    <tr><th>Unit of Measure</th><td>{{ $sku->unit_of_measure }}</td></tr>
                    <tr><th>Low Stock Threshold</th><td>{{ $sku->low_stock_threshold }}</td></tr>
                    <tr><th>HSN Code</th><td>{{ $sku->hsn_code ?? '-' }}</td></tr>
                    <tr><th>Status</th><td><span class="badge {{ $sku->is_active ? 'bg-success' : 'bg-danger' }}">{{ $sku->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                    @if($sku->variant_attributes)
                    <tr><th>Variants</th><td>
                        @foreach($sku->variant_attributes as $key => $val)
                            <span class="badge bg-light text-dark">{{ $key }}: {{ $val }}</span>
                        @endforeach
                    </td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-building"></i> Stock by Godown</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-stack table-bordered mb-0">
                        <thead>
                            <tr><th>Godown</th><th class="text-end">On-hand</th><th class="text-end">Reserved</th><th class="text-end">Available</th></tr>
                        </thead>
                        <tbody>
                            @php $totalOnHand = 0; $totalReserved = 0; @endphp
                            @foreach($stockRecords as $record)
                            @php $totalOnHand += $record->on_hand; $totalReserved += $record->reserved; @endphp
                            <tr>
                                <td>{{ $record->godown->code }} - {{ $record->godown->name }}</td>
                                <td class="text-end">{{ number_format($record->on_hand, 0) }}</td>
                                <td class="text-end">{{ number_format($record->reserved, 0) }}</td>
                                <td class="text-end fw-bold">{{ number_format($record->available, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td>Total</td>
                                <td class="text-end">{{ number_format($totalOnHand, 0) }}</td>
                                <td class="text-end">{{ number_format($totalReserved, 0) }}</td>
                                <td class="text-end">{{ number_format($totalOnHand - $totalReserved, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
