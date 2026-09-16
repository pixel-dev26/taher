@extends('layouts.app')
@section('title', 'Dispatch')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dispatch</li>
@endsection
@section('content')
@php
    $subtitles = [
        'to-send' => 'Sheets waiting to go out',
        'mine'    => 'Dispatch sheets you created',
        'history' => 'Every dispatch, with filters and exports',
    ];
@endphp

<x-page-header title="Dispatch" :subtitle="$subtitles[$tab]">
    <a href="{{ route('dispatch-sheets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Dispatch
    </a>
</x-page-header>

{{-- One screen, three views of the same table. These used to be three
     separate sidebar entries: Dispatches, Send Out Stock, Dispatch History. --}}
<ul class="nav nav-pills seg-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'to-send' ? 'active' : '' }}" href="{{ route('dispatch-sheets.index', ['tab' => 'to-send']) }}">
            To send
            @if($pendingCount > 0)<span class="badge bg-danger ms-1">{{ $pendingCount }}</span>@endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'mine' ? 'active' : '' }}" href="{{ route('dispatch-sheets.index', ['tab' => 'mine']) }}">Mine</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('dispatch-sheets.index', ['tab' => 'history']) }}">History</a>
    </li>
</ul>

@if($tab !== 'to-send')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end" data-autosubmit-filters>
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-6 col-lg-2">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Waiting</option>
                    <option value="dispatched" {{ request('status') === 'dispatched' ? 'selected' : '' }}>Sent</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            @if($tab === 'history')
            <div class="col-6 col-lg-2">
                <label class="form-label" for="godown_id">Godown</label>
                <select name="godown_id" id="godown_id" class="form-select">
                    <option value="">All</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ (string) request('godown_id') === (string) $g->id ? 'selected' : '' }}>{{ $g->code }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-6 col-lg-2">
                <label class="form-label" for="date_from">From</label>
                <input type="date" name="date_from" id="date_from" class="form-control"
                       value="{{ request('date_from', $tab === 'history' ? today()->subDays(30)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="date_to">To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
            @if($tab === 'history')
            @php
                // Send the dates actually on screen, not the raw query string —
                // the tab defaults them, and the exports need them explicitly.
                $exportParams = array_merge(request()->except('tab'), [
                    'date_from' => request('date_from', today()->subDays(30)->format('Y-m-d')),
                    'date_to' => request('date_to', today()->format('Y-m-d')),
                ]);
            @endphp
            <div class="col-12 d-none d-md-flex gap-2">
                <a href="{{ route('reports.dispatch-register.export.excel', $exportParams) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel
                </a>
                <a href="{{ route('reports.dispatch-register.export.pdf', $exportParams) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </a>
            </div>
            @endif
        </form>
    </div>
</div>
@endif

@if($tab === 'to-send' && $sheets->count() > 0)
    @php
        $overdue = $sheets->filter(fn($s) => $s->delivery_date && $s->delivery_date->isPast() && !$s->delivery_date->isToday())->count();
        $dueToday = $sheets->filter(fn($s) => $s->delivery_date && $s->delivery_date->isToday())->count();
    @endphp
    <div class="alert {{ $overdue ? 'alert-danger' : ($dueToday ? 'alert-warning' : 'alert-info') }} border-0 d-flex align-items-center gap-2 mb-3">
        <i class="bi {{ $overdue ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill' }}"></i>
        <div>
            <strong>{{ $sheets->total() }} waiting</strong>
            @if($overdue) — <span class="fw-bold">{{ $overdue }} overdue</span>@endif
            @if($dueToday) — <span class="fw-bold">{{ $dueToday }} due today</span>@endif
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        @if($sheets->count() > 0)
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>Dispatch #</th>
                        <th>Customer</th>
                        <th>Godown</th>
                        <th>Items</th>
                        @if($tab === 'to-send')<th>Delivery</th>@else<th>Status</th><th>Created</th>@endif
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sheets as $sheet)
                        @php
                            $late = $sheet->delivery_date && $sheet->delivery_date->isPast() && !$sheet->delivery_date->isToday();
                            $today = $sheet->delivery_date && $sheet->delivery_date->isToday();
                        @endphp
                        <tr class="{{ $tab === 'to-send' ? ($late ? 'row-stock-critical' : ($today ? 'row-stock-low' : '')) : '' }}">
                            <td><a href="{{ route('dispatch-sheets.show', $sheet) }}" class="fw-bold text-decoration-none">{{ $sheet->ds_number }}</a></td>
                            <td>{{ $sheet->customer_name ?? '-' }}</td>
                            <td>{{ $sheet->godown->code ?? '-' }}</td>
                            <td>
                                <details>
                                    <summary style="cursor:pointer;">{{ $sheet->items->count() }} items</summary>
                                    <div class="small mt-1">
                                        @foreach($sheet->items as $item)
                                            <div>{{ $item->sku->code }} — {{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }} {{ $item->sku->unit_of_measure }}</div>
                                        @endforeach
                                    </div>
                                </details>
                            </td>
                            @if($tab === 'to-send')
                                <td>
                                    {{ $sheet->delivery_date?->format('d M Y') ?? '-' }}
                                    @if($late)<span class="badge badge-overdue ms-1">Overdue</span>
                                    @elseif($today)<span class="badge badge-today ms-1">Today</span>@endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-success js-confirm-dispatch"
                                            data-ds="{{ $sheet->ds_number }}"
                                            data-customer="{{ $sheet->customer_name }}"
                                            data-action="{{ route('fulfillment.confirm', $sheet) }}">
                                        <i class="bi bi-check-circle me-1"></i> Send out
                                    </button>
                                </td>
                            @else
                                <td>@include('components.status-badge', ['status' => $sheet->status])</td>
                                <td class="small text-muted">{{ $sheet->created_at->format('d M Y') }}</td>
                                <td><a href="{{ route('dispatch-sheets.show', $sheet) }}" class="btn btn-outline-primary"><i class="bi bi-eye me-1"></i> View</a></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards p-3">
            @foreach($sheets as $sheet)
                @php
                    $late = $sheet->delivery_date && $sheet->delivery_date->isPast() && !$sheet->delivery_date->isToday();
                    $today = $sheet->delivery_date && $sheet->delivery_date->isToday();
                @endphp
                <div class="card-list-item {{ $tab === 'to-send' ? ($late ? 'row-stock-critical' : ($today ? 'row-stock-low' : '')) : '' }}">
                    <div class="cl-row mb-2">
                        <a href="{{ route('dispatch-sheets.show', $sheet) }}" class="fw-bold text-decoration-none">{{ $sheet->ds_number }}</a>
                        @if($tab === 'to-send')
                            @if($late)<span class="badge badge-overdue">Overdue</span>
                            @elseif($today)<span class="badge badge-today">Today</span>@endif
                        @else
                            @include('components.status-badge', ['status' => $sheet->status])
                        @endif
                    </div>
                    <div class="cl-row"><span class="cl-label">Customer</span><span class="cl-value">{{ $sheet->customer_name ?? '-' }}</span></div>
                    <div class="cl-row"><span class="cl-label">Godown</span><span class="cl-value">{{ $sheet->godown->code ?? '-' }}</span></div>
                    <div class="cl-row"><span class="cl-label">{{ $tab === 'to-send' ? 'Delivery' : 'Created' }}</span>
                        <span class="cl-value">{{ $tab === 'to-send' ? ($sheet->delivery_date?->format('d M Y') ?? '-') : $sheet->created_at->format('d M Y') }}</span></div>
                    <details class="mt-2">
                        <summary class="cl-label" style="cursor:pointer;">{{ $sheet->items->count() }} items</summary>
                        @foreach($sheet->items as $item)
                            <div class="cl-row mt-1">
                                <span class="cl-label">{{ $item->sku->code }}</span>
                                <span class="cl-value">{{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }} {{ $item->sku->unit_of_measure }}</span>
                            </div>
                        @endforeach
                    </details>
                    @if($tab === 'to-send')
                        <button type="button" class="btn btn-success w-100 mt-3 js-confirm-dispatch"
                                data-ds="{{ $sheet->ds_number }}"
                                data-customer="{{ $sheet->customer_name }}"
                                data-action="{{ route('fulfillment.confirm', $sheet) }}">
                            <i class="bi bi-check-circle me-1"></i> Send out
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <i class="bi bi-{{ $tab === 'to-send' ? 'truck' : 'file-earmark-text' }}"></i>
            <div class="empty-text">
                {{ $tab === 'to-send' ? 'All clear — nothing waiting to go out.' : 'No dispatches found' }}
            </div>
            <div class="empty-hint">
                @if($tab === 'to-send')
                    New dispatch sheets appear here once created.
                @else
                    Try widening the date range or clearing the filters.
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@if($sheets->count() > 0)
    <div class="mt-3">{{ $sheets->links() }}</div>
@endif

{{-- One shared confirmation. Sending stock out is irreversible, so the check
     stays — what went away is the read-only page that used to sit in between. --}}
<div class="modal fade" id="confirmDispatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="confirmDispatchForm">
                @csrf @method('PATCH')
                <div class="modal-body confirm-modal-body">
                    <div class="confirm-icon"><i class="bi bi-truck"></i></div>
                    <h5 class="mb-2">Send this dispatch out?</h5>
                    <p class="text-muted mb-0">
                        <strong id="confirmDsNumber"></strong><span id="confirmCustomer"></span><br>
                        Stock will leave the godown. This cannot be undone.
                    </p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Not yet</button>
                    <button type="submit" class="btn btn-primary">Yes, send it out</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-confirm-dispatch');
    if (!btn) return;

    document.getElementById('confirmDispatchForm').action = btn.dataset.action;
    document.getElementById('confirmDsNumber').textContent = btn.dataset.ds;
    document.getElementById('confirmCustomer').textContent =
        btn.dataset.customer ? ' — ' + btn.dataset.customer : '';

    new bootstrap.Modal(document.getElementById('confirmDispatchModal')).show();
});
</script>
@endpush
