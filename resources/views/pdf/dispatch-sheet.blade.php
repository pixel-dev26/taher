<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .header { border-bottom: 3px solid #002A85; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { color: #002A85; font-size: 20px; float: right; }
        .header .company { font-size: 14px; font-weight: bold; }
        .header .ds-number { font-size: 16px; color: #002A85; }
        .clear { clear: both; }
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { max-width: 320px; max-height: 70px; }
        .letterhead .company-contact { font-size: 9px; color: #002A85; font-weight: bold; margin-top: 4px; line-height: 1.5; }
        .info-section { margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; }
        .info-section table { width: 100%; }
        .info-section td { padding: 3px 5px; vertical-align: top; }
        .info-section .label { font-weight: bold; color: #555; width: 130px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background-color: #002A85; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; }
        .items-table td { border: 1px solid #ddd; padding: 6px; }
        .items-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .totals { margin-top: 10px; text-align: right; font-weight: bold; }
        .notes { margin-top: 15px; padding: 8px; background: #f5f5f5; border: 1px solid #ddd; }
        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 9px; border-top: 1px solid #ddd; padding-top: 8px; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 10px; }
        .status-pending { background: #FEF9E7; color: #F39C12; border: 1px solid #F39C12; }
        .status-dispatched { background: #E8F6ED; color: #15803D; border: 1px solid #15803D; }
        .status-cancelled { background: #FDEDEC; color: #A93330; border: 1px solid #A93330; }
    </style>
</head>
<body>
    @if($companyLogo)
        <div class="letterhead">
            <img src="{{ storage_path('app/public/' . $companyLogo) }}">
            <div class="company-contact">
                @if($companyAddress) {{ $companyAddress }} <br> @endif
                @if($companyPhone) Phone: {{ $companyPhone }} @endif
                @if($companyFax) &nbsp; Fax: {{ $companyFax }} @endif
                @if($companyEmail) &nbsp; Email: {{ $companyEmail }} @endif
            </div>
        </div>
    @endif
    <div class="header">
        <div style="float: left;">
            @unless($companyLogo)
                <div class="company">{{ $companyName }}</div>
            @endunless
        </div>
        <h1>DISPATCH SHEET</h1>
        <div style="float: right; text-align: right;">
            <div class="ds-number">{{ $sheet->ds_number }}</div>
            <span class="status status-{{ $sheet->status }}">{{ strtoupper($sheet->status) }}</span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td class="label">Created By:</td>
                <td>{{ $sheet->creator->name }}</td>
                <td class="label">Date:</td>
                <td>{{ $sheet->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Godown:</td>
                <td>{{ $sheet->godown->code }} - {{ $sheet->godown->name }}</td>
                <td class="label">Status:</td>
                <td>{{ ucfirst($sheet->status) }}</td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td class="label">Customer:</td>
                <td colspan="3">{{ $sheet->customer_name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Delivery Address:</td>
                <td colspan="3">{{ $sheet->delivery_address ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Delivery Date:</td>
                <td>{{ $sheet->delivery_date?->format('d M Y') ?? '-' }}</td>
                <td class="label">Vehicle:</td>
                <td>{{ $sheet->vehicle_no ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Driver:</td>
                <td>{{ $sheet->driver_name ?? '-' }}</td>
                <td class="label">Driver Phone:</td>
                <td>{{ $sheet->driver_phone ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px">S.No</th>
                <th style="width:80px">SKU Code</th>
                <th>Product Name</th>
                <th class="text-right" style="width:70px">Qty</th>
                <th style="width:50px">UoM</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->sku->code }}</td>
                <td>{{ $item->sku->name }}</td>
                <td class="text-right">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 3) }}</td>
                <td>{{ $item->sku->unit_of_measure }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        Total Items: {{ $sheet->items->count() }} &nbsp; | &nbsp; Total Quantity: {{ number_format($sheet->items->sum('quantity'), 0) }}
    </div>

    @if($sheet->notes)
    <div class="notes">
        <strong>Notes:</strong> {{ $sheet->notes }}
    </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y') }} at {{ now()->format('H:i') }} IST
    </div>
</body>
</html>
