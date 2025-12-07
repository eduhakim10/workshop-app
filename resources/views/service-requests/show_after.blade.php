<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Service Request {{ $serviceRequest->sr_number }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .container { width: 700px; margin: auto; }
    h2, h3 { margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: left; }
    .photo-grid {
      display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;
    }
    .photo-grid img {
      border: 1px solid #ccc; padding: 4px; width: 200px; height: auto;
    }
    @media print {
      .btn, .no-print { display: none !important; }
      @page { margin: 15mm; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>

<body>
  <button onclick="window.print()" class="btn no-print">🖨 Cetak</button>

  <div class="container">
    <h2>Service Request (AFTER)</h2>
    <hr>

    <h3>General Info</h3>
    <table>
      <tr>
        <td><strong>SR Number:</strong></td>
        <td>{{ $serviceRequest->sr_number }}</td>
      </tr>
      <tr>
        <td><strong>Customer:</strong></td>
        <td>{{ $serviceRequest->customer->name ?? '-' }}</td>
      </tr>
      <tr>
        <td><strong>Vehicle:</strong></td>
        <td>{{ $serviceRequest->vehicle->license_plate.' / '.$serviceRequest->vehicle->type.' / '.$serviceRequest->vehicle->brand }}</td>
      </tr>
      <tr>
        <td><strong>Date:</strong></td>
        <td>{{ \Carbon\Carbon::parse($serviceRequest->created_at)->translatedFormat('d F Y') }}</td>
      </tr>
    </table>

    <h3>Deskripsi Kerusakan</h3>

    <!-- Flex container hanya untuk tabel kerusakan + tanda tangan -->
    <div style="display:flex; flex-direction:row; justify-content:space-between; margin-top:10px; width:100%;">

      <!-- Kolom Kerusakan -->
      <div style="width:65%;">
        <table style="border:none;">
          @php
            $damages = $serviceRequest->damages->pluck('name')->take(10)->toArray();
            $damages = array_pad($damages, 10, '................');
          @endphp
          @for ($i = 0; $i < 5; $i++)
          <tr style="border:none;">
            <td style="border:none; padding:4px 0;">{{ $i+1 }}. {{ $damages[$i] }}</td>
            <td style="border:none; padding:4px 0;">{{ $i+6 }}. {{ $damages[$i+5] }}</td>
          </tr>
          @endfor
        </table>
      </div>

      <!-- Kolom Tanda Tangan -->
      <div style="width:30%; text-align:center; font-size:12px;">

        <p><strong>Dibuat Oleh</strong></p>

        @if($serviceRequest->createdBy?->signature)
          <img src="{{ url(Storage::url($serviceRequest->createdBy->signature)) }}" style="height:60px;">
        @else
          <div style="height:60px;">(TTD)</div>
        @endif

        <u>{{ $serviceRequest->createdBy?->name ?? '................' }}</u><br>
        {{ $serviceRequest->createdBy?->position ?? '' }}

        <p style="margin:10px 0;">{{ \Carbon\Carbon::parse($serviceRequest->created_at)->translatedFormat('d F Y') }}</p>

        <hr style="border-top:1px dashed #aaa; margin:12px 0;">

        <p><strong>Disetujui Oleh Customer</strong></p>

        @if($serviceRequest->customer_signature)
          <img src="{{ url(Storage::url($serviceRequest->customer_signature)) }}" style="height:60px;">
        @else
          <div style="height:60px;"></div>
        @endif

        <u>{{ $serviceRequest->customer->name ?? '................' }}</u><br>

        <p style="margin-top:10px;">
          {{ \Carbon\Carbon::parse($serviceRequest->created_at)->translatedFormat('d F Y') }}
        </p>

      </div>
    </div>

    <!-- Photos always 100% width -->
    <h3>Photos</h3>
    <div class="photo-grid">
      @foreach ($serviceRequest->photos as $photo)
        @php
          $path = $photo->file_path;
          if (!$path) {
              $photoUrl = asset('images/no-image.png');
          } elseif (Str::startsWith($path, ['http://','https://'])) {
              $photoUrl = $path;
          } else {
              $photoUrl = url(Storage::url($path));
          }
        @endphp
        <img src="{{ $photoUrl }}">
      @endforeach
    </div>

  </div>
</body>
</html>
