{{--
    Radar chart (SVG server-side) membandingkan profil centroid antar-klaster
    pada beberapa fitur. Tanpa dependensi JS. Nilai tiap fitur dinormalisasi
    min-max antar-klaster agar bentuk radar sebanding.

    Properti:
        - profil : array profil_klaster (tiap item: cluster, label_deskriptif, centroid[])
        - palet  : array warna heksadesimal per indeks klaster
--}}
@props([
    'profil' => [],
    'palet'  => ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'],
])

@php
    $labelFitur = [
        'ipk_rata_rata'  => 'IPK Rata',
        'ipk_terakhir'   => 'IPK Akhir',
        'tren'           => 'Tren',
        'konsistensi'    => 'Konsistensi',
        'semester_aktif' => 'Semester',
    ];

    $fitur = ! empty($profil) ? array_keys($profil[0]['centroid'] ?? []) : [];
    $n = count($fitur);

    $cx = 170; $cy = 150; $R = 110;

    // Geometri dihitung di sini agar markup SVG di bawah sederhana.
    $aksis = $cincin = $klaster = [];

    if ($n >= 3) {
        $minF = $maxF = [];
        foreach ($fitur as $f) {
            $vals = array_map(fn ($p) => (float) ($p['centroid'][$f] ?? 0), $profil);
            $minF[$f] = min($vals);
            $maxF[$f] = max($vals);
        }

        $titik = function (int $i, float $frac) use ($cx, $cy, $R, $n) {
            $sudut = deg2rad(-90 + 360 * $i / $n);
            return [round($cx + $R * $frac * cos($sudut), 1), round($cy + $R * $frac * sin($sudut), 1)];
        };

        // Cincin grid (poligon konsentris).
        foreach ([0.25, 0.5, 0.75, 1.0] as $ring) {
            $pts = [];
            for ($i = 0; $i < $n; $i++) {
                [$x, $y] = $titik($i, $ring);
                $pts[] = "{$x},{$y}";
            }
            $cincin[] = implode(' ', $pts);
        }

        // Sumbu + label.
        for ($i = 0; $i < $n; $i++) {
            [$ax, $ay] = $titik($i, 1.0);
            [$lx, $ly] = $titik($i, 1.18);
            $aksis[] = [
                'ax' => $ax, 'ay' => $ay, 'lx' => $lx, 'ly' => $ly,
                'label' => $labelFitur[$fitur[$i]] ?? $fitur[$i],
            ];
        }

        // Poligon tiap klaster.
        foreach ($profil as $p) {
            $pts = [];
            for ($i = 0; $i < $n; $i++) {
                $f = $fitur[$i];
                $rentang = $maxF[$f] - $minF[$f];
                $frac = $rentang > 0 ? (((float) ($p['centroid'][$f] ?? 0)) - $minF[$f]) / $rentang : 0.5;
                $frac = 0.18 + 0.82 * $frac;
                [$x, $y] = $titik($i, $frac);
                $pts[] = ['x' => $x, 'y' => $y];
            }
            $klaster[] = [
                'warna' => $palet[$p['cluster'] % count($palet)],
                'poly'  => implode(' ', array_map(fn ($t) => "{$t['x']},{$t['y']}", $pts)),
                'titik' => $pts,
            ];
        }
    }
@endphp

@if ($n >= 3)
    <div class="flex flex-col items-center">
        <svg viewBox="0 0 340 320" class="w-full max-w-md h-auto" role="img" aria-label="Radar profil klaster">
            @foreach ($cincin as $poly)
                <polygon points="{{ $poly }}" fill="none" stroke="#e2e8f0" stroke-width="1" />
            @endforeach

            @foreach ($aksis as $a)
                <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $a['ax'] }}" y2="{{ $a['ay'] }}" stroke="#e2e8f0" stroke-width="1" />
                <text x="{{ $a['lx'] }}" y="{{ $a['ly'] }}" text-anchor="middle" dominant-baseline="middle"
                      class="fill-slate-500" style="font-size:10px">{{ $a['label'] }}</text>
            @endforeach

            @foreach ($klaster as $k)
                <polygon points="{{ $k['poly'] }}" fill="{{ $k['warna'] }}" fill-opacity="0.12" stroke="{{ $k['warna'] }}" stroke-width="2" />
                @foreach ($k['titik'] as $t)
                    <circle cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="2.5" fill="{{ $k['warna'] }}" />
                @endforeach
            @endforeach
        </svg>

        <div class="mt-2 flex flex-wrap justify-center gap-3">
            @foreach ($profil as $p)
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $palet[$p['cluster'] % count($palet)] }}"></span>
                    Klaster {{ $p['cluster'] }} · {{ $p['label_deskriptif'] }}
                </span>
            @endforeach
        </div>
        <p class="mt-2 text-[11px] text-slate-400 text-center">Nilai dinormalisasi antar-klaster (min–maks) agar bentuk sebanding.</p>
    </div>
@endif
