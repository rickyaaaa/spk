<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil SPK Siswa Berprestasi - {{ $period }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #047857;
            padding-bottom: 12px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            color: #065f46;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 9pt;
            color: #6b7280;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 9pt;
        }
        .meta-label {
            font-weight: bold;
            width: 120px;
            color: #4b5563;
        }
        .title-section {
            margin-top: 12px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 11pt;
            color: #065f46;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
            font-size: 8.5pt;
        }
        .data-table th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.5px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 9999px;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-gray {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }
        .signature-section {
            margin-top: 35px;
            width: 100%;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL SELEKSI SISWA BERPRESTASI</h1>
        <p>Sanggar Kegiatan Belajar 26 (SKB 26)</p>
        <p>Periode Evaluasi: {{ $period }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Metode Seleksi</td>
            <td>: Analytical Hierarchy Process (AHP) & Simple Additive Weighting (SAW)</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal Cetak</td>
            <td>: {{ date('d F Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status Kelulusan</td>
            <td>: Peringkat 1-3 Direkomendasikan (Berprestasi), Peringkat >3 Cadangan</td>
        </tr>
    </table>

    <div class="title-section">
        Bobot Kriteria AHP
    </div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($criteria as $criterion)
                    <th class="text-center">{{ $criterion->name }} ({{ $criterion->code }})</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($criteria as $criterion)
                    <td class="text-center" style="font-weight: bold; font-size: 10pt; color: #047857;">
                        {{ number_format($criterion->weight * 100, 1) }}%
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="title-section" style="margin-top: 20px;">
        Hasil Perankingan Siswa
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 45px;">Rank</th>
                <th style="width: 80px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 100px;">Kelas</th>
                @foreach ($criteria as $criterion)
                    <th class="text-center" style="width: 60px;">{{ $criterion->code }}</th>
                @endforeach
                <th class="text-right" style="width: 75px;">Skor Akhir</th>
                <th class="text-center" style="width: 110px;">Rekomendasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td class="text-center" style="font-weight: bold;">{{ $student['rank'] }}</td>
                    <td>{{ $student['nis'] }}</td>
                    <td style="font-weight: bold;">{{ $student['name'] }}</td>
                    <td>{{ $student['class_name'] }}</td>
                    @foreach ($criteria as $criterion)
                        <td class="text-center">{{ number_format($student[$criterion->code.'_raw'] ?? 0, 0) }}</td>
                    @endforeach
                    <td class="text-right" style="font-weight: bold; color: #047857;">
                        {{ number_format($student['score'], 2) }}
                    </td>
                    <td class="text-center">
                        @if ($student['rank'] <= 3)
                            <span class="badge badge-success">Berprestasi</span>
                        @else
                            <span class="badge badge-gray">Cadangan</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signature-section" style="width: 100%; border: none;">
        <tr>
            <td style="width: 60%; border: none;"></td>
            <td style="text-align: center; border: none; font-size: 9.5pt;">
                <p>Jakarta, {{ date('d F Y') }}</p>
                <p style="font-weight: bold; margin-top: 4px;">Kepala Sanggar Kegiatan Belajar 26</p>
                <div class="signature-space"></div>
                <p style="font-weight: bold; text-decoration: underline;">( ___________________________ )</p>
                <p style="color: #6b7280; font-size: 8.5pt; margin-top: 2px;">NIP. ........................................</p>
            </td>
        </tr>
    </table>
</body>
</html>
