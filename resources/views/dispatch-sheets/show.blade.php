@extends('layouts.app')
@section('title', 'Dispatch #' . $dispatchSheet->ds_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dispatch-sheets.index') }}">Dispatch</a></li>
    <li class="breadcrumb-item active">{{ $dispatchSheet->ds_number }}</li>
@endsection
@section('content')
{{-- Header with Dispatch # and Status --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h4>
            <i class="bi bi-file-earmark-text me-2"></i>Dispatch #{{ $dispatchSheet->ds_number }}
            @include('components.status-badge', ['status' => $dispatchSheet->status])
        </h4>
        <p class="page-subtitle mb-0">
            Created {{ $dispatchSheet->created_at->format('d M Y') }} by {{ $dispatchSheet->creator->name }}
        </p>
    </div>
    <a href="{{ route('dispatch-sheets.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Dispatches
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        {{-- Info Strip --}}
        <div class="info-strip">
            <div class="info-item">
                <div class="info-label">Godown</div>
                <div class="info-value">{{ $dispatchSheet->godown->code }} - {{ $dispatchSheet->godown->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Created By</div>
                <div class="info-value">{{ $dispatchSheet->creator->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Created</div>
                <div class="info-value">{{ $dispatchSheet->created_at->format('d M Y, h:i A') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer</div>
                <div class="info-value">{{ $dispatchSheet->customer_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer Phone</div>
                <div class="info-value">{{ $dispatchSheet->customer_phone ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer GSTIN</div>
                <div class="info-value">{{ $dispatchSheet->customer_gstin ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Place of Supply</div>
                <div class="info-value">{{ $dispatchSheet->place_of_supply ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Delivery Date</div>
                <div class="info-value">{{ $dispatchSheet->delivery_date?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Delivery Address</div>
                <div class="info-value">{{ $dispatchSheet->delivery_address ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Vehicle</div>
                <div class="info-value">{{ $dispatchSheet->vehicle_no ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Driver</div>
                <div class="info-value">{{ $dispatchSheet->driver_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Driver Phone</div>
                <div class="info-value">{{ $dispatchSheet->driver_phone ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">L.R. No.</div>
                <div class="info-value">{{ $dispatchSheet->lr_no ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">E-Way No.</div>
                <div class="info-value">{{ $dispatchSheet->eway_no ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Transport</div>
                <div class="info-value">{{ $dispatchSheet->transport_name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Transport ID</div>
                <div class="info-value">{{ $dispatchSheet->transport_id ?? '-' }}</div>
            </div>
        </div>

        @if($dispatchSheet->notes)
            <div class="mt-2 mb-3 p-3 rounded" style="background-color: #F8F9FA;">
                <strong class="text-muted small text-uppercase">Notes</strong>
                <div class="mt-1">{{ $dispatchSheet->notes }}</div>
            </div>
        @endif

        {{-- Dispatched Alert --}}
        @if($dispatchSheet->status === 'dispatched')
        <div class="alert alert-success d-flex align-items-center gap-3 mb-3" style="border-left: 5px solid #15803D;">
            <i class="bi bi-check-circle-fill" style="font-size: 1.8rem; color: #15803D;"></i>
            <div>
                <strong class="d-block">Dispatched Successfully</strong>
                <span class="text-muted">By {{ $dispatchSheet->dispatcher->name ?? '-' }} on {{ $dispatchSheet->dispatched_at?->format('d M Y, h:i A') }}</span>
            </div>
        </div>
        @endif

        {{-- Cancelled Alert --}}
        @if($dispatchSheet->status === 'cancelled')
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-3" style="border-left: 5px solid #A93330;">
            <i class="bi bi-x-circle-fill" style="font-size: 1.8rem; color: #A93330;"></i>
            <div>
                <strong class="d-block">Dispatch Cancelled</strong>
                <span>{{ $dispatchSheet->cancel_reason }}</span>
                <br><small class="text-muted">{{ $dispatchSheet->cancelled_at?->format('d M Y, h:i A') }}</small>
            </div>
        </div>
        @endif

        <hr class="section-divider">

        {{-- Line Items --}}
        <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> Items in this Dispatch</h6>
        @php
            // Dispatch sheets created before rates were captured keep their old layout.
            $priced = $dispatchSheet->items->whereNotNull('unit_price')->isNotEmpty();
        @endphp
        <div class="table-responsive">
            <table class="table table-stack table-bordered">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>SKU Code</th>
                        <th>Product</th>
                        @if($priced)
                            <th>HSN</th>
                        @endif
                        <th class="text-end">Quantity</th>
                        <th>UoM</th>
                        @if($priced)
                            <th class="text-end">Rate</th><th class="text-end">Amount</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($dispatchSheet->items as $i => $item)
                    <tr style="{{ $i % 2 === 1 ? 'background-color: #FAFBFC;' : '' }}">
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $item->sku->code }}</code></td>
                        <td class="fw-semibold">{{ $item->sku->name }}</td>
                        @if($priced)
                            <td data-label="HSN">{{ $item->hsn_code ?? '-' }}</td>
                        @endif
                        <td class="text-end fw-bold" data-label="Quantity">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 3) }}</td>
                        <td data-label="Unit">{{ $item->sku->unit_of_measure }}</td>
                        @if($priced)
                            <td class="text-end" data-label="Rate">{{ \App\Support\Money::inr($item->unit_price === null ? null : (float) $item->unit_price) }}</td>
                            <td class="text-end fw-semibold" data-label="Amount">{{ \App\Support\Money::inr($item->amount) }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #F0F3F5;">
                        <td colspan="3" class="fw-bold">Total</td>
                        @if($priced)
                            <td></td>
                        @endif
                        <td class="text-end fw-bold" style="font-size: 0.9rem;" data-label="Quantity">{{ number_format($dispatchSheet->items->sum('quantity'), 0) }}</td>
                        <td></td>
                        @if($priced)
                            <td></td>
                            <td class="text-end fw-bold" data-label="Total Amount">{{ \App\Support\Money::inr($dispatchSheet->items->sum(fn ($item) => $item->amount ?? 0)) }}</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <hr class="section-divider">

        {{-- Action Buttons --}}
        <div class="d-flex gap-3 flex-wrap">
            @if($dispatchSheet->status === 'pending')
                <a href="{{ route('dispatch-sheets.edit', $dispatchSheet) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> Edit Dispatch
                </a>
                <button type="button" class="btn btn-outline-danger btn-lg" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-1"></i> Cancel Dispatch
                </button>
            @endif
            <a href="{{ route('dispatch-sheets.pdf', $dispatchSheet) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-pdf me-1"></i> Download PDF
            </a>
            @if($challanIssue)
                <button type="button" class="btn btn-outline-secondary" disabled title="{{ $challanIssue }}">
                    <i class="bi bi-receipt me-1"></i> Download Challan
                </button>
            @else
                <a href="{{ route('dispatch-sheets.challan', $dispatchSheet) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-receipt me-1"></i> Download Challan
                </a>
            @endif
            @if(in_array($dispatchSheet->status, ['pending', 'dispatched']))
                <button type="button" class="btn btn-primary" id="sharePdfBtn" onclick="sharePdf()">
                    <i class="bi bi-share-fill me-1"></i> Share
                </button>
            @endif
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
@if($dispatchSheet->status === 'pending')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('dispatch-sheets.cancel', $dispatchSheet) }}">
                @csrf @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body confirm-modal-body">
                    <div class="confirm-icon text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h5>Cancel this Dispatch?</h5>
                    <p>
                        You are about to cancel <strong>Dispatch #{{ $dispatchSheet->ds_number }}</strong>.<br>
                        All reserved stock will be released back to the godown.
                    </p>
                    <div class="text-start mt-3">
                        <label for="cancel_reason" class="form-label required">Reason for Cancellation</label>
                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" required placeholder="Please explain why this dispatch is being cancelled..."></textarea>
                        <div class="form-hint">This reason will be saved for records</div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Keep Dispatch
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle me-1"></i> Yes, Cancel Dispatch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>
async function sharePdf() {
    const btn = document.getElementById('sharePdfBtn');
    const originalHtml = btn.innerHTML;
    const pdfUrl = '{{ route("dispatch-sheets.pdf", $dispatchSheet) }}';
    const fileName = '{{ $dispatchSheet->ds_number }}.pdf';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Preparing...';

    try {
        const response = await fetch(pdfUrl);
        if (!response.ok) throw new Error('Could not load PDF');
        const blob = await response.blob();
        const file = new File([blob], fileName, { type: 'application/pdf' });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                files: [file],
                title: 'Dispatch {{ $dispatchSheet->ds_number }}',
                text: 'Dispatch sheet {{ $dispatchSheet->ds_number }} from {{ $dispatchSheet->godown->name }}',
            });
        } else {
            // Fallback: browsers/devices without file-sharing support just get the PDF downloaded
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
        }
    } catch (err) {
        // AbortError fires when the user just cancels the native share sheet — not an error
        if (err.name !== 'AbortError') {
            window.location.href = pdfUrl;
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}
</script>
@endpush
