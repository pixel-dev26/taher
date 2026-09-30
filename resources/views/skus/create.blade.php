@extends('layouts.app')
@section('title', 'Add SKU')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('skus.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Add SKU</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Add New SKU</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('skus.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="code" class="form-label">SKU Code *</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" list="categoryList" placeholder="Other">
                            <datalist id="categoryList">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label">Base Unit</label>
                            <select class="form-select @error('unit_of_measure') is-invalid @enderror" id="unit_of_measure" name="unit_of_measure">
                                @foreach(['Pcs', 'Kgs', 'Ltrs', 'Mtrs', 'Ft', 'Nos', 'Box', 'Set', 'Roll', 'Bundle'] as $uom)
                                <option value="{{ $uom }}" {{ old('unit_of_measure', 'Pcs') === $uom ? 'selected' : '' }}>{{ $uom }}</option>
                                @endforeach
                            </select>
                            @error('unit_of_measure') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-secondary-unit-picker />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price per Unit</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0" inputmode="decimal">
                                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-hint">Starting price. Each batch you receive with its own price updates the average.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                            <input type="number" class="form-control @error('low_stock_threshold') is-invalid @enderror" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold') }}" min="0" placeholder="10">
                            @error('low_stock_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="hsn_code" class="form-label">HSN Code</label>
                            <input type="text" class="form-control @error('hsn_code') is-invalid @enderror" id="hsn_code" name="hsn_code" value="{{ old('hsn_code') }}">
                            @error('hsn_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" value="{{ old('weight') }}" step="0.001" min="0" inputmode="decimal">
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-1">Opening Stock <span class="text-muted fw-normal">(optional)</span></h6>
                    <p class="form-hint mt-0 mb-3">Puts this product into stock at one godown right away, the same as a Stock Correction would. Leave both blank to start at zero everywhere and receive stock later via GRN.</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="target_godown_id" class="form-label">Godown</label>
                            <select class="form-select @error('target_godown_id') is-invalid @enderror" id="target_godown_id" name="target_godown_id" data-stock-url-template="{{ route('api.godown-stock', ['godown' => '__ID__']) }}">
                                <option value="">— None —</option>
                                @foreach($godowns as $godown)
                                <option value="{{ $godown->id }}" {{ old('target_godown_id') == $godown->id ? 'selected' : '' }}>{{ $godown->code }} — {{ $godown->name }}</option>
                                @endforeach
                            </select>
                            @error('target_godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="opening_quantity" class="form-label">Opening Quantity</label>
                            <input type="number" class="form-control @error('opening_quantity') is-invalid @enderror" id="opening_quantity" name="opening_quantity" value="{{ old('opening_quantity') }}" step="0.001" min="0" inputmode="decimal">
                            @error('opening_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div id="godownStockPreview" class="mb-3" hidden>
                        <div class="form-hint mb-1">Already in this godown:</div>
                        <div class="list-group list-group-flush border rounded" id="godownStockList" style="max-height: 220px; overflow-y: auto;"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create SKU</button>
                        <a href="{{ route('skus.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/secondary-unit-picker.js') }}?v={{ filemtime(public_path('js/secondary-unit-picker.js')) }}"></script>
<script src="{{ asset('js/godown-stock-preview.js') }}?v={{ filemtime(public_path('js/godown-stock-preview.js')) }}"></script>
@endsection
