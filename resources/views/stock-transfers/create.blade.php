@extends('layouts.app')
@section('title', 'Send Stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
    <li class="breadcrumb-item active">Send Stock</li>
@endsection
@section('content')
<div class="page-header">
    <h4><i class="bi bi-box-arrow-up-right me-2"></i>Send Stock to Another Godown</h4>
    <div class="page-subtitle">Pick a destination, add products, and send them on their way</div>
</div>

{{-- Step indicator --}}
<div class="step-indicator">
    <div class="step active">Choose Destination</div>
    <div class="step">Add Products</div>
    <div class="step">Send</div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('stock-transfers.store') }}">
            @csrf

            <x-form-errors />

            {{-- Step 1: Choose godowns --}}
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="source_godown_id" class="form-label required">Sending From</label>
                    <select class="form-select @error('source_godown_id') is-invalid @enderror" name="source_godown_id" id="source_godown_id" required>
                        <option value="">-- Pick a godown --</option>
                        @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ old('source_godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('source_godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-hint">Stock will be taken from here.</div>
                </div>
                <div class="col-md-6">
                    <label for="dest_godown_id" class="form-label required">Sending To</label>
                    <select class="form-select @error('dest_godown_id') is-invalid @enderror" name="dest_godown_id" id="dest_godown_id" required>
                        <option value="">-- Pick a godown --</option>
                        @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ old('dest_godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('dest_godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-hint">Choose the godown you want to send stock to.</div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Notes (optional)</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Any reason for this transfer or special instructions...">{{ old('notes') }}</textarea>
            </div>

            <hr class="section-divider">

            {{-- Step 2: Add products --}}
            <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Products to Send</h6>

            <x-sku-picker godown-field="source_godown_id" require-godown show-available
                          placeholder="Type product name or code..." />

            <x-line-items godown-field="source_godown_id" show-available qty-label="Quantity to Send" />

            {{-- Step 3: Send --}}
            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> Send Transfer
                </button>
                <a href="{{ route('stock-transfers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <span class="fa-summary" id="lineItemsSummary"></span>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/line-items.js') }}?v={{ filemtime(public_path('js/line-items.js')) }}"></script>
@endpush
