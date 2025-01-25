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
        <h2>Rekap Laporan Absensi Pegawai RSUD Drs. H. Amri Tambunan</h2>
        <p>Periode: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 8%;">Nama</th>
                <th rowspan="2" style="width: 12%;">Divisi</th>
                <th colspan="2" class="subheader">Absen Masuk</th>
                <th colspan="2" class="subheader">Absen Pulang</th>
                <th colspan="4" class="subheader">TL</th>
                <th colspan="4" class="subheader">PSW</th>
                <th rowspan="2" style="width: 8%;">Jumlah Absensi</th>
            </tr>
            <tr>
                <th>Tepat Waktu</th>
                <th>Terlambat</th>
                <th>Lebih Cepat Pulang</th>
                <th>Tepat Waktu</th>
                <th>TL 1</th>
                <th>TL 2</th>
                <th>TL 3</th>
                <th>TL 4</th>
                <th>PSW 1</th>
                <th>PSW 2</th>
                <th>PSW 3</th>
                <th>PSW 4</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensi as $index => $record)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $record->nama }}</td>
                <td>{{ $record->divisi }}</td>
                <!-- Absen Masuk -->
                <td>{{ $record->tepat_waktu_masuk }}</td>
                <td>{{ $record->terlambat_masuk }}</td>
                <!-- Absen Pulang -->
                <td>{{ $record->lebih_cepat_pulang }}</td>
                <td>{{ $record->tepat_waktu_pulang }}</td>
                <!-- TL -->
                <td>{{ $record->tl_1 }}</td>
                <td>{{ $record->tl_2 }}</td>
                <td>{{ $record->tl_3 }}</td>
                <td>{{ $record->tl_4 }}</td>
                <!-- PSW -->
                <td>{{ $record->psw_1 }}</td>
                <td>{{ $record->psw_2 }}</td>
                <td>{{ $record->psw_3 }}</td>
                <td>{{ $record->psw_4 }}</td>
                <!-- Jumlah Absensi -->
                <td>{{ $record->total_absensi }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem pada {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>