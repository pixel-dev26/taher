@extends('layouts.app')
@section('title', 'Receive')
@section('breadcrumb')
    <li class="breadcrumb-item active">Receive</li>
@endsection
@section('content')
<x-page-header title="Receive" subtitle="Goods received into your godowns">
    <a href="{{ route('grn.create') }}" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-down-right me-1"></i> Receive Stock
    </a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-sm-4">
                <input type="text" name="search" class="form-control" placeholder="Search by receipt number or supplier..." value="{{ request('search') }}">
            </div>
            <div class="col-sm-3">
                <select name="godown_id" class="form-select">
                    <option value="">All Godowns</option>
                    @foreach($godowns as $g)
                    <option value="{{ $g->id }}" {{ request('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
            </div>
        </form>

        <div class="table-responsive d-desktop-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Godown</th>
                        <th>Date Received</th>
                        <th>Supplier</th>
                        <th>Challan / Invoice</th>
                        <th>Products</th>
                        <th>Recorded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                    <tr>
                        <td><a href="{{ route('grn.show', $grn) }}" class="fw-semibold">{{ $grn->grn_number }}</a></td>
                        <td>{{ $grn->godown->code ?? '-' }}</td>
                        <td>{{ $grn->receipt_date->format('d M Y') }}</td>
                        <td>{{ $grn->supplier_name ?? '-' }}</td>
                        <td>{{ $grn->challan_no ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $grn->items->count() }} items</span></td>
                        <td class="small text-muted">{{ $grn->creator->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('grn.show', $grn) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state icon="bi-inbox" text="No receipts recorded yet">Stock receipts will appear here after you receive goods</x-empty-state>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards">
            @forelse($grns as $grn)
            <a href="{{ route('grn.show', $grn) }}" class="card-list-item d-block text-decoration-none text-reset">
                <div class="cl-row mb-2">
                    <strong>{{ $grn->grn_number }}</strong>
                    <span class="badge bg-light text-dark border">{{ $grn->items->count() }} items</span>
                </div>
                <div class="cl-row"><span class="cl-label">Supplier</span><span class="cl-value">{{ $grn->supplier_name ?? '-' }}</span></div>
                <div class="cl-row"><span class="cl-label">Godown</span><span class="cl-value">{{ $grn->godown->code ?? '-' }}</span></div>
                <div class="cl-row"><span class="cl-label">Received</span><span class="cl-value">{{ $grn->receipt_date->format('d M Y') }}</span></div>
                <div class="cl-row"><span class="cl-label">Challan</span><span class="cl-value">{{ $grn->challan_no ?? '-' }}</span></div>
            </a>
            @empty
            <x-empty-state icon="bi-inbox" text="No receipts recorded yet">Stock receipts will appear here after you receive goods</x-empty-state>
            @endforelse
        </div>
        {{ $grns->links() }}
    </div>
</div>
@endsection
