<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .subheader {
            background-color: #e9e9e9;
            font-weight: bold;
            text-align: center;
        }
        .time-cell {
            white-space: nowrap;
        }
        .date-cell {
            white-space: nowrap;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Absensi Karyawan</h2>
        <p>Periode: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 8%;">Tanggal</th>
                <th rowspan="2" style="width: 12%;">Nama</th>
                <th rowspan="2" style="width: 5%;">shift</th>
                <th colspan="5" class="subheader">Absen Masuk</th>
                <th colspan="5" class="subheader">Absen Pulang</th>
            </tr>
            <tr>
                <th>Waktu</th>
                <th>Reff</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Waktu</th>
                <th>Reff</th>
                <th>Selisih</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensi as $index => $record)
            @php
                // Mengambil data absen pulang pertama jika ada
                $absenPulang = $record->absenPulang->first();
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td class="date-cell">{{ \Carbon\Carbon::parse($record->waktu_masuk)->format('d/m/Y') }}</td>
                <td>{{ $record->user->name ?? '-' }}</td>
                <td>{{ $record->shift->nama_shift ?? '-' }}</td>
                <!-- Absen Masuk -->
                <td class="time-cell">{{ \Carbon\Carbon::parse($record->waktu_masuk)->format('H:i:s') }}</td>
                <td>{{ $record->waktuKerja->jam_mulai ?? '-' }}</td>
                <td>{{ $record->selish ?? '-' }}</td>
                <td>{{ $record->tpp_in ?? '-' }}</td>
                <td>{{ $record->keterangan ?? '-' }}</td>
                <!-- Absen Pulang -->
                <td class="time-cell">
                    {{ $absenPulang ? \Carbon\Carbon::parse($absenPulang->waktu_pulang)->format('H:i:s') : '-' }}
                </td>
                <td>{{ $record->waktuKerja->jam_selesai ?? '-' }}</td>
                <td>{{ $absenPulang->selish ?? '-' }}</td>
                <td>{{ $absenPulang->tpp_out ?? '-' }}</td>
                <td>{{ $absenPulang->keterangan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem pada {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>