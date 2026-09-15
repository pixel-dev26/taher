@extends('layouts.app')
@section('title', 'Products')
@section('breadcrumb')
    <li class="breadcrumb-item active">Products</li>
@endsection
@section('content')
<x-page-header title="Products" subtitle="Your product catalogue">
    <a href="{{ route('skus.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-sm-4">
                <input type="text" name="search" class="form-control" placeholder="Search code or name..." value="{{ request('search') }}">
            </div>
            <div class="col-sm-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>

        {{-- Desktop: table --}}
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Category</th><th>UoM</th><th class="text-end" title="Average price per unit, weighted by quantity brought in">Avg. Price</th><th>Low Stock</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($skus as $sku)
                    <tr>
                        <td><code>{{ $sku->code }}</code></td>
                        <td>{{ $sku->name }}</td>
                        <td>{{ $sku->category }}</td>
                        <td>{{ $sku->unit_of_measure }}</td>
                        <td class="text-end {{ isset($prices[$sku->id]) ? '' : 'text-muted' }}">{{ \App\Support\Money::inr($prices[$sku->id]['average'] ?? null) }}</td>
                        <td>{{ $sku->low_stock_threshold }}</td>
                        <td>
                            <span class="badge {{ $sku->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $sku->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('skus.show', $sku) }}" class="btn btn-outline-primary btn-icon" aria-label="View {{ $sku->code }}" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('skus.edit', $sku) }}" class="btn btn-outline-primary btn-icon" aria-label="Edit {{ $sku->code }}" title="Edit"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><x-empty-state icon="bi-box-seam" text="No products found">Try a different search term or clear the filters.</x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card list --}}
        <div class="d-mobile-cards">
            @forelse($skus as $sku)
            <div class="card-list-item">
                <div class="cl-row mb-1">
                    <a href="{{ route('skus.show', $sku) }}" class="text-decoration-none"><code>{{ $sku->code }}</code></a>
                    <span class="badge {{ $sku->is_active ? 'bg-success' : 'bg-danger' }}">{{ $sku->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="fw-semibold mb-2">{{ $sku->name }}</div>
                <div class="cl-row"><span class="cl-label">Category</span><span class="cl-value">{{ $sku->category }}</span></div>
                <div class="cl-row"><span class="cl-label">Unit</span><span class="cl-value">{{ $sku->unit_of_measure }}</span></div>
                <div class="cl-row"><span class="cl-label">Avg. Price</span><span class="cl-value">{{ \App\Support\Money::inr($prices[$sku->id]['average'] ?? null) }}</span></div>
                <div class="cl-row mb-2"><span class="cl-label">Low Stock At</span><span class="cl-value">{{ $sku->low_stock_threshold }}</span></div>
                <div class="d-flex gap-2">
                    <a href="{{ route('skus.show', $sku) }}" class="btn btn-sm btn-outline-primary flex-fill">View</a>
                    <a href="{{ route('skus.edit', $sku) }}" class="btn btn-sm btn-outline-primary flex-fill">Edit</a>
                </div>
            </div>
            @empty
            <x-empty-state icon="bi-box-seam" text="No products found">Try a different search term or clear the filters.</x-empty-state>
            @endforelse
        </div>

        {{ $skus->links() }}
    </div>
</div>
@endsection
