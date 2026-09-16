@extends('layouts.app')
@section('title', 'Settings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
@endsection
@section('content')
<h4 class="mb-4"><i class="bi bi-gear"></i> System Settings</h4>

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Company Information</h5></div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}">
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="company_logo" class="form-label">Company Logo</label>
                    <input type="file" class="form-control @error('company_logo') is-invalid @enderror" id="company_logo" name="company_logo" accept="image/*">
                    @error('company_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($settings['company_logo'])
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Logo" style="max-height: 50px;">
                    </div>
                    @endif
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="company_address" class="form-label">Address</label>
                    <textarea class="form-control" id="company_address" name="company_address" rows="2">{{ old('company_address', $settings['company_address']) }}</textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="company_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}">
                </div>
                <div class="col-md-4">
                    <label for="company_fax" class="form-label">Fax</label>
                    <input type="text" class="form-control" id="company_fax" name="company_fax" value="{{ old('company_fax', $settings['company_fax']) }}">
                </div>
                <div class="col-md-4">
                    <label for="company_email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('company_email') is-invalid @enderror" id="company_email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}">
                    @error('company_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <label for="company_gstin" class="form-label">GSTIN</label>
                    <input type="text" class="form-control @error('company_gstin') is-invalid @enderror" id="company_gstin" name="company_gstin" value="{{ old('company_gstin', $settings['company_gstin']) }}" placeholder="e.g. 19AAAAA0000A1Z5">
                    <div class="form-hint">Printed on the Delivery Challan header</div>
                    @error('company_gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Inventory Settings</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="default_low_stock_threshold" class="form-label">Default Low Stock Threshold</label>
                    <input type="number" class="form-control" id="default_low_stock_threshold" name="default_low_stock_threshold" value="{{ old('default_low_stock_threshold', $settings['default_low_stock_threshold']) }}" min="0">
                </div>
                <div class="col-md-4">
                    <label for="default_gst_rate" class="form-label">Default GST Rate (%)</label>
                    <input type="number" class="form-control @error('default_gst_rate') is-invalid @enderror" id="default_gst_rate" name="default_gst_rate" value="{{ old('default_gst_rate', $settings['default_gst_rate']) }}" min="0" max="100" step="0.01">
                    <div class="form-hint">Split evenly into CGST + SGST on the Delivery Challan</div>
                    @error('default_gst_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button>
</form>
@endsection
