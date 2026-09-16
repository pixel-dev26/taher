<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; padding: 20px; }
        .header { border-bottom: 3px solid #002A85; padding-bottom: 10px; margin-bottom: 12px; }
        .header h1 { color: #002A85; font-size: 20px; }
        .header .company { font-size: 14px; font-weight: bold; }
        .header .gstin { font-size: 10px; font-weight: bold; color: #002A85; margin-top: 2px; }
        .header .ds-number { font-size: 15px; color: #002A85; font-weight: bold; }
        .header .challan-date { font-size: 10px; color: #555; margin-top: 2px; }
        .clear { clear: both; }
        .letterhead { text-align: center; margin-bottom: 10px; }
        .letterhead img { max-width: 320px; max-height: 70px; }
        .letterhead .company-contact { font-size: 9px; color: #002A85; font-weight: bold; margin-top: 4px; line-height: 1.5; }

        .info-section { border: 1px solid #ddd; }
        .info-section .box-title { background-color: #002A85; color: #fff; font-weight: bold; font-size: 10px; padding: 4px 8px; }
        .info-section table { width: 100%; }
        .info-section td { padding: 3px 8px; vertical-align: top; font-size: 9.5px; }
        .info-section .label { font-weight: bold; color: #555; width: 95px; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .items-table th { background-color: #002A85; color: #fff; padding: 5px 4px; text-align: left; font-size: 8.5px; border: 1px solid #002A85; }
        .items-table td { border: 1px solid #ddd; padding: 5px 4px; font-size: 9px; }
        .items-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .items-table tfoot td { border-top: 2px solid #002A85; font-weight: bold; background-color: #f0f3f8; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-table { width: 100%; border: 1px solid #ddd; border-collapse: collapse; }
        .totals-table td { padding: 4px 8px; font-size: 9.5px; border-bottom: 1px solid #eee; }
        .totals-table td:last-child { text-align: right; }
        .totals-table .grand-total td { font-weight: bold; font-size: 10.5px; border-top: 2px solid #002A85; border-bottom: none; }
        .totals-table .eoe td { font-size: 8px; color: #999; text-align: right; border-bottom: none; padding-top: 0; }

        .signature-box { margin-top: 18px; text-align: center; font-size: 9px; }
        .signature-box .for-company { font-weight: bold; font-size: 10px; margin-top: 4px; }
        .signature-box .stamp-note { margin-top: 32px; color: #999; }
        .signature-box .signatory-line { margin-top: 4px; }

        .footer { margin-top: 14px; text-align: center; color: #999; font-size: 9px; border-top: 1px solid #ddd; padding-top: 8px; }
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
            <h1>DELIVERY CHALLAN</h1>
            <div class="ds-number">{{ $sheet->ds_number }}</div>
            <div class="challan-date">{{ $sheet->created_at->format('d M Y') }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <table style="width: 100%; margin-bottom: 12px;">
        <tr>
            <td style="width: 52%; vertical-align: top; padding-right: 8px;">
                <div class="info-section">
                    <div class="box-title">Customer Detail</div>
                    <table>
                        <tr>
                            <td class="label">M/S</td>
                            <td colspan="3">{{ $sheet->customer_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Address</td>
                            <td colspan="3">{{ $sheet->delivery_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Phone</td>
                            <td>{{ $sheet->customer_phone ?? '-' }}</td>
                            <td class="label">GSTIN</td>
                            <td>{{ $sheet->customer_gstin ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Place of Supply</td>
                            <td colspan="3">{{ $sheet->place_of_supply ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 48%; vertical-align: top; padding-left: 8px;">
                <div class="info-section">
                    <div class="box-title">Shipment Detail</div>
                    <table>
                        <tr>
                            <td class="label">Challan No.</td>
                            <td>{{ $sheet->ds_number }}</td>
                        </tr>
                        <tr>
                            <td class="label">Challan Date</td>
                            <td>{{ $sheet->created_at->format('d-M-Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Godown</td>
                            <td>{{ $sheet->godown->code }} - {{ $sheet->godown->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">L.R. No.</td>
                            <td>{{ $sheet->lr_no ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">E-Way No.</td>
                            <td>{{ $sheet->eway_no ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Transport</td>
                            <td>{{ $sheet->transport_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Transport ID</td>
                            <td>{{ $sheet->transport_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Vehicle Number</td>
                            <td>{{ $sheet->vehicle_no ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 24px;">Sr.<br>No.</th>
                <th rowspan="2">Name of Product / Service</th>
                <th rowspan="2" style="width: 55px;">HSN/SAC</th>
                <th rowspan="2" class="text-right" style="width: 45px;">Qty</th>
                <th rowspan="2" style="width: 32px;">Unit</th>
                <th rowspan="2" class="text-right" style="width: 55px;">Rate</th>
                <th rowspan="2" class="text-right" style="width: 65px;">Taxable Value</th>
                <th colspan="2" class="text-center">CGST</th>
                <th colspan="2" class="text-center">SGST</th>
                <th rowspan="2" class="text-right" style="width: 65px;">Total</th>
            </tr>
            <tr>
                <th class="text-right" style="width: 35px;">%</th>
                <th class="text-right" style="width: 55px;">Amount</th>
                <th class="text-right" style="width: 35px;">%</th>
                <th class="text-right" style="width: 55px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $i => $line)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $line->sku->name }}
                </td>
                <td>{{ $line->sku->hsn_code ?? '-' }}</td>
                <td class="text-right">{{ number_format($line->quantity, $line->quantity == intval($line->quantity) ? 0 : 3) }}</td>
                <td>{{ $line->sku->unit_of_measure }}</td>
                <td class="text-right">{{ number_format($line->rate, 2) }}</td>
                <td class="text-right">{{ number_format($line->taxable, 2) }}</td>
                <td class="text-right">{{ number_format($line->cgst_rate, 2) }}</td>
                <td class="text-right">{{ number_format($line->cgst_amount, 2) }}</td>
                <td class="text-right">{{ number_format($line->sgst_rate, 2) }}</td>
                <td class="text-right">{{ number_format($line->sgst_amount, 2) }}</td>
                <td class="text-right">{{ number_format($line->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">{{ number_format($sheet->items->sum('quantity'), 0) }}</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($taxableTotal, 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($cgstTotal, 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($sgstTotal, 2) }}</td>
                <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; margin-top: 12px;">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 10px;">
                <div class="info-section">
                    <div class="box-title">Total in Words</div>
                    <div style="padding: 6px 8px; font-size: 9.5px;">{{ $amountInWords }}</div>
                </div>
                <div class="info-section" style="margin-top: 8px;">
                    <div class="box-title">Terms and Conditions</div>
                    <div style="padding: 6px 8px; font-size: 8.5px; line-height: 1.6;">
                        1. This delivery challan is issued for the movement of goods and is not a tax invoice.<br>
                        2. Our responsibility ceases as soon as the goods leave our premises.<br>
                        3. Goods once dispatched will not be taken back.<br>
                        4. Please verify the quantity and condition of goods on receipt.
                    </div>
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; padding-left: 10px;">
                <table class="totals-table">
                    <tr>
                        <td>Taxable Amount</td>
                        <td>{{ \App\Support\Money::inr($taxableTotal) }}</td>
                    </tr>
                    <tr>
                        <td>Add: CGST</td>
                        <td>{{ \App\Support\Money::inr($cgstTotal) }}</td>
                    </tr>
                    <tr>
                        <td>Add: SGST</td>
                        <td>{{ \App\Support\Money::inr($sgstTotal) }}</td>
                    </tr>
                    <tr>
                        <td>Total Tax</td>
                        <td>{{ \App\Support\Money::inr($cgstTotal + $sgstTotal) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>Total Amount After Tax</td>
                        <td>{{ \App\Support\Money::inr($grandTotal) }}</td>
                    </tr>
                    <tr class="eoe">
                        <td colspan="2">(E &amp; O.E.)</td>
                    </tr>
                    <tr>
                        <td>GST Payable on Reverse Charge</td>
                        <td>N.A.</td>
                    </tr>
                </table>

                <div class="signature-box">
                    <div>Certified that the particulars given above are true and correct.</div>
                    <div class="for-company">For {{ $companyName }}</div>
                    <div class="stamp-note">This is a computer generated document, no signature required.</div>
                    <div class="signatory-line">Authorised Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y') }} at {{ now()->format('H:i') }} IST
    </div>
</body>
</html>
