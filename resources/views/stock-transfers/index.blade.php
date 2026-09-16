@extends('layouts.app')
@section('title', 'Transfers')
@section('breadcrumb')
    <li class="breadcrumb-item active">Transfers</li>
@endsection
@section('content')
<x-page-header title="Transfers" icon="bi-arrow-left-right" subtitle="Move stock between godowns">
    <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Transfer
    </a>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2" data-autosubmit-filters>
            <div class="col-sm-4">
                <select name="godown_id" class="form-select">
                    <option value="">All Godowns</option>
                    @foreach($godowns as $g)
                    <option value="{{ $g->id }}" {{ request('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($transfers->count() > 0)
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $t)
                    <tr class="{{ $t->status === 'pending' && $t->created_at->diffInHours(now()) > 48 ? 'row-stock-critical' : '' }}">
                        <td>
                            <a href="{{ route('stock-transfers.show', $t) }}" class="fw-bold">
                                {{ $t->transfer_number }}
                            </a>
                        </td>
                        <td>{{ $t->sourceGodown->code ?? '-' }}</td>
                        <td>{{ $t->destGodown->code ?? '-' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->items->count() }} {{ $t->items->count() === 1 ? 'item' : 'items' }}</span>
                        </td>
                        <td>
                            @include('components.status-badge', ['status' => $t->status])
                            @if($t->status === 'pending' && $t->created_at->diffInHours(now()) > 48)
                                <span class="badge badge-overdue ms-1">Overdue</span>
                            @endif
                        </td>
                        <td>{{ $t->created_at->format('d M Y') }}</td>
                        <td>
                            @if($t->status === 'pending')
                                <a href="{{ route('stock-transfers.show', $t) }}" class="btn btn-outline-success">
                                    <i class="bi bi-check-circle me-1"></i> View &amp; Accept
                                </a>
                            @else
                                <a href="{{ route('stock-transfers.show', $t) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards p-3">
            @foreach($transfers as $t)
            @php $overdue = $t->status === 'pending' && $t->created_at->diffInHours(now()) > 48; @endphp
            <div class="card-list-item {{ $overdue ? 'row-stock-critical' : '' }}">
                <div class="cl-row mb-2">
                    <strong>{{ $t->transfer_number }}</strong>
                    <span>
                        @include('components.status-badge', ['status' => $t->status])
                        @if($overdue)<span class="badge badge-overdue ms-1">Overdue</span>@endif
                    </span>
                </div>
                <div class="cl-row"><span class="cl-label">Route</span><span class="cl-value">{{ $t->sourceGodown->code ?? '-' }} → {{ $t->destGodown->code ?? '-' }}</span></div>
                <div class="cl-row"><span class="cl-label">Items</span><span class="cl-value">{{ $t->items->count() }}</span></div>
                <div class="cl-row"><span class="cl-label">Date</span><span class="cl-value">{{ $t->created_at->format('d M Y') }}</span></div>
                <a href="{{ route('stock-transfers.show', $t) }}" class="btn {{ $t->status === 'pending' ? 'btn-success' : 'btn-outline-primary' }} w-100 mt-3">
                    @if($t->status === 'pending')
                        <i class="bi bi-check-circle me-1"></i> View &amp; Accept
                    @else
                        <i class="bi bi-eye me-1"></i> View
                    @endif
                </a>
            </div>
            @endforeach
        </div>
        {{ $transfers->links() }}
        @else
        <x-empty-state icon="bi-arrow-left-right" text="No transfers yet">When you move stock between godowns, it will show up here.</x-empty-state>
        @endif
    </div>
</div>
@endsection
