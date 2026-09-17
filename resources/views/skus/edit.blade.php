@extends('layouts.app')
@section('title', 'Edit SKU')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('skus.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Edit SKU</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Edit SKU: {{ $sku->code }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('skus.update', $sku) }}">
                    @csrf @method('PUT')
                    <x-form-errors />
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">SKU Code</label>
                            <input type="text" class="form-control" value="{{ $sku->code }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $sku->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category *</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $sku->category) }}" list="categoryList" required>
                            <datalist id="categoryList">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label">Unit of Measure *</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure" required>
                                @foreach(['Pcs', 'Kgs', 'Ltrs', 'Mtrs', 'Ft', 'Nos', 'Box', 'Set', 'Roll', 'Bundle'] as $uom)
                                <option value="{{ $uom }}" {{ old('unit_of_measure', $sku->unit_of_measure) === $uom ? 'selected' : '' }}>{{ $uom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price per Unit</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $sku->price) }}" step="0.01" min="0" inputmode="decimal">
                                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-hint">Used for stock that has no price of its own, like opening stock. Batches received with a price are averaged in by quantity.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold', $sku->low_stock_threshold) }}" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hsn_code" class="form-label">HSN Code</label>
                            <input type="text" class="form-control @error('hsn_code') is-invalid @enderror" id="hsn_code" name="hsn_code" value="{{ old('hsn_code', $sku->hsn_code) }}" maxlength="20">
                            @error('hsn_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @if(auth()->user()->isAdmin())
                    <div class="mb-3 form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $sku->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                        <div class="form-hint">Untick to retire this product. Only an admin can change this, and only when it holds no stock.</div>
                    </div>
                    @elseif(! $sku->is_active)
                    <div class="mb-3 text-muted small"><i class="bi bi-info-circle me-1"></i>This product is inactive. Ask an admin to reactivate it.</div>
                    @endif
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update SKU</button>
                        <a href="{{ route('skus.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
