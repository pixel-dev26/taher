@extends('layouts.app')
@section('title', 'Confirm Dispatch')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('fulfillment.index') }}">Send Out Stock</a></li>
    <li class="breadcrumb-item active">{{ $dispatchSheet->ds_number }}</li>
@endsection
@section('content')

{{-- Page Header --}}
<div class="page-header">
    <h4><i class="bi bi-truck text-success"></i> Confirm Dispatch &mdash; {{ $dispatchSheet->ds_number }}</h4>
    <p class="page-subtitle">Review the items below, then confirm when everything is loaded</p>
</div>

<div class="card mb-4">
    <div class="card-body">

        {{-- Info Strip: Dispatch Details --}}
        <div class="info-strip">
            <div class="info-item">
                <div class="info-label">Created By</div>
                <div class="info-value">{{ $dispatchSheet->creator->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer</div>
                <div class="info-value">{{ $dispatchSheet->customer_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Delivery Date</div>
                <div class="info-value">
                    {{ $dispatchSheet->delivery_date?->format('d M Y') ?? '-' }}
                    @if($dispatchSheet->delivery_date?->isToday())
                        <span class="badge badge-today ms-1">Today</span>
                    @elseif($dispatchSheet->delivery_date?->isPast())
                        <span class="badge badge-overdue ms-1">Overdue</span>
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Vehicle</div>
                <div class="info-value">{{ $dispatchSheet->vehicle_no ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Driver</div>
                <div class="info-value">{{ $dispatchSheet->driver_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Driver Phone</div>
                <div class="info-value">{{ $dispatchSheet->driver_phone ?? '-' }}</div>
            </div>
        </div>

        @if($dispatchSheet->notes)
            <div class="alert alert-light mt-2" style="border-radius:8px;">
                <i class="bi bi-sticky me-1"></i> <strong>Notes:</strong> {{ $dispatchSheet->notes }}
            </div>
        @endif

        <hr class="section-divider">

        {{-- Items Table --}}
        <h6 class="mb-3"><i class="bi bi-box-seam me-1"></i> Items to Dispatch</h6>
        <div class="table-responsive">
            <table class="table table-stack table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th class="text-end">Quantity</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dispatchSheet->items as $i => $item)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $item->sku->code }}</code></td>
                        <td>{{ $item->sku->name }}</td>
                        <td class="text-end fw-bold fs-6">{{ number_format($item->quantity, 0) }}</td>
                        <td>{{ $item->sku->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3">Total</td>
                        <td class="text-end fs-6">{{ number_format($dispatchSheet->items->sum('quantity'), 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <hr class="section-divider">

        {{-- Action Buttons --}}
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmDispatchModal">
                <i class="bi bi-check-circle-fill me-1"></i> Confirm Dispatch
            </button>
            <a href="{{ route('fulfillment.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Go Back
            </a>
        </div>

    </div>
</div>

{{-- Confirmation Modal (replaces JS confirm) --}}
<div class="modal fade" id="confirmDispatchModal" tabindex="-1" aria-labelledby="confirmDispatchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-body confirm-modal-body">
                <div class="confirm-icon text-success">
                    <i class="bi bi-truck"></i>
                </div>
                <h5>Ready to dispatch?</h5>
                <p>This will remove items from your godown stock.<br>Please make sure all items are loaded.</p>

                <div class="d-flex justify-content-center gap-3 mt-4">
                    <form method="POST" action="{{ route('fulfillment.confirm', $dispatchSheet) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill me-1"></i> Yes, Confirm Dispatch
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
