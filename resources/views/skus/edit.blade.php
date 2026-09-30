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
                <h6 class="mb-2">Current Stock</h6>
                @php $totalOnHand = $stockRecords->sum('on_hand'); @endphp
                @if($totalOnHand > 0)
                <div class="table-responsive mb-4">
                    <table class="table table-stack table-bordered table-sm mb-0">
                        <thead><tr><th>Godown</th><th class="text-end">On-hand</th><th class="text-end">Reserved</th><th class="text-end">Available</th></tr></thead>
                        <tbody>
                            @foreach($stockRecords as $record)
                                @continue($record->on_hand == 0 && $record->reserved == 0)
                                <tr>
                                    <td>{{ $record->godown->code }} — {{ $record->godown->name }}</td>
                                    <td class="text-end">{{ number_format($record->on_hand, 3) }}</td>
                                    <td class="text-end">{{ number_format($record->reserved, 3) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($record->available, 3) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="form-hint mt-0 mb-4">No stock in any godown yet — use "Add Stock" below, or receive it via GRN.</p>
                @endif
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
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $sku->category) }}" list="categoryList">
                            <datalist id="categoryList">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label for="unit_of_measure" class="form-label">Base Unit</label>
                            <select class="form-select" id="unit_of_measure" name="unit_of_measure">
                                @foreach(['Pcs', 'Kgs', 'Ltrs', 'Mtrs', 'Ft', 'Nos', 'Box', 'Set', 'Roll', 'Bundle'] as $uom)
                                <option value="{{ $uom }}" {{ old('unit_of_measure', $sku->unit_of_measure) === $uom ? 'selected' : '' }}>{{ $uom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <x-secondary-unit-picker
                                :secondary-unit="$sku->secondary_unit_of_measure"
                                :conversion-rate="$sku->conversion_rate"
                                :show-clear="true" />
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
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold', $sku->low_stock_threshold) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="hsn_code" class="form-label">HSN Code</label>
                            <input type="text" class="form-control @error('hsn_code') is-invalid @enderror" id="hsn_code" name="hsn_code" value="{{ old('hsn_code', $sku->hsn_code) }}" maxlength="20">
                            @error('hsn_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" value="{{ old('weight', $sku->weight) }}" step="0.001" min="0" inputmode="decimal">
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    <x-godown-stock-picker
                        :godowns="$godowns"
                        heading="Add Stock"
                        hint="Adds this quantity to the chosen godown right now, the same as a Stock Correction. Leave both blank to make no stock change here." />

                    <hr class="my-4">
                    <h6 class="mb-1"><i class="bi bi-shield-lock me-1"></i> Admin Approval Required</h6>
                    <p class="form-hint mt-0 mb-3">Saving any change to a product needs an admin's password, entered fresh here — not just whoever is signed in. If you're staff, ask an admin to type theirs.</p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="admin_email" class="form-label">Admin Email</label>
                            <input type="email" class="form-control @error('admin_email') is-invalid @enderror" id="admin_email" name="admin_email" autocomplete="off">
                            @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="admin_password" class="form-label">Admin Password</label>
                            <input type="password" class="form-control @error('admin_password') is-invalid @enderror" id="admin_password" name="admin_password" autocomplete="off">
                            @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update SKU</button>
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
