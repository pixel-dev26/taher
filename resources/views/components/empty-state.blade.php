@props([
    'icon' => 'bi-inbox',
    'text' => 'Nothing here yet',
])

{{-- Replaces the same markup hand-written across a dozen views. --}}
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi {{ $icon }}"></i>
    <div class="empty-text">{{ $text }}</div>
    @if(trim($slot) !== '')
        <div class="empty-hint">{{ $slot }}</div>
    @endif
</div>
