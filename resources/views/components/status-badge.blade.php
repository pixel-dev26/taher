@props(['status'])
@php
$classes = match($status) {
    'pending' => 'badge-pending',
    'dispatched', 'completed' => 'badge-dispatched',
    'cancelled', 'rejected' => 'badge-cancelled',
    default => 'bg-secondary',
};
$labels = match($status) {
    'pending' => 'Waiting',
    'dispatched' => 'Sent',
    'completed' => 'Done',
    'cancelled' => 'Cancelled',
    'rejected' => 'Rejected',
    default => ucfirst($status),
};
@endphp
<span class="badge {{ $classes }}">{{ $labels }}</span>
