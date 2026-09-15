@extends('layouts.app')
@section('title', 'Create New Dispatch')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dispatch-sheets.index') }}">Dispatch</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection
@section('content')
<div class="page-header">
    <h4><i class="bi bi-file-earmark-plus me-2"></i>Create New Dispatch</h4>
    <p class="page-subtitle">Reserve stock for dispatch from a godown</p>
</div>

{{-- Step Indicator --}}
<div class="step-indicator">
    <div class="step active">Dispatch Details</div>
    <div class="step">Add Products</div>
    <div class="step">Save</div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('dispatch-sheets.store') }}" id="dsForm">
            @csrf

            <x-form-errors />

            {{-- Section 1: Godown & Delivery Date --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="godown_id" class="form-label required">Godown</label>
                    <select class="form-select @error('godown_id') is-invalid @enderror" id="godown_id" name="godown_id" required>
                        <option value="">Select Godown</option>
                        @foreach($godowns as $godown)
                        <option value="{{ $godown->id }}" {{ old('godown_id') == $godown->id ? 'selected' : '' }}>{{ $godown->code }} - {{ $godown->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Select the godown to dispatch from</div>
                    @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="delivery_date" class="form-label">Delivery Date</label>
                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" value="{{ old('delivery_date') }}" min="{{ today()->format('Y-m-d') }}">
                    <div class="form-hint">When should this be delivered?</div>
                </div>
            </div>

            <hr class="section-divider">

            {{-- Section 2: Customer & Address --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="customer_name" class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" placeholder="e.g. Sharma Traders">
                    <div class="form-hint">Who is this dispatch for?</div>
                </div>
                <div class="col-md-6">
                    <label for="delivery_address" class="form-label">Delivery Address</label>
                    <input type="text" class="form-control" id="delivery_address" name="delivery_address" value="{{ old('delivery_address') }}" placeholder="Full delivery address">
                    <div class="form-hint">Where should the goods be delivered?</div>
                </div>
            </div>

            <hr class="section-divider">

            {{-- Section 3: Vehicle & Driver --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="vehicle_no" class="form-label">Vehicle Number</label>
                    <input type="text" class="form-control" id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no') }}" placeholder="e.g. MH 12 AB 1234">
                    <div class="form-hint">Vehicle registration number</div>
                </div>
                <div class="col-md-4">
                    <label for="driver_name" class="form-label">Driver Name</label>
                    <input type="text" class="form-control" id="driver_name" name="driver_name" value="{{ old('driver_name') }}" placeholder="Full name of driver">
                </div>
                <div class="col-md-4">
                    <label for="driver_phone" class="form-label">Driver Phone</label>
                    <input type="text" class="form-control" id="driver_phone" name="driver_phone" value="{{ old('driver_phone') }}" placeholder="+91 98765 43210">
                    <div class="form-hint">Mobile number for contact</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions or remarks...">{{ old('notes') }}</textarea>
                <div class="form-hint">Optional notes for this dispatch</div>
            </div>

            <hr class="section-divider">

            {{-- Section 4: Line Items --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Add Products</h6>

            <x-sku-picker require-godown show-available placeholder="Type product name or code..." />

            <x-line-items show-available qty-label="Quantity" />

            {{-- Submit --}}
            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Create Dispatch Sheet
                </button>
                <a href="{{ route('dispatch-sheets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <span class="fa-summary" id="lineItemsSummary"></span>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/line-items.js') }}?v={{ filemtime(public_path('js/line-items.js')) }}"></script>
@endpush
