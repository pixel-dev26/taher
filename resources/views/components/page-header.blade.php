@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

{{--
    Standard page heading. The <h4> is hidden on mobile by the layout, because
    the sticky top bar already carries the page name — so anything passed in the
    slot (the page's primary action) takes the freed width.
--}}
<div {{ $attributes->merge(['class' => 'page-header d-flex justify-content-between align-items-start flex-wrap gap-2']) }}>
    <div>
        <h4>@if($icon)<i class="bi {{ $icon }} me-2"></i>@endif{{ $title }}</h4>
        @if($subtitle)
            <p class="page-subtitle mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="d-flex gap-2 flex-wrap page-header-actions">{{ $slot }}</div>
    @endif
</div>
