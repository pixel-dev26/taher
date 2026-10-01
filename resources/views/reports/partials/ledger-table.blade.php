{{--
    One ledger table, reused for the Stock In / Stock Out / Full Activity
    sections of the Daily Report. Expects $rows (a collection of StockLedger
    entries with sku/godown/performer loaded) and $emptyText.
--}}
@php
    $ledgerBadge = fn($type) => match($type) {
        'stock_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)'],
        'reserved' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)'],
        'reserve_released' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)'],
        'dispatch_out' => ['bg' => 'var(--coral-soft)', 'color' => 'var(--coral-dark)'],
        'transfer_out' => ['bg' => 'var(--warning-soft)', 'color' => 'var(--warning-dark)'],
        'transfer_in' => ['bg' => 'var(--brand-soft)', 'color' => 'var(--brand-dark)'],
        'transfer_rejected' => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
        'adjustment' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info-dark)'],
        default => ['bg' => '#F0F1F3', 'color' => '#5B6470'],
    };
@endphp
<div class="table-responsive">
    <table class="table table-bordered table-sm mb-0">
        <thead>
            <tr><th>Time</th><th>Type</th><th>SKU</th><th>Product</th><th class="text-end">Qty</th><th>Godown</th><th>Reference</th><th>By</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $entry)
                @php $b = $ledgerBadge($entry->movement_type); @endphp
                <tr>
                    <td class="small">{{ $entry->created_at->format('H:i') }}</td>
                    <td><span class="badge" style="background:{{ $b['bg'] }}; color:{{ $b['color'] }};">{{ str_replace('_', ' ', $entry->movement_type) }}</span></td>
                    <td><code>{{ $entry->sku->code ?? '-' }}</code></td>
                    <td class="small">{{ $entry->sku->name ?? '-' }}</td>
                    <td class="text-end {{ $entry->quantity > 0 ? 'text-success' : 'text-danger' }}">
                        {{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0) }}
                    </td>
                    <td>{{ $entry->godown->code ?? '-' }}</td>
                    <td class="small">{{ ucfirst(str_replace('_', ' ', $entry->reference_type)) }} #{{ $entry->reference_id }}</td>
                    <td class="small">{{ $entry->performer->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-3">{{ $emptyText }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
