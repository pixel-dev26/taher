@extends('layouts.app')
@section('title', 'New Stock Correction')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock-adjustments.index') }}">Corrections</a></li>
    <li class="breadcrumb-item active">New Correction</li>
@endsection
@section('content')
<x-page-header title="New Stock Correction" subtitle="Adjust stock for damage, counting errors, or initial loading" />

<div class="step-indicator">
    <div class="step active">Details</div>
    <div class="step">Add Products</div>
    <div class="step">Save</div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('stock-adjustments.store') }}">
            @csrf

            <x-form-errors />

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required" for="godown_id">Godown</label>
                    <select class="form-select @error('godown_id') is-invalid @enderror" name="godown_id" id="godown_id" required>
                        <option value="">Choose godown...</option>
                        @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Which godown needs correction?</div>
                    @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Reason</label>
                    <select class="form-select @error('reason') is-invalid @enderror" name="reason" required>
                        <option value="">Choose reason...</option>
                        @foreach(['initial_load' => 'Initial Load', 'grn_correction' => 'Receipt Correction', 'physical_count' => 'Physical Count', 'damage_loss' => 'Damage / Loss', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('reason') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Why is stock being corrected?</div>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference Document</label>
                    <input type="text" class="form-control" name="reference_doc" value="{{ old('reference_doc') }}" placeholder="e.g. counting sheet number">
                    <div class="form-hint">Optional - any related document number</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label required">Explain the Correction</label>
                <textarea class="form-control @error('reason_notes') is-invalid @enderror" name="reason_notes" rows="2" required placeholder="Describe why this correction is needed...">{{ old('reason_notes') }}</textarea>
                @error('reason_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <hr class="section-divider">
            <h6 class="mb-2">Products to Correct</h6>
            <div class="alert alert-info border-0 py-2 mb-3" style="background: var(--info-soft);">
                <i class="bi bi-info-circle me-1"></i>
                Use <strong>positive numbers</strong> to add stock, <strong>negative numbers</strong> (e.g. -5) to remove stock
            </div>

            <x-sku-picker placeholder="Type product name or code to find it..." />

            <x-line-items allow-negative qty-label="Quantity (+/-)" />

            <div class="form-action-bar">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Correction</button>
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <span class="fa-summary" id="lineItemsSummary"></span>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/line-items.js') }}?v={{ filemtime(public_path('js/line-items.js')) }}"></script>
@endpush
