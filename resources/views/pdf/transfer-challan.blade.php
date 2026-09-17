<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; padding: 8px 20px; }
        .header { border-bottom: 3px solid #002A85; padding-bottom: 4px; margin-bottom: 6px; }
        .header h1 { color: #002A85; font-size: 20px; }
        .header .company { font-size: 14px; font-weight: bold; }
        .header .gstin { font-size: 10px; font-weight: bold; color: #002A85; margin-top: 2px; }
        .header .ds-number { font-size: 15px; color: #002A85; font-weight: bold; }
        .header .challan-date { font-size: 10px; color: #555; margin-top: 2px; }
        .clear { clear: both; }
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { max-width: 320px; max-height: 70px; }
        .letterhead .company-contact { font-size: 9px; color: #002A85; font-weight: bold; margin-top: 4px; line-height: 1.5; }

        .info-section { border: 1px solid #ddd; page-break-inside: avoid; }
        .info-section .box-title { background-color: #002A85; color: #fff; font-weight: bold; font-size: 10px; padding: 2px 8px; }
        .info-section table { width: 100%; }
        .info-section td { padding: 1px 8px; vertical-align: top; font-size: 9.5px; }
        .info-section .label { font-weight: bold; color: #555; width: 85px; }
        .info-section .sub { font-weight: normal; color: #666; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .items-table th { background-color: #002A85; color: #fff; padding: 4px; text-align: left; font-size: 8.5px; border: 1px solid #002A85; }
        .items-table td { border: 1px solid #ddd; padding: 4px; font-size: 9px; }
        .items-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .items-table tbody tr { page-break-inside: avoid; }
        .items-table tfoot td { border-top: 2px solid #002A85; font-weight: bold; background-color: #f0f3f8; padding: 4px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .signature-box { margin-top: 30px; text-align: center; font-size: 9px; page-break-inside: avoid; }
        .signature-box .for-company { font-weight: bold; font-size: 10px; margin-top: 3px; }
        .signature-box .stamp-note { margin-top: 5px; color: #999; }
        .signature-box .signatory-line { margin-top: 3px; }

        .footer { margin-top: 4px; text-align: left; color: #999; font-size: 9px; border-top: 1px solid #ddd; padding-top: 4px; }
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
            @if($companyGstin)
                <div class="gstin">GSTIN: {{ $companyGstin }}</div>
            @endif
        </div>
        <div style="float: right; text-align: right;">
            <h1>STOCK TRANSFER CHALLAN</h1>
            <div class="ds-number">{{ $transfer->transfer_number }}</div>
            <div class="challan-date">{{ $transfer->created_at->format('d M Y') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div style="margin-bottom: 8px;">
        <div style="float: left; width: 50%;">
            <div class="info-section" style="margin-right: 8px;">
                <div class="box-title">Ship From (Consignor)</div>
                <table>
                    <tr>
                        <td class="label">Godown</td>
                        <td>{{ $transfer->sourceGodown->code }} - {{ $transfer->sourceGodown->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Address</td>
                        <td>{{ $transfer->sourceGodown->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phone</td>
                        <td>{{ $transfer->sourceGodown->contact_phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="float: left; width: 50%;">
            <div class="info-section" style="margin-left: 8px;">
                <div class="box-title">Ship To (Consignee)</div>
                <table>
                    <tr>
                        <td class="label">Godown</td>
                        <td>{{ $transfer->destGodown->code }} - {{ $transfer->destGodown->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Address</td>
                        <td>{{ $transfer->destGodown->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phone</td>
                        <td>{{ $transfer->destGodown->contact_phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="info-section" style="margin-bottom: 8px;">
        <div class="box-title">Transfer Detail</div>
        <table>
            <tr>
                <td class="label" style="width: 110px;">Transfer No.</td>
                <td>{{ $transfer->transfer_number }}</td>
                <td class="label" style="width: 80px;">Date</td>
                <td>{{ $transfer->created_at->format('d-M-Y') }}</td>
                <td class="label" style="width: 60px;">Status</td>
                <td class="text-capitalize">{{ ucfirst($transfer->status) }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 28px;">Sr.<br>No.</th>
                <th>Name of Product</th>
                <th style="width: 70px;">HSN/SAC</th>
                <th class="text-right" style="width: 70px;">Quantity</th>
                <th style="width: 60px;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->sku->name }}</td>
                <td>{{ $item->sku->hsn_code ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 3) }}</td>
                <td>{{ $item->sku->unit_of_measure }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">{{ number_format($transfer->items->sum('quantity'), 0) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 5px;">
        <div style="float: left; width: 58%;">
            @if($transfer->notes)
            <div class="info-section" style="margin-right: 10px;">
                <div class="box-title">Notes</div>
                <div style="padding: 6px 8px; font-size: 9.5px;">{{ $transfer->notes }}</div>
            </div>
            @endif
            <div class="info-section" style="margin-right: 10px; @if($transfer->notes) margin-top: 8px; @endif">
                <div class="box-title">Terms and Conditions</div>
                <div style="padding: 6px 8px; font-size: 8.5px; line-height: 1.6;">
                    1. This is a Stock Transfer Challan for internal movement of goods between godowns of {{ $companyName }}.<br>
                    2. This is not a sale document and no tax invoice is applicable.<br>
                    3. Please verify the quantity and condition of goods on receipt at the destination godown.
                </div>
            </div>
            <div class="footer" style="margin-right: 10px;">
                Generated on {{ now()->format('d M Y') }} at {{ now()->format('H:i') }} IST
            </div>
        </div>
        <div style="float: left; width: 42%;">
            <div style="margin-left: 10px;">
                <div class="signature-box">
                    <div>Certified that the particulars given above are true and correct.</div>
                    <div class="for-company">For {{ $companyName }}</div>
                    <div class="stamp-note">This is a computer generated document, no signature required.</div>
                    <div class="signatory-line">Authorised Signatory</div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>
