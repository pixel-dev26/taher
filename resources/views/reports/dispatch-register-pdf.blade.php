<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background-color: #002A85; color: #fff; text-align: left; }
        .text-right { text-align: right; }
        h2 { color: #002A85; }
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { max-width: 320px; max-height: 70px; }
        .letterhead .company-contact { font-size: 9px; color: #002A85; font-weight: bold; margin-top: 4px; line-height: 1.5; }
    </style>
</head>
<body>
    @if($company['logo'])
        <div class="letterhead">
            <img src="{{ storage_path('app/public/' . $company['logo']) }}">
            <div class="company-contact">
                @if($company['address']) {{ $company['address'] }} <br> @endif
                @if($company['phone']) Phone: {{ $company['phone'] }} @endif
                @if($company['fax']) &nbsp; Fax: {{ $company['fax'] }} @endif
                @if($company['email']) &nbsp; Email: {{ $company['email'] }} @endif
            </div>
        </div>
    @else
        <div class="company">{{ $company['name'] }}</div>
    @endif
    <h2>Dispatch Register</h2>
    <p>Generated: {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr><th>DS No.</th><th>Godown</th><th>Created By</th><th>Customer</th><th>Date</th><th>Status</th><th>SKU</th><th>Product</th><th class="text-right">Qty</th></tr>
        </thead>
        <tbody>
            @foreach($sheets as $sheet)
                @foreach($sheet->items as $i => $item)
                <tr>
                    @if($i === 0)
                    <td rowspan="{{ $sheet->items->count() }}">{{ $sheet->ds_number }}</td>
                    <td rowspan="{{ $sheet->items->count() }}">{{ $sheet->godown->name }}</td>
                    <td rowspan="{{ $sheet->items->count() }}">{{ $sheet->creator->name }}</td>
                    <td rowspan="{{ $sheet->items->count() }}">{{ $sheet->customer_name ?? '-' }}</td>
                    <td rowspan="{{ $sheet->items->count() }}">{{ $sheet->created_at->format('d/m/Y') }}</td>
                    <td rowspan="{{ $sheet->items->count() }}">{{ ucfirst($sheet->status) }}</td>
                    @endif
                    <td>{{ $item->sku->code }}</td>
                    <td>{{ $item->sku->name }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
