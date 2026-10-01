<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { max-width: 320px; max-height: 70px; }
        .letterhead .company-contact { font-size: 9px; color: #002A85; font-weight: bold; margin-top: 4px; line-height: 1.5; }
        .header { border-bottom: 3px solid #002A85; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { color: #002A85; font-size: 20px; }
        .header .company { font-size: 14px; font-weight: bold; }
        .header .report-date { font-size: 16px; color: #002A85; }
        .clear { clear: both; }

        .summary { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .summary td { border: 1px solid #ddd; padding: 8px 10px; width: 25%; vertical-align: top; }
        .summary .figure { font-size: 18px; font-weight: bold; }
        .summary .figure.in { color: #15803D; }
        .summary .figure.out { color: #DC2626; }
        .summary .label { font-size: 9px; color: #555; text-transform: uppercase; letter-spacing: 0.3px; }
        .summary .sub { font-size: 9px; color: #777; margin-top: 2px; }

        h2.section { font-size: 13px; color: #002A85; margin: 16px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.items th { background-color: #002A85; color: #fff; padding: 6px; text-align: left; font-size: 9px; }
        table.items td { border: 1px solid #ddd; padding: 5px 6px; font-size: 9.5px; }
        table.items tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .text-success { color: #15803D; }
        .text-danger { color: #DC2626; }
        .empty { color: #999; font-style: italic; padding: 6px 0; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8.5px; background: #F0F1F3; color: #5B6470; }

        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 9px; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    @if($companyLogo)
        <div class="letterhead">
            <img src="{{ storage_path('app/public/' . $companyLogo) }}">
            <div class="company-contact">
                @if($companyAddress) {{ $companyAddress }} <br> @endif
                @if($companyPhone) Phone: {{ $companyPhone }} @endif
            </div>
        </div>
    @endif
    <div class="header">
        <div style="float: left;">
            @unless($companyLogo)
                <div class="company">{{ $companyName }}</div>
            @endunless
        </div>
        <div style="float: right; text-align: right;">
            <h1>DAILY STOCK REPORT</h1>
            <div class="report-date">{{ \Carbon\Carbon::parse($date)->format('d M Y (l)') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="figure in">+{{ number_format($totalIn, 0) }}</div>
                <div class="label">Total Stock In</div>
                <div class="sub">{{ $grnCount }} GRN{{ $grnCount === 1 ? '' : 's' }} received</div>
            </td>
            <td>
                <div class="figure out">-{{ number_format($totalOut, 0) }}</div>
                <div class="label">Total Stock Out</div>
                <div class="sub">{{ $dispatchCount }} dispatch{{ $dispatchCount === 1 ? '' : 'es' }} sent</div>
            </td>
            <td>
                <div class="figure">{{ $transferOutCount }} / {{ $transferInCount }}</div>
                <div class="label">Transfers Sent / Received</div>
                <div class="sub">Between godowns</div>
            </td>
            <td>
                <div class="figure">{{ $adjustmentCount }}</div>
                <div class="label">Corrections</div>
                <div class="sub">{{ $entries->count() }} ledger entr{{ $entries->count() === 1 ? 'y' : 'ies' }} total</div>
            </td>
        </tr>
    </table>

    <h2 class="section">Stock In</h2>
    @if($stockIn->isEmpty())
        <div class="empty">No stock received on this date.</div>
    @else
        <table class="items">
            <thead>
                <tr><th>Time</th><th>Type</th><th>SKU</th><th>Product</th><th class="text-right">Qty</th><th>Godown</th><th>Reference</th></tr>
            </thead>
            <tbody>
                @foreach($stockIn as $entry)
                <tr>
                    <td>{{ $entry->created_at->format('H:i') }}</td>
                    <td><span class="badge">{{ str_replace('_', ' ', $entry->movement_type) }}</span></td>
                    <td>{{ $entry->sku->code ?? '-' }}</td>
                    <td>{{ $entry->sku->name ?? '-' }}</td>
                    <td class="text-right text-success">+{{ number_format(abs($entry->quantity), 0) }}</td>
                    <td>{{ $entry->godown->code ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry->reference_type)) }} #{{ $entry->reference_id }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="section">Stock Out</h2>
    @if($stockOut->isEmpty())
        <div class="empty">No stock dispatched on this date.</div>
    @else
        <table class="items">
            <thead>
                <tr><th>Time</th><th>Type</th><th>SKU</th><th>Product</th><th class="text-right">Qty</th><th>Godown</th><th>Reference</th></tr>
            </thead>
            <tbody>
                @foreach($stockOut as $entry)
                <tr>
                    <td>{{ $entry->created_at->format('H:i') }}</td>
                    <td><span class="badge">{{ str_replace('_', ' ', $entry->movement_type) }}</span></td>
                    <td>{{ $entry->sku->code ?? '-' }}</td>
                    <td>{{ $entry->sku->name ?? '-' }}</td>
                    <td class="text-right text-danger">-{{ number_format(abs($entry->quantity), 0) }}</td>
                    <td>{{ $entry->godown->code ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry->reference_type)) }} #{{ $entry->reference_id }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="section">Full Activity ({{ $entries->count() }} entries)</h2>
    @if($entries->isEmpty())
        <div class="empty">No stock activity recorded on this date.</div>
    @else
        <table class="items">
            <thead>
                <tr><th>Time</th><th>Type</th><th>SKU</th><th>Product</th><th class="text-right">Qty</th><th class="text-right">Balance</th><th>Godown</th><th>Reference</th><th>By</th></tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry->created_at->format('H:i') }}</td>
                    <td><span class="badge">{{ str_replace('_', ' ', $entry->movement_type) }}</span></td>
                    <td>{{ $entry->sku->code ?? '-' }}</td>
                    <td>{{ $entry->sku->name ?? '-' }}</td>
                    <td class="text-right {{ $entry->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($entry->balance_after, 0) }}</td>
                    <td>{{ $entry->godown->code ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry->reference_type)) }} #{{ $entry->reference_id }}</td>
                    <td>{{ $entry->performer->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Generated {{ now()->format('d M Y, h:i A') }} &middot; Reconstructed from the stock ledger, not a stored file — download again any time for the same figures.</div>
</body>
</html>
