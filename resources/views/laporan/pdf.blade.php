<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kemahasiswaan FT UNSUR</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 0; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 18px 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; color: #0f172a; }
        .muted { color: #64748b; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
        .kpi { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kpi td { width: 25%; border: 1px solid #e2e8f0; padding: 8px; text-align: center; }
        .kpi .angka { font-size: 16px; font-weight: bold; color: #1e3a8a; }
        .kpi .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #e2e8f0; padding: 5px 7px; text-align: left; }
        table.data th { background: #f1f5f9; font-size: 10px; }
        table.data td.num, table.data th.num { text-align: right; }
        .foot { margin-top: 18px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
    </style>
</head>
<body>
    @php
        $labelStatus = ['aktif' => 'Aktif', 'cuti' => 'Cuti', 'non_aktif' => 'Non-aktif', 'lulus' => 'Lulus', 'do' => 'DO'];
    @endphp

    <div class="header">
        <h1>Laporan Kemahasiswaan</h1>
        <div class="muted">Fakultas Teknik, Universitas Suryakancana (SIMAFTUNSUR)</div>
    </div>

    {{-- Ringkasan --}}
    <table class="kpi">
        <tr>
            <td><div class="angka">{{ number_format($ringkasan['total_mahasiswa']) }}</div><div class="label">Total Mahasiswa</div></td>
            <td><div class="angka">{{ number_format($ringkasan['mahasiswa_aktif']) }}</div><div class="label">Mahasiswa Aktif</div></td>
            <td><div class="angka">{{ $ringkasan['rata_ipk'] !== null ? number_format($ringkasan['rata_ipk'], 2) : '-' }}</div><div class="label">Rata-rata IPK</div></td>
            <td><div class="angka">{{ number_format($ringkasan['total_prestasi']) }}</div><div class="label">Total Prestasi</div></td>
        </tr>
    </table>

    {{-- Rekap per prodi --}}
    <h2>Rekap per Program Studi</h2>
    <table class="data">
        <thead>
            <tr><th>Kode</th><th>Program Studi</th><th class="num">Jumlah</th><th class="num">Aktif</th><th class="num">Rata-rata IPK</th></tr>
        </thead>
        <tbody>
            @foreach ($prodi as $r)
                <tr>
                    <td>{{ $r['kode'] }}</td>
                    <td>{{ $r['nama'] }}</td>
                    <td class="num">{{ number_format($r['jumlah']) }}</td>
                    <td class="num">{{ number_format($r['aktif']) }}</td>
                    <td class="num">{{ $r['rata_ipk'] !== null ? number_format($r['rata_ipk'], 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Status mahasiswa --}}
    <h2>Sebaran Status Mahasiswa</h2>
    <table class="data">
        <thead><tr><th>Status</th><th class="num">Jumlah</th></tr></thead>
        <tbody>
            @foreach ($status as $r)
                <tr><td>{{ $labelStatus[$r['status']] ?? ucfirst($r['status']) }}</td><td class="num">{{ number_format($r['jumlah']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    {{-- Tracer --}}
    <h2>Status Pekerjaan Alumni (Tracer Study)</h2>
    <table class="data">
        <thead><tr><th>Status</th><th class="num">Jumlah</th></tr></thead>
        <tbody>
            @foreach ($tracer as $r)
                <tr><td>{{ $r['label'] }}</td><td class="num">{{ number_format($r['jumlah']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    {{-- Prestasi per tingkat --}}
    <h2>Prestasi per Tingkat</h2>
    <table class="data">
        <thead><tr><th>Tingkat</th><th class="num">Jumlah</th></tr></thead>
        <tbody>
            @foreach ($tingkat as $r)
                <tr><td>{{ $r['label'] }}</td><td class="num">{{ number_format($r['jumlah']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">
        Dicetak {{ $dicetak }}@if ($oleh) · oleh {{ $oleh }}@endif · Data simulasi/operasional SIMAFTUNSUR.
    </div>
</body>
</html>
