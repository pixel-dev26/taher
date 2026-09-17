@extends('layouts.app')
@section('title', 'Activity Log')
@section('breadcrumb')
    <li class="breadcrumb-item active">Activity Log</li>
@endsection
@section('content')
<x-page-header title="Activity Log" icon="bi-clock-history" subtitle="Every change made in the app, who made it, and when" />

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end" data-autosubmit-filters>
            <div class="col-6 col-lg-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Record Type</label>
                <select name="subject_type" class="form-select">
                    <option value="">All</option>
                    @foreach($subjectTypes as $type)
                    <option value="{{ $type }}" {{ request('subject_type') === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All</option>
                    @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'login' => 'Signed In', 'logout' => 'Signed Out', 'login_failed' => 'Failed Sign-in'] as $val => $label)
                    <option value="{{ $val }}" {{ request('action') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

@php
    $actionBadge = fn ($action) => match ($action) {
        'created' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)'],
        'updated' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)'],
        'deleted', 'login_failed' => ['bg' => 'var(--critical-soft)', 'color' => 'var(--critical-dark)'],
        'login', 'logout' => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
        default => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
    };
    $actionLabel = fn ($action) => match ($action) {
        'login' => 'Signed In',
        'logout' => 'Signed Out',
        'login_failed' => 'Failed Sign-in',
        default => ucfirst($action),
    };
    // Change values are whatever the model held: booleans print as nothing
    // and arrays crash e(); render every value through one formatter.
    $fmtVal = fn ($v) => match (true) {
        is_bool($v) => $v ? 'Yes' : 'No',
        is_null($v) || $v === '' => '—',
        is_array($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        default => (string) $v,
    };
@endphp

<div class="card">
    <div class="card-body p-0">
        @if($entries->count() > 0)
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead>
                    <tr><th>Date/Time</th><th>User</th><th>Action</th><th>Record</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                    @php $b = $actionBadge($entry->action); @endphp
                    <tr>
                        <td class="small">{{ $entry->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $entry->user_name ?? 'System' }}</td>
                        <td><span class="badge" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ $actionLabel($entry->action) }}</span></td>
                        <td>
                            <span class="fw-semibold">{{ $entry->subject_type_short }}</span>
                            <div class="small text-muted">{{ $entry->subject_label }}</div>
                        </td>
                        <td>
                            @if(!empty($entry->changes))
                                <details>
                                    <summary style="cursor:pointer; font-size:0.82rem;">View</summary>
                                    <div class="small mt-1">
                                        @php
                                            $before = $entry->changes['before'] ?? [];
                                            $after = $entry->changes['after'] ?? [];
                                            $fields = array_unique(array_merge(array_keys($before), array_keys($after)));
                                        @endphp
                                        @foreach($fields as $field)
                                            <div>
                                                <strong>{{ str_replace('_', ' ', $field) }}:</strong>
                                                @if(array_key_exists($field, $before) && array_key_exists($field, $after))
                                                    {{ $fmtVal($before[$field]) }} &rarr; {{ $fmtVal($after[$field]) }}
                                                @elseif(array_key_exists($field, $after))
                                                    {{ $fmtVal($after[$field]) }}
                                                @else
                                                    {{ $fmtVal($before[$field]) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards p-3">
            @foreach($entries as $entry)
            @php $b = $actionBadge($entry->action); @endphp
            <div class="card-list-item">
                <div class="cl-row mb-1">
                    <span class="badge" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ $actionLabel($entry->action) }}</span>
                    <span class="small text-muted">{{ $entry->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="mb-1"><strong>{{ $entry->subject_type_short }}</strong> <span style="font-size:0.82rem;">{{ $entry->subject_label }}</span></div>
                <div class="cl-row"><span class="cl-label">By</span><span class="cl-value">{{ $entry->user_name ?? 'System' }}</span></div>
                @if(!empty($entry->changes))
                    <details class="mt-2">
                        <summary class="cl-label" style="cursor:pointer;">Details</summary>
                        @php
                            $before = $entry->changes['before'] ?? [];
                            $after = $entry->changes['after'] ?? [];
                            $fields = array_unique(array_merge(array_keys($before), array_keys($after)));
                        @endphp
                        @foreach($fields as $field)
                            <div class="cl-row mt-1">
                                <span class="cl-label">{{ str_replace('_', ' ', $field) }}</span>
                                <span class="cl-value">
                                    @if(array_key_exists($field, $before) && array_key_exists($field, $after))
                                        {{ $fmtVal($before[$field]) }} &rarr; {{ $fmtVal($after[$field]) }}
                                    @elseif(array_key_exists($field, $after))
                                        {{ $fmtVal($after[$field]) }}
                                    @else
                                        {{ $fmtVal($before[$field]) }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </details>
                @endif
            </div>
            @endforeach
        </div>

        <div class="p-3">{{ $entries->links() }}</div>
        @else
        <x-empty-state icon="bi-clock-history" text="No activity in this range">Try widening the date range or clearing the filters.</x-empty-state>
        @endif
    </div>
</div>
@endsection
