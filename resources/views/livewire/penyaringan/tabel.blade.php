{{-- Tabel kandidat: kolom data mentah + grid ✓/✗ per syarat.
     Header data mentah dapat diklik untuk mengurutkan (satu kolom saja).
     Variabel: $baris, $syaratList, $kolomUrut, $panah, $kosong --}}
@if ($baris->isEmpty())
    <p class="py-8 text-center text-sm text-slate-500">{{ $kosong }}</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                    <th class="py-2 pr-3 font-medium">
                        <button type="button" wire:click="urutkan('npm')" class="hover:text-slate-800 cursor-pointer">NIM / Nama {!! $panah('npm') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium">
                        <button type="button" wire:click="urutkan('prodi')" class="hover:text-slate-800 cursor-pointer">Prodi {!! $panah('prodi') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('angkatan')" class="hover:text-slate-800 cursor-pointer">Angk {!! $panah('angkatan') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('ipk_rata')" class="hover:text-slate-800 cursor-pointer">IPK Rata {!! $panah('ipk_rata') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('ipk_akhir')" class="hover:text-slate-800 cursor-pointer">IPK Akhir {!! $panah('ipk_akhir') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('skor_prestasi')" class="hover:text-slate-800 cursor-pointer">Prest {!! $panah('skor_prestasi') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('skor_kegiatan')" class="hover:text-slate-800 cursor-pointer">Keg {!! $panah('skor_kegiatan') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium text-right">
                        <button type="button" wire:click="urutkan('skor_pengabdian')" class="hover:text-slate-800 cursor-pointer">Pengb {!! $panah('skor_pengabdian') !!}</button>
                    </th>
                    <th class="py-2 pr-3 font-medium">Klaster</th>
                    {{-- Kolom per syarat --}}
                    @foreach ($syaratList as $idx => $s)
                        <th class="py-2 px-1 font-medium text-center" title="{{ $s->label }}{{ $s->wajib ? ' (wajib)' : ' (opsional)' }}">
                            <span class="font-mono text-[11px] {{ $s->wajib ? 'text-slate-600' : 'text-slate-400' }}">S{{ $idx + 1 }}</span>
                        </th>
                    @endforeach
                    <th class="py-2 pl-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($baris as $b)
                    <tr wire:key="kandidat-{{ $b['mahasiswa']->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                        <td class="py-2 pr-3">
                            <a href="{{ route('mahasiswa.detail', $b['mahasiswa']) }}" wire:navigate class="font-medium text-slate-900 hover:text-primary-700">{{ $b['nama'] }}</a>
                            <p class="font-mono text-[11px] text-slate-500">{{ $b['npm'] }}</p>
                        </td>
                        <td class="py-2 pr-3 text-slate-500">{{ $b['prodi'] ?? '—' }}</td>
                        <td class="py-2 pr-3 text-right text-slate-600">{{ $b['angkatan'] }}</td>
                        <td class="py-2 pr-3 text-right font-mono {{ $urut === 'ipk_rata' ? 'font-semibold text-primary-700' : 'text-slate-700' }}">{{ number_format($b['ipk_rata'], 2) }}</td>
                        <td class="py-2 pr-3 text-right font-mono {{ $urut === 'ipk_akhir' ? 'font-semibold text-primary-700' : 'text-slate-700' }}">{{ number_format($b['ipk_akhir'], 2) }}</td>
                        <td class="py-2 pr-3 text-right font-mono {{ $urut === 'skor_prestasi' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $b['skor_prestasi'] }}</td>
                        <td class="py-2 pr-3 text-right font-mono {{ $urut === 'skor_kegiatan' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $b['skor_kegiatan'] }}</td>
                        <td class="py-2 pr-3 text-right font-mono {{ $urut === 'skor_pengabdian' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $b['skor_pengabdian'] }}</td>
                        <td class="py-2 pr-3 text-slate-500 text-xs">{{ $b['label_klaster'] ?? '—' }}</td>
                        {{-- Status per syarat --}}
                        @foreach ($b['kriteria'] as $k)
                            <td class="py-2 px-1 text-center">
                                @if ($k->keterangan && ! $k->lolos && in_array($k->keterangan, ['belum diklaster', 'data belum tersedia'], true))
                                    <span class="text-slate-300" title="{{ $k->keterangan }}">—</span>
                                @elseif ($k->lolos)
                                    <span class="text-green-600" title="{{ $k->label }}: terpenuhi">✓</span>
                                @else
                                    <span class="text-red-500" title="{{ $k->label }}: tidak terpenuhi">✗</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="py-2 pl-3 text-right">
                            <button type="button" wire:click="lihatDetail({{ $b['mahasiswa']->id }})"
                                    class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Detail</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
