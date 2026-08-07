# Proses Bisnis yang Sedang Berjalan (Sistem Manual)

Diagram proses **pengelolaan data kemahasiswaan yang berjalan saat ini** (manual & tersebar),
untuk BAB III — Analisis Sistem yang Sedang Berjalan. Menyoroti titik nyeri: rekapitulasi manual,
profiling berbasis asumsi, dan adanya perulangan permintaan data (rework).

## Cara pakai di Visual Paradigm
- **Import BPMN:** `docs/vp-import/proses-berjalan.bpmn` (BPMN 2.0). Di VP: *Project → Import → BPMN 2.0…*
  lalu pilih file tersebut. Bila tata letak perlu dirapikan, gunakan *Layout → Auto Layout*.
- **Gambar latar putih**, hindari PNG transparan saat ekspor (sesuai catatan pembimbing).
- Mermaid di bawah hanya untuk **verifikasi alur** cepat (render di editor Markdown); bukan untuk VP.

## Alur (referensi visual — Mermaid)

```mermaid
flowchart TD
    subgraph WD3["Lane: Wakil Dekan III"]
        S1([Perlu gambaran karakteristik mahasiswa]) --> T1[Ajukan permintaan rekapitulasi data]
        T7[Tinjau rekapitulasi] --> G1{Data memadai?}
        G1 -- Ya --> T8[Rumuskan strategi pembinaan] --> E1([Strategi pembinaan ditetapkan])
    end
    subgraph STAF["Lane: Staf Kemahasiswaan"]
        T2[Kumpulkan data dari berkas tiap unit] --> T4[Rekapitulasi data secara manual]
        T4 --> T5[Susun profil mahasiswa berdasarkan asumsi] --> T6[Serahkan rekap ke pimpinan]
    end
    subgraph UNIT["Lane: Unit / Program Studi"]
        T3[Sediakan berkas data lembar kerja terpisah]
    end
    T1 --> T2
    T2 -- minta berkas --> T3
    T3 -- berkas --> T4
    T6 --> T7
    G1 -- "Tidak (lengkapi data)" --> T2
```

## Elemen (untuk penomoran/keterangan di naskah)

| Lane | Kegiatan |
|---|---|
| Wakil Dekan III | Mengajukan permintaan rekapitulasi; meninjau hasil; memutuskan kecukupan data; merumuskan strategi pembinaan. |
| Staf Kemahasiswaan | Mengumpulkan data dari berkas tiap unit; merekapitulasi manual; menyusun profil berdasarkan asumsi; menyerahkan ke pimpinan. |
| Unit / Program Studi | Menyediakan berkas data (lembar kerja terpisah). |

**Titik nyeri yang ditonjolkan:** proses manual & memakan waktu, profiling berbasis asumsi (bukan analisis
data), serta gerbang keputusan "Data memadai?" yang kerap memicu perulangan permintaan data. Ketiga hal ini
menjadi dasar kebutuhan sistem yang diusulkan (SIMAFTUNSUR) pada subbab berikutnya.
