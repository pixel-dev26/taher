@extends('layouts.app')
@section('title', 'Receive New Stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">Receive</a></li>
    <li class="breadcrumb-item active">Receive New Stock</li>
@endsection
@section('content')

{{-- Page Header --}}
<div class="page-header">
    <h4><i class="bi bi-box-arrow-in-down-right text-success"></i> Receive New Stock</h4>
    <p class="page-subtitle">Record items coming into your godown</p>
</div>

{{-- Step Indicator --}}
<div class="step-indicator">
    <div class="step active">Basic Details</div>
    <div class="step">Add Products</div>
    <div class="step">Save</div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('grn.store') }}" id="grnForm">
            @csrf

            <x-form-errors />

            {{-- Section 1: Basic Details --}}
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="godown_id" class="form-label required">Receiving Godown</label>
                    <select class="form-select @error('godown_id') is-invalid @enderror" id="godown_id" name="godown_id" required>
                        <option value="">-- Pick a godown --</option>
                        @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Which godown is receiving this stock?</div>
                    @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="receipt_date" class="form-label required">Date of Receiving</label>
                    <input type="date" class="form-control @error('receipt_date') is-invalid @enderror"
                           id="receipt_date" name="receipt_date"
                           value="{{ old('receipt_date', today()->format('Y-m-d')) }}"
                           max="{{ today()->format('Y-m-d') }}"
                           min="{{ today()->subDays(7)->format('Y-m-d') }}"
                           required>
                    <div class="form-hint">When did the goods arrive?</div>
                    @error('receipt_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="challan_no" class="form-label">Challan / Invoice Number</label>
                    <input type="text" class="form-control" id="challan_no" name="challan_no" value="{{ old('challan_no') }}" placeholder="e.g. INV-12345">
                    <div class="form-hint">Number on supplier's delivery slip</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="supplier_name" class="form-label">Supplier Name</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" placeholder="Who sent the goods?">
                    <div class="form-hint">Name of the company or person who sent goods</div>
                </div>
            </div>
            <div class="mb-4">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any extra details about this delivery (optional)">{{ old('notes') }}</textarea>
            </div>

            {{-- Section 2: Add Products --}}
            <hr class="section-divider">
            <h6 class="mb-3"><i class="bi bi-search"></i> Find and Add Products</h6>

            <x-sku-picker placeholder="Type product name or code to add..." />

            <x-line-items qty-label="Quantity" :show-price="true" />

            {{-- Section 3: Save --}}
            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <i class="bi bi-check-circle-fill me-1"></i> Save Stock Receipt
                </button>
                <a href="{{ route('grn.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <span class="fa-summary" id="lineItemsSummary"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/line-items.js') }}?v={{ filemtime(public_path('js/line-items.js')) }}"></script>
@endpush
