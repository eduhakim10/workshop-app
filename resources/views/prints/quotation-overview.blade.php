<!DOCTYPE html>


<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Quotation</title>
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
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
<div style="display: flex; align-items: flex-start;">
  <img src="/images/logo.jpeg" alt="logo" style="height: 50px; margin-right: 15px;" />
  <div>
    <h2 style="margin: 0;">P. T. MITRA TOYOTAKA INDONESIA</h2>
    <p style="margin: 0;"><strong>Karawang Branch</strong></p>
    <p style="margin: 0;">Jl. Raya Klari Km. 16, Klapanunggal, Karawang, Jawa Barat, 41371 Telp: (0267) 8400118</p>
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
          <strong>Quotation No:</strong> {{ $service->offer_number }}<br />
          <strong>Date:</strong> 01/06/2025<br />
          <strong>Attn:</strong> Mr. Deki<br />
          <strong>From:</strong> PT MTI
        </td>
      </tr>
    </table>

    <p>Referring to your inquiry for Our Product, herewith we would like to quote the following price :</p>
@php
    $items = $service->items_offer ?? [];
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

<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Category</th>
      <th>Item</th>
      <th>Qty</th>
      <th>Unit</th>
      <th>Price</th>
      <th>Amount</th>
      <th>Remarks</th>
    </tr>
  </thead>
  <tbody>
    @php 
      $total = 0; 
    @endphp

    @foreach ($items as $index => $item)
      @php
        $categoryModel = \App\Models\CategoryItem::find($item['category_item_id']);
        $itemModel = \App\Models\Item::find($item['item_id']);
        $name = $itemModel?->name ?? '-';
        $category = $categoryModel?->name ?? '-';
        $unit = $itemModel?->unit ?? '-';
        $qty = (int) $item['quantity'];
        $price = (float) $item['sales_price'];
        $amount = $qty * $price;
        $total += $amount;
      @endphp
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $category }}</td>
        <td>{{ $name }}</td>
        <td>{{ $qty }}</td>
        <td>{{ $unit }}</td>
        <td>Rp {{ number_format($price, 0, ',', '.') }}</td>
        <td>Rp {{ number_format($amount, 0, ',', '.') }}</td>
         @if ($index === 0)
                <td rowspan="{{ count($items) }}" style="vertical-align: top;">
                    {{ strtoupper($service->location?->name ?? 'REMARK') }}
                </td>
            @endif
      </tr>
    @endforeach

    @php
      $ppn = $total * 0.11;
      $grandTotal = $total + $ppn;
    @endphp

    <!-- Total -->
    <tr>
      <td colspan="5" class="no-border"></td>
      <td class="border-label">Total</td>
      <td class="border-amount">Rp {{ number_format($total, 0, ',', '.') }}</td>
      <td class="no-border"></td>
    </tr>
    <tr>
      <td colspan="5" class="no-border"></td>
      <td class="border-label">PPN 11%</td>
      <td class="border-amount">Rp {{ number_format($ppn, 0, ',', '.') }}</td>
      <td class="no-border"></td>
    </tr>
    <tr>
      <td colspan="5" class="no-border"></td>
      <td class="border-label">Total</td>
      <td class="border-amount">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
      <td class="no-border"></td>
    </tr>
  </tbody>
</table>



   <!-- Terms & Conditions -->
<p><strong>Terms & Conditions:</strong></p>
<div style="margin-top: 5px; line-height: 1.8;">
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Payment</strong></div>
    <div>: 50% Down Payment, 50% after completion</div>
  </div>
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Delivery</strong></div>
    <div>: 14 Days after PO</div>
  </div>
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Validity</strong></div>
    <div>: 30 days</div>
  </div>
  <div style="display: flex;">
    <div style="width: 100px;"><strong>Note</strong></div>
    <div>: Harga di atas belum termasuk PPN 11%</div>
  </div>
</div>


    <!-- Signature -->
    <div class="signature">
      <p>Karawang, 01/06/2025</p>
      <p><strong>P. T. MITRA TOYOTAKA INDONESIA</strong></p>
      <br /><br /><br />
      <p><u>D. LUTFI</u><br />MARKETING</p>
    </div>
  </div>
</body>
</html>
