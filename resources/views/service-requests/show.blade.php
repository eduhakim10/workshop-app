<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Service Request {{ $serviceRequest->sr_number }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }
    .container {
      width: 700px;
      margin: auto;
    }
    h2, h3 {
      margin: 5px 0;
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
    .text-left {
      text-align: left;
    }
    .no-border {
      border: none !important;
    }
    .photo-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
    .photo-grid img {
      border: 1px solid #ccc;
      padding: 4px;
      width: 200px;
      height: auto;
    }
    @media print {
      .btn, .no-print {
        display: none !important;
      }
      @page {
        margin: 15mm;
      }
      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>
  <button onclick="window.print()" class="btn no-print">🖨 Cetak</button>

  <div class="container">
    <h2>Service Request Detail</h2>
    <hr>

    <h3>General Info</h3>
    <table>
      <tr>
        <td class="text-left"><strong>SR Number:</strong></td>
        <td class="text-left">{{ $serviceRequest->sr_number }}</td>
      </tr>
      <tr>
        <td class="text-left"><strong>Customer:</strong></td>
        <td class="text-left">{{ $serviceRequest->customer->name ?? '-' }}</td>
      </tr>
      <tr>
        <td class="text-left"><strong>Vehicle:</strong></td>
        <td class="text-left">{{ $serviceRequest->vehicle->license_plate.' / '.$serviceRequest->vehicle->type.' / '.$serviceRequest->vehicle->brand  ?? '-' }}</td>
      </tr>
      <tr>
        <td class="text-left"><strong>Date:</strong></td>
        <td class="text-left">{{ \Carbon\Carbon::parse($serviceRequest->created_at)->translatedFormat('d F Y') }}</td>
      </tr>
    </table>

    <h3>Deskripsi Kerusakan</h3>
<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:10px;">
  <!-- Kolom Damages -->
  <div style="width:65%;">
    <table style="width:100%; border-collapse:collapse; border:none;">
      <thead>
        <tr>
          <th class="text-left" style="width:50%; border:none;">Deskripsi Kerusakan</th>
          <!-- <th class="text-left" style="width:50%; border:none;">Right Side</th> -->
        </tr>
      </thead>
      <tbody>
        @php
          $damages = $serviceRequest->damages->pluck('name')->take(10)->toArray();
          $damages = array_pad($damages, 10, '................');
        @endphp

        @for ($i = 0; $i < 5; $i++)
          <tr style="border:none;">
            <td class="text-left" style="border:none; padding:4px 0;">
              {{ $i+1 }}. {{ $damages[$i] }}
            </td>
            <td class="text-left" style="border:none; padding:4px 0;">
              {{ $i+6 }}. {{ $damages[$i+5] }}
            </td>
          </tr>
        @endfor
      </tbody>
    </table>
  </div>

  <!-- Kolom Dibuat Oleh -->
  <div style="width:30%; text-align:center; font-size:12px;">
    <p><strong>Dibuat Oleh</strong></p>
    @if($serviceRequest->createdBy?->signature)
      <img src="{{ url(Storage::url($serviceRequest->createdBy->signature)) }}" 
           alt="Tanda Tangan" style="height:60px; margin-bottom:5px;">
    @else
      <div style="height:60px; margin-bottom:5px;">(TTD)</div>
    @endif
    <div>
      <u>{{ $serviceRequest->createdBy?->name ?? '................' }}</u><br>
      {{ $serviceRequest->createdBy?->position ?? '' }}
    </div>
    <p style="margin-top:10px;">
      {{ \Carbon\Carbon::parse($serviceRequest->created_at)->translatedFormat('d F Y') }}
    </p>
  </div>
</div>




    <h3>Photos</h3>
    <div class="photo-grid">
      @forelse ($serviceRequest->photos as $photo)
      @php
      $photoPath = $photo->file_path ?? '';

      if (empty($photoPath)) {
          // fallback image lokal kalau path kosong
          $photoUrl = asset('images/no-image.png');
      } elseif (Str::startsWith($photoPath, ['http://', 'https://'])) {
          // sudah full URL
          $photoUrl = $photoPath;
      } else {
          // pastikan tidak ada prefix 'storage/' ganda
          $normalized = preg_replace('#^storage/#', '', $photoPath);
          // pakai Storage::url lalu url() biar dapat absolute URL sesuai APP_URL
          $photoUrl = url(Storage::url($normalized));
      }
    @endphp
        <img src="{{ $photoUrl }}" alt="Photo">
      @empty
        <p>No photos available</p>
      @endforelse
    </div>

  </div>
</body>
</html>
