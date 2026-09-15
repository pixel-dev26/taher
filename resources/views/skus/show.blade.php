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
                    <tr>
                        <th>Average Price</th>
                        <td>
                            @if($price)
                                <span class="fw-bold">{{ \App\Support\Money::inr($price['average']) }}</span> / {{ $sku->unit_of_measure }}
                                <div class="small text-muted">
                                    @if($price['quantity'] > 0)
                                        Across {{ rtrim(rtrim(number_format($price['quantity'], 3, '.', ','), '0'), '.') }} {{ $sku->unit_of_measure }} brought in
                                    @else
                                        Product price — no stock brought in yet
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">No price yet — set one on this product, or enter it when stock comes in</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Product Price</th><td>{{ \App\Support\Money::inr($sku->price === null ? null : (float) $sku->price) }}</td></tr>
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

<div class="row">
    <div class="col-12">
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-currency-rupee"></i> Price History</h5></div>
            <div class="card-body p-0">
                @if($purchases->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-stack table-bordered mb-0">
                        <thead>
                            <tr><th>Date</th><th>Stock In</th><th class="text-end">Quantity</th><th class="text-end">Price / Unit</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $line)
                            @php
                                $quantity = (float) $line->quantity;
                                // A line without its own price is valued at the product price.
                                $unitPrice = $line->unit_price ?? $sku->price;
                            @endphp
                            <tr>
                                <td data-label="Date">{{ \Carbon\Carbon::parse($line->moved_at)->format('d M Y') }}</td>
                                <td data-label="Stock In">
                                    @if($line->source === 'grn')
                                        <a href="{{ route('grn.show', $line->document_id) }}">{{ $line->document_number }}</a>
                                        <div class="small text-muted">Receipt · {{ $godownCodes[$line->godown_id] ?? '' }}</div>
                                    @else
                                        <a href="{{ route('stock-adjustments.show', $line->document_id) }}">{{ $line->document_number }}</a>
                                        <div class="small text-muted">Correction · {{ $godownCodes[$line->godown_id] ?? '' }}</div>
                                    @endif
                                </td>
                                <td class="text-end" data-label="Quantity">{{ number_format($quantity, $quantity == intval($quantity) ? 0 : 3) }}</td>
                                <td class="text-end" data-label="Price / Unit">
                                    {{ \App\Support\Money::inr($unitPrice === null ? null : (float) $unitPrice) }}
                                    @if($line->unit_price === null && $unitPrice !== null)
                                        <div class="small text-muted">product price</div>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold" data-label="Amount">{{ \App\Support\Money::inr($unitPrice === null ? null : $quantity * (float) $unitPrice) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($purchaseCount > $purchases->count())
                    <div class="small text-muted px-3 py-2">Showing the latest {{ $purchases->count() }} of {{ $purchaseCount }}. The average uses all of them.</div>
                @endif
                @else
                <x-empty-state icon="bi-currency-rupee" text="No stock brought in yet">Prices are entered when you receive stock or add it through a correction.</x-empty-state>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
