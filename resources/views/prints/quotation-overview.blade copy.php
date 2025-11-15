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
<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
  <thead>
    <tr>
      <th style="width:5%; text-align:center;">NO</th>
      <th style="width:40%; text-align:center;">ITEM</th>
      <th style="width:10%; text-align:center;">QTY ORDER<br>(UNIT)</th>
      <th style="width:15%; text-align:center;">PRICE / UNIT</th>
      <th style="width:15%; text-align:center;">AMOUNT</th>
      <th style="width:15%; text-align:center;">REMARKS</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="text-align:center;">1</td>
      <td>
        <strong>WING BOX QR4-9440L B 9890 UEW</strong><br><br>
        <strong>REPAIR :</strong><br>
        ~ ROOF<br>
        ~ CENTER ROOF<br>
        ~ LOCK ROOF<br>
        ~ BASE<br>
        ~ AORI<br>
        ~ ELECTRICAL<br>
        ~ BACK DOOR (Plat Bending)<br>
        ~ PAINTING SUPPORT<br>
      </td>
      <td style="text-align:center; vertical-align:top;">1</td>
      <td style="vertical-align:top;">
        <br><br><br>
        Rp 7,150,000<br>
        Rp 3,700,000<br>
        Rp 1,500,000<br>
        Rp 2,000,000<br>
        Rp 2,000,000<br>
        Rp   862,643<br>
        Rp 8,337,357<br>
        Rp 1,450,000
      </td>
      <td style="vertical-align:top;">
          <br><br><br>
        Rp 7,150,000<br>
        Rp 3,700,000<br>
        Rp 1,500,000<br>
        Rp 2,000,000<br>
        Rp 2,000,000<br>
        Rp   862,643<br>
        Rp 8,337,357<br>
        Rp 1,450,000
      </td>
      <td></td>
    </tr>
    <!-- Footer subtotal -->
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Sub Total</strong></td>
      <td>Rp 27,000,000</td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>PPN 11%</strong></td>
      <td>Rp 2,970,000</td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Total</strong></td>
      <td><strong>Rp 29,970,000</strong></td>
      <td></td>
    </tr>
  </tbody>
</table>

<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
  <thead>
    <tr>
      <th style="width:5%; text-align:center;">NO</th>
      <th style="width:40%; text-align:center;">ITEM</th>
      <th style="width:10%; text-align:center;">QTY ORDER<br>(UNIT)</th>
      <th style="width:15%; text-align:center;">PRICE / UNIT</th>
      <th style="width:15%; text-align:center;">AMOUNT</th>
      <th style="width:15%; text-align:center;">REMARKS</th>
    </tr>
  </thead>
  <tbody>
    <!-- Row utama -->
    <tr>
      <td style="text-align:center; vertical-align:top;">1</td>
      <td colspan="4">
        <strong>WING BOX QR4-9440L B 9890 UEW</strong><br>
        <strong>REPAIR :</strong>
      </td>
      <td></td>
    </tr>

    <!-- Subrow breakdown -->
    <tr>
      <td></td>
      <td>~ ROOF</td>
      <td></td>
      <td>Rp 7,150,000</td>
      <td>Rp 7,150,000</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ CENTER ROOF</td>
      <td></td>
      <td>Rp 3,700,000</td>
      <td>Rp 3,700,000</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ LOCK ROOF</td>
      <td></td>
      <td>Rp 1,500,000</td>
      <td>Rp 1,500,000</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ BASE</td>
      <td></td>
      <td>Rp 2,000,000</td>
      <td>Rp 2,000,000</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ AORI</td>
      <td></td>
      <td>Rp 2,000,000</td>
      <td>Rp 2,000,000</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ ELECTRICAL</td>
      <td></td>
      <td>Rp 862,643</td>
      <td>Rp 862,643</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ BACK DOOR (Plat Bending)</td>
      <td></td>
      <td>Rp 8,337,357</td>
      <td>Rp 8,337,357</td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td>~ PAINTING SUPPORT</td>
      <td></td>
      <td>Rp 1,450,000</td>
      <td>Rp 1,450,000</td>
      <td></td>
    </tr>

    <!-- Footer subtotal -->
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Sub Total</strong></td>
      <td>Rp 27,000,000</td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>PPN 11%</strong></td>
      <td>Rp 2,970,000</td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Total</strong></td>
      <td><strong>Rp 29,970,000</strong></td>
      <td></td>
    </tr>
  </tbody>
</table>

<table>
  <thead>
    <tr>
      <th>No</th>
      <!-- <th>Category</th> -->
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
        <td class="no-border">{{ $category }}</td>
        <!-- <td>{{ $name }}</td> -->
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
