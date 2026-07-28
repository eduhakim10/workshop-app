<!DOCTYPE html>


<html lang="en">
<head>
  <meta charset="UTF-8" />
 <title>{{ $service->offer_number }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }
    .container {
      width: 700px;
      margin: auto;
    }
    .header,
    .footer {
      text-align: center;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #000;
      padding: 4px;
      text-align: center;
    }
    .no-border {
      border: none;
    }
    .bold {
      font-weight: bold;
    }
    .text-left {
      text-align: left;
    }
    .signature {
      margin-top: 40px;
      text-align: right;
    }
     .signature-left {
        margin-top: 40px;
        text-align: left;
    }
    @media print {
   .btn, .no-print {
      display: none !important;
   }
     @page {
    margin: 20mm;
  }

  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  @page {
    size: auto;
    margin: 0;
  }
}

  </style>
</head>
<body>
  <button onclick="window.print()" class="btn btn-primary">
   Cetak
</button>

  <div class="container">
    <!-- Header -->
<div style="display: flex; align-items: flex-start;">
  <img src="/images/logo.jpeg" alt="logo" style="height: 50px; margin-right: 15px;" />
  <div>
    <h2 style="margin: 0;">P. T. MITRA TOYOTAKA INDONESIA</h2>
    <p style="margin: 0;"><strong>Karawang Branch</strong></p>
    <p style="margin: 0;">Jl. Raya Klari Km. 10, Klapanunggal, Karawang, Jawa Barat, 41371 Telp: (0267) 8400118</p>
  </div>
</div>
<hr>

    <!-- Quotation Info -->
    <table class="no-border">
      <tr>
        <td class="no-border text-left" style="width: 50%;">
          <strong>To:</strong><br />
         {{ $service->customer->name }}<br />
         {{ $service->customer->address }}
        </td>
        <td class="no-border text-left">
          <table style="border:none; border-collapse:collapse; text-align:left;">
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>Quotation No</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">{{ $service->offer_number ?? '-' }}</td>
            </tr>
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>Date</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">{{ \Carbon\Carbon::parse($service->created_at)->format('d/m/Y') }}</td>
            </tr>
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>Attn</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">{{ $service->attn_quotation ?? '-' }}</td>
            </tr>
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>From</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">PT Mitra Toyotaka Indonesia</td>
            </tr>
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>SR No</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">{{ $service->serviceRequest?->sr_number ?? '-' }}</td>
            </tr>
            <tr>
              <td style="border:none; padding:1px 0; text-align:left; white-space:nowrap;"><strong>License Plate</strong></td>
              <td style="border:none; padding:1px 4px; text-align:left; white-space:nowrap;">:</td>
              <td style="border:none; padding:1px 0; text-align:left;">{{ $service->vehicle?->license_plate ?? '-' }}</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <p>Referring to your inquiry for Our Product, herewith we would like to quote the following price :</p>
@php
    use App\Helpers\QuotationPricing;

    $items = $service->items_offer ?? [];
    $totals = QuotationPricing::calcFromGroups(
        $items,
        $service->ppn_type,
        $service->ppn_percent
    );
    $ppnPercentLabel = rtrim(rtrim(number_format((float) ($service->ppn_percent ?? 0), 2, ',', '.'), '0'), ',');
    if ($ppnPercentLabel === '') { $ppnPercentLabel = '0'; }
    $hasDiscount = collect($items)->flatMap(fn($g) => $g['items'] ?? [])->contains(fn($i) => ((float) ($i['discount_percent'] ?? 0)) > 0);
    $footerColspan = $hasDiscount ? 6 : 4;
@endphp


    <!-- Items Table -->
 <style>
  .no-border {
    border: none !important;
  }

  .border-label {
    border: 1px solid black;
    font-weight: bold;
    text-align: left;
    padding-left: 5px;
  }

  .border-amount {
    border: 1px solid black;
    font-weight: bold;
    text-align: right;
    padding-right: 5px;
  }
</style>
<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
  <thead>
    <tr>
      <th style="width:5%; text-align:center;">NO</th>
      <th style="width:{{ $hasDiscount ? '32' : '43' }}%; text-align:center;">ITEM</th>
      <th style="width:8%; text-align:center;">QTY ORDER<br>(UNIT)</th>
      <th style="width:13%; text-align:center; border-right: 1px solid #000;">PRICE / UNIT</th>
      @if($hasDiscount)
      <th style="width:8%; text-align:center; border-right: 1px solid #000;">DISC (%)</th>
      <th style="width:11%; text-align:center; border-right: 1px solid #000;">DISC AMOUNT</th>
      @endif
      <th style="width:13%; text-align:center;">AMOUNT</th>
      <th style="width:10%; text-align:center;">REMARKS</th>
    </tr>
  </thead>
  <tbody>
    @php
        $no = 1;
    @endphp

    @foreach($items as $group)
        @php
            $serviceGroup = \App\Models\ServiceGroup::find($group['service_group_id'] ?? null);
            $groupName = $serviceGroup?->name ?? '-';
            $groupQty = $group['qty'] ?? 1;
            $remarks = $service->notes;
            $groupItems = $group['items'] ?? [];
            $itemCount = count($groupItems);
        @endphp

        {{-- Group header row: NO & QTY span all item rows --}}
        <tr>
            <td style="text-align:center; vertical-align:middle;" rowspan="{{ $itemCount + 1 }}">{{ $no++ }}</td>
            <td style="text-align:left; font-weight:bold; border-bottom:hidden;">{{ $groupName }}<br><strong>REPAIR :</strong></td>
            <td style="text-align:center; vertical-align:top;" rowspan="{{ $itemCount + 1 }}">{{ $groupQty }}</td>
            <td style="border-bottom:hidden;"></td>
            @if($hasDiscount)
            <td style="border-bottom:hidden;"></td>
            <td style="border-bottom:hidden;"></td>
            @endif
            <td style="border-bottom:hidden;"></td>
            <td style="border-bottom:hidden; vertical-align:top;">{{ $remarks }}</td>
        </tr>

        {{-- One row per item --}}
        @foreach($groupItems as $idx => $item)
            @php
                $category = \App\Models\CategoryItem::find($item['category_item_id'] ?? null);
                $line = QuotationPricing::calcLine($item);
                $isLast = ($idx === $itemCount - 1);
                $nb = $isLast ? '' : 'border-bottom:hidden;';
            @endphp
            <tr>
                <td style="text-align:left; {{ $nb }}">~ {{ $category?->name ?? '-' }}</td>
                {{-- QTY is rowspanned above, skip --}}
                <td style="border: 1px solid #000; {{ $isLast ? '' : 'border-bottom:hidden;' }}">
                    <div style="display:flex; justify-content:space-between;">
                        <span>Rp</span>
                        <span>{{ number_format((float) ($item['sales_price'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </td>
                @if($hasDiscount)
                <td style="text-align:center; border: 1px solid #000; {{ $isLast ? '' : 'border-bottom:hidden;' }}">{{ rtrim(rtrim(number_format((float) ($item['discount_percent'] ?? 0), 2, ',', '.'), '0'), ',') }}%</td>
                <td style="border: 1px solid #000; {{ $isLast ? '' : 'border-bottom:hidden;' }}">
                    <div style="display:flex; justify-content:space-between;">
                        <span>Rp</span>
                        <span>{{ number_format($line['discount'], 0, ',', '.') }}</span>
                    </div>
                </td>
                @endif
                <td style="{{ $nb }}">
                    <div style="display:flex; justify-content:space-between;">
                        <span>Rp</span>
                        <span>{{ number_format($line['subtotal'], 0, ',', '.') }}</span>
                    </div>
                </td>
                <td style="{{ $nb }}"></td>
            </tr>
        @endforeach
    @endforeach

    <!-- Footer summary -->
    <tr>
      <td colspan="{{ $footerColspan }}" style="text-align:right;"><strong>Sub Total (DPP)</strong></td>
      <td>
        <div style="display:flex; justify-content:space-between;">
          <span style="text-align:left;">Rp</span>
          <span style="text-align:right;">{{ number_format($totals['subtotal'], 0, ',', '.') }}</span>
        </div>
      </td>
      <td></td>
    </tr>
    <tr>
      <td colspan="{{ $footerColspan }}" style="text-align:right;">
        <strong>
          PPN {{ $ppnPercentLabel }}%
          <br><small>({{ QuotationPricing::ppnTypeLabel($service->ppn_type) }})</small>
        </strong>
      </td>
      <td>
        <div style="display:flex; justify-content:space-between;">
          <span style="text-align:left;">Rp</span>
          <span style="text-align:right;">{{ number_format($totals['ppn'], 0, ',', '.') }}</span>
        </div>
      </td>
      <td></td>
    </tr>
    <tr>
      <td colspan="{{ $footerColspan }}" style="text-align:right;"><strong>Total</strong></td>
      <td>
        <div style="display:flex; justify-content:space-between;">
          <span style="text-align:left;">Rp</span>
          <span style="text-align:right;"><strong>{{ number_format($totals['total'], 0, ',', '.') }}</strong></span>
        </div>
      </td>
      <td></td>
    </tr>
  </tbody>
</table>




   <!-- Terms & Conditions -->
<p><strong>Terms & Conditions:</strong></p>
<div style="margin-top: 5px; line-height: 1.8;">
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Payment</strong></div>
    <div>: {{ $service->payment_terms }}</div>
  </div>
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Delivery</strong></div>
    <div>: {{$service->delivery_terms }}</div>
  </div>
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Validity</strong></div>
    <div>: {{ $service->validity_terms }}</div>
  </div>
</div>
<p>Karawang, {{ \Carbon\Carbon::parse($service->created_at_offer)->translatedFormat('d F Y') }}</p>

<div style="display:flex; justify-content:space-between; margin-top:10px; text-align:center;">
  <div>
    Approved by
    <br><br><br><br>
    <u>G. HATIBIE</u>
  </div>

  <div>
    <strong>P. T. MITRA TOYOTAKA INDONESIA</strong>
    <br><br>
    @if($service->preparedBy?->signature)
    <img src="{{ Storage::url($service->preparedBy?->signature) }}" alt="Tanda Tangan" style="height:80px;">
@endif
    <br><br>
    <u>{{ $service->preparedBy?->name }}</u><br>
  {{ $service->preparedBy?->position }}
  </div>
</div>

DOC-MKT-03-MTI
<div style="border: 1px solid #000; padding: 5px; width: 700px;">
  Customer approval :
  <br><br><br>

</div>

</body>
</html>
