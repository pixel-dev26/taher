@extends('layouts.app')
@section('title', 'Transfer Detail')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
    <li class="breadcrumb-item active">{{ $stockTransfer->transfer_number }}</li>
@endsection
@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-arrow-left-right me-2"></i>Transfer: {{ $stockTransfer->transfer_number }}</h4>
        <div class="page-subtitle">
            @include('components.status-badge', ['status' => $stockTransfer->status])
            @if($stockTransfer->status === 'pending' && $stockTransfer->created_at->diffInHours(now()) > 48)
                <span class="badge badge-overdue ms-1">Overdue</span>
            @endif
        </div>
    </div>
    <a href="{{ route('stock-transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Transfers
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        {{-- Transfer details using info-strip --}}
        <div class="info-strip">
            <div class="info-item">
                <div class="info-label">From Godown</div>
                <div class="info-value">{{ $stockTransfer->sourceGodown->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">To Godown</div>
                <div class="info-value">{{ $stockTransfer->destGodown->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Created By</div>
                <div class="info-value">{{ $stockTransfer->creator->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Date Created</div>
                <div class="info-value">{{ $stockTransfer->created_at->format('d M Y, h:i A') }}</div>
            </div>
            @if($stockTransfer->resolved_at)
            <div class="info-item">
                <div class="info-label">Resolved On</div>
                <div class="info-value">{{ $stockTransfer->resolved_at->format('d M Y, h:i A') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Resolved By</div>
                <div class="info-value">{{ $stockTransfer->resolver->name ?? '-' }}</div>
            </div>
            @endif
        </div>

        @if($stockTransfer->notes)
        <hr class="section-divider">
        <div class="mb-0">
            <div class="info-label mb-1">Notes</div>
            <div>{{ $stockTransfer->notes }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Items table --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-box-seam me-1"></i> Items in This Transfer
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-stack table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>SKU Code</th>
                        <th>Product Name</th>
                        <th class="text-end">Quantity</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockTransfer->items as $i => $item)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $item->sku->code }}</code></td>
                        <td>{{ $item->sku->name }}</td>
                        <td class="text-end fw-bold">{{ number_format($item->quantity, 0) }}</td>
                        <td>{{ $item->sku->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex gap-3 flex-wrap mb-4">
    <a href="{{ route('stock-transfers.challan', $stockTransfer) }}" class="btn btn-outline-secondary">
        <i class="bi bi-receipt me-1"></i> Download Road Challan
    </a>
</div>

{{-- Accept / Reject buttons (only for pending transfers) --}}
@if($stockTransfer->status === 'pending')
<div class="card">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-hand-index me-1"></i> What would you like to do?</h6>
        <div class="d-flex gap-3 flex-wrap">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#acceptModal">
                <i class="bi bi-check-lg me-1"></i> Accept Transfer
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-lg me-1"></i> Reject Transfer
            </button>
        </div>
    </div>
</div>

{{-- Accept Confirmation Modal --}}
<div class="modal fade" id="acceptModal" tabindex="-1" aria-labelledby="acceptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body confirm-modal-body">
                <div class="confirm-icon text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h5>Accept this transfer?</h5>
                <p>Stock will be added to your godown.</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('stock-transfers.accept', $stockTransfer) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Yes, Accept
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Reject Confirmation Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body confirm-modal-body">
                <div class="confirm-icon text-danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <h5>Reject this transfer?</h5>
                <p>Stock will be returned to the source godown.</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('stock-transfers.reject', $stockTransfer) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i> Yes, Reject
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
