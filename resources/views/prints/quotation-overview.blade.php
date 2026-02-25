<!DOCTYPE html>


<html lang="en">
<head>
  <meta charset="UTF-8" />
 <title>Quotation {{ $service->offer_number }}</title>
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
    margin: 20mm; /* atur margin */
  }
  
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Sembunyiin header/footer bawaan browser */
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
  <!-- <img src="/images/certificate.png" alt="logo kanan" style="height: 50px; weight:100px; margin-left: 15px;" /> -->
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
          <div style="display:flex; align-items:flex-start;">
            <div style="width:120px;">
              <strong>Quotation No</strong><br />
              <strong>Date</strong><br />
              <strong>Attn</strong><br />
              <strong>From</strong><br />
              <strong>SR No</strong><br />
              <strong>License Plate</strong>
            </div>
            <div>
              :<br />
              :<br />
              :<br />
              :<br />
              :<br />
              :
            </div>
            <div style="flex:1; padding-left:2px;">
              {{ $service->offer_number }}<br />
              {{ \Carbon\Carbon::parse($service->created_at)->format('d/m/Y') }}<br />
              {{ $service->attn_quotation ?? '-' }}<br />
              PT Mitra Toyotaka Indonesia<br />
              {{ $service->serviceRequest?->sr_number ?? '-' }}<br />
              {{ $service->vehicle?->license_plate ?? '-' }}
            </div>
          </div>
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
    @php 
        $no = 1;
        $subtotal = 0;
    @endphp

    @foreach($service->items_offer as $group)
        @php 
            $serviceGroup = \App\Models\ServiceGroup::find($group['service_group_id']);
            $groupName = $serviceGroup?->name ?? '-';
            $groupQty = $group['qty'];
            $remarks = $service->notes;
        @endphp
        <tr>
            <td style="text-align:center;">{{ $no++ }}</td>
            <td style="text-align: left;">
                <strong>{{ $groupName }}</strong><br><br>
                <strong>REPAIR :</strong><br>
                @foreach($group['items'] as $item)
                    @php
                        $category = \App\Models\CategoryItem::find($item['category_item_id']);
                    @endphp
                    ~ {{ $category?->name ?? '-' }} <br>
                @endforeach
            </td>
            <td style="text-align:center; vertical-align:top;">{{ $groupQty }}</td>
            <td style="vertical-align:top;">
                <br><br><br>
                @foreach($group['items'] as $item)
                <div style="display:flex; justify-content:space-between;">
                  <span style="text-align:left;">Rp</span>
                  <span style="text-align:right;">{{ number_format($item['sales_price'], 0, ',', '.') }}</span>
                </div>
                @endforeach
            </td>
            <td style="vertical-align:top;">
                <br><br><br>
                @foreach($group['items'] as $item)
                    @php 
                        $amount = $item['sales_price'] * $item['quantity'];
                        $subtotal += $amount;
                    @endphp
                        <div style="display:flex; justify-content:space-between;">
                          <span style="text-align:left;">Rp</span>
                          <span style="text-align:right;">{{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                  
                @endforeach
            </td>
            <td>{{ $remarks }}</td>
        </tr>
    @endforeach

    <!-- Footer subtotal -->
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Sub Total</strong></td>
      <td>
       
              <div style="display:flex; justify-content:space-between;">
                <span style="text-align:left;">Rp</span>
                <span style="text-align:right;">{{ number_format($subtotal, 0, ',', '.') }}</span>
              </div>
      
      </td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>PPN 11%</strong></td>
      <td>
            <div style="display:flex; justify-content:space-between;">
                <span style="text-align:left;">Rp</span>
                <span style="text-align:right;">{{ number_format($subtotal * 0.11, 0, ',', '.') }}</span>
              </div>

      </td>
      <td></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;"><strong>Total</strong></td>
      <td>
              <div style="display:flex; justify-content:space-between;">
                <span style="text-align:left;">Rp</span>
                <span style="text-align:right;"><strong> {{ number_format($subtotal * 1.11, 0, ',', '.') }}</strong></span>
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
  <!-- <div style="display: flex;">
    <div style="width: 100px;"><strong>Note</strong></div>
    <div>: Harga di atas belum termasuk PPN 11%</div>
  </div> -->
</div>
<p>Balaraja, {{ \Carbon\Carbon::parse($service->created_at_offer)->translatedFormat('d F Y') }}</p>

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


