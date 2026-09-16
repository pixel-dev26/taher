@extends('layouts.app')
@section('title', 'Edit Dispatch')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dispatch-sheets.index') }}">Dispatch</a></li>
    <li class="breadcrumb-item active">Edit {{ $dispatchSheet->ds_number }}</li>
@endsection
@section('content')
<x-page-header :title="'Edit Dispatch: ' . $dispatchSheet->ds_number" subtitle="Update dispatch details or change products" />

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('dispatch-sheets.update', $dispatchSheet) }}">
            @csrf @method('PUT')

            <x-form-errors />

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Godown</label>
                    <input type="text" class="form-control" value="{{ $dispatchSheet->godown->code }} - {{ $dispatchSheet->godown->name }}" disabled >
                    <div class="form-hint">Godown cannot be changed after creation</div>
                </div>
                <div class="col-md-6">
                    <label for="delivery_date" class="form-label">Delivery Date</label>
                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', $dispatchSheet->delivery_date?->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}">
                    <div class="form-hint">When should this be delivered?</div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Customer Name</label>
                    <input type="text" class="form-control" name="customer_name" value="{{ old('customer_name', $dispatchSheet->customer_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer Phone</label>
                    <input type="text" class="form-control" name="customer_phone" value="{{ old('customer_phone', $dispatchSheet->customer_phone) }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Delivery Address</label>
                    <input type="text" class="form-control" name="delivery_address" value="{{ old('delivery_address', $dispatchSheet->delivery_address) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer GSTIN</label>
                    <input type="text" class="form-control" name="customer_gstin" value="{{ old('customer_gstin', $dispatchSheet->customer_gstin) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Place of Supply</label>
                    <input type="text" class="form-control" name="place_of_supply" value="{{ old('place_of_supply', $dispatchSheet->place_of_supply) }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Vehicle Number</label>
                    <input type="text" class="form-control" name="vehicle_no" value="{{ old('vehicle_no', $dispatchSheet->vehicle_no) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Driver Name</label>
                    <input type="text" class="form-control" name="driver_name" value="{{ old('driver_name', $dispatchSheet->driver_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Driver Phone</label>
                    <input type="text" class="form-control" name="driver_phone" value="{{ old('driver_phone', $dispatchSheet->driver_phone) }}" placeholder="+91...">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">L.R. No.</label>
                    <input type="text" class="form-control" name="lr_no" value="{{ old('lr_no', $dispatchSheet->lr_no) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">E-Way No.</label>
                    <input type="text" class="form-control" name="eway_no" value="{{ old('eway_no', $dispatchSheet->eway_no) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transport</label>
                    <input type="text" class="form-control" name="transport_name" value="{{ old('transport_name', $dispatchSheet->transport_name) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transport ID</label>
                    <input type="text" class="form-control" name="transport_id" value="{{ old('transport_id', $dispatchSheet->transport_id) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $dispatchSheet->notes) }}</textarea>
            </div>

            <hr class="section-divider">
            <h6 class="mb-2">Products in this Dispatch</h6>

            <x-sku-picker placeholder="Type product name or code to add more..." />

            {{-- Old input wins over the saved rows, so a failed save keeps the
                 user's edits instead of reverting to what is stored. --}}
            <x-line-items qty-label="Quantity" :show-price="true"
                          :items="$dispatchSheet->items->map(fn($i) => ['sku_id' => $i->sku_id, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price])->all()" />

            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                <a href="{{ route('dispatch-sheets.show', $dispatchSheet) }}" class="btn btn-outline-secondary">Cancel</a>
                <span class="fa-summary" id="lineItemsSummary"></span>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/line-items.js') }}?v={{ filemtime(public_path('js/line-items.js')) }}"></script>
@endpush
