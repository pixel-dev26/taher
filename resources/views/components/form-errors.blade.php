{{--
    Summary of everything that went wrong, rendered at the top of a form.

    Per-row errors are also shown against their own input, but on a phone row 27
    is thousands of pixels below the fold — without this summary a failed save
    looks like nothing happened at all.
--}}
@if($errors->any())
    @php $count = count($errors->all()); @endphp
    <div class="alert alert-danger border-0 mb-3" role="alert">
        <div class="fw-bold mb-2">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ $count }} {{ \Illuminate\Support\Str::plural('problem', $count) }} — please fix and save again
        </div>
        <ul class="mb-0 ps-3">
            @foreach($errors->keys() as $key)
                @foreach($errors->get($key) as $message)
                    @if(\Illuminate\Support\Str::startsWith($key, 'items.'))
                        <li><a href="#item-row-{{ explode('.', $key)[1] ?? '' }}" class="alert-link">{{ $message }}</a></li>
                    @else
                        <li>{{ $message }}</li>
                    @endif
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
