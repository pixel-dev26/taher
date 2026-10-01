@extends('layouts.app')
@section('title', 'Daily Report')
@section('breadcrumb')
    <li class="breadcrumb-item">Reports</li>
    <li class="breadcrumb-item active">Daily Report</li>
@endsection
@section('content')
@php
    $prevDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
    $nextDate = \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d');
    $isToday = $date === today()->format('Y-m-d');
@endphp
<div class="page-header"><h4><i class="bi bi-calendar2-check"></i> Daily Report</h4></div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <a href="{{ route('reports.daily', ['date' => $prevDate]) }}" class="btn btn-outline-secondary" title="Previous day"><i class="bi bi-chevron-left"></i></a>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="col-auto">
                <a href="{{ route('reports.daily', ['date' => $nextDate]) }}" class="btn btn-outline-secondary {{ $isToday ? 'disabled' : '' }}" title="Next day"><i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('reports.daily.pdf', ['date' => $date]) }}" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
            </div>
        </form>
        <div class="form-hint mt-2 mb-0">
            Nothing here is stored as a file — every report, today's or a past date, is rebuilt from the stock ledger each time, so it always matches what actually happened. Pick any earlier date and download its PDF whenever you need it.
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold text-success">+{{ number_format($totalIn, 0) }}</div>
                <div class="cl-label">Stock In</div>
                <div class="small text-muted mt-1">{{ $grnCount }} GRN{{ $grnCount === 1 ? '' : 's' }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold text-danger">-{{ number_format($totalOut, 0) }}</div>
                <div class="cl-label">Stock Out</div>
                <div class="small text-muted mt-1">{{ $dispatchCount }} dispatch{{ $dispatchCount === 1 ? '' : 'es' }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">{{ $transferOutCount }} / {{ $transferInCount }}</div>
                <div class="cl-label">Transfers Sent / Received</div>
                <div class="small text-muted mt-1">Between godowns</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">{{ $adjustmentCount }}</div>
                <div class="cl-label">Corrections</div>
                <div class="small text-muted mt-1">{{ $entries->count() }} ledger entr{{ $entries->count() === 1 ? 'y' : 'ies' }} total</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 text-success"><i class="bi bi-box-arrow-in-down"></i> Stock In</h6></div>
    <div class="card-body p-0">
        @include('reports.partials.ledger-table', ['rows' => $stockIn, 'emptyText' => 'No stock received on this date.'])
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0 text-danger"><i class="bi bi-box-arrow-up"></i> Stock Out</h6></div>
    <div class="card-body p-0">
        @include('reports.partials.ledger-table', ['rows' => $stockOut, 'emptyText' => 'No stock dispatched on this date.'])
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ul"></i> Full Activity ({{ $entries->count() }})</h6></div>
    <div class="card-body p-0">
        @include('reports.partials.ledger-table', ['rows' => $entries, 'emptyText' => 'No stock activity recorded on this date.'])
    </div>
</div>
@endsection
