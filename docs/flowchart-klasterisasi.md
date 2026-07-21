# Flowchart Proses Klasterisasi K-Means — SIMAFTUNSUR (BAB III)

> **Menjawab feedback dosen 2 Juli 2026:** proses klasterisasi digambarkan sebagai
> **flowchart** (bukan Activity Diagram), dengan proses **Elbow**, **Silhouette**,
> dan **Davies-Bouldin Index** yang terpisah dan terlihat tahapannya.
> Diselaraskan dengan implementasi nyata `ml/pipeline/` (orchestrator, train,
> evaluate, interpret) per 2026-07-02.
>
> **Render:** cara termudah — jalankan `tools\render-gambar.ps1`; kelima
> flowchart otomatis jadi PNG latar putih di `docs\gambar\` (FC-01..FC-05)
> lewat sumber PlantUML `docs/vp-import/simaftunsur-flowchart.puml` (java-only).
> Blok ```mermaid``` di bawah tetap disediakan untuk pratinjau cepat di
> <https://mermaid.live> (bila ekspor dari sana, pilih **latar putih**, bukan
> transparan). Untuk Visual Paradigm, gambar ulang memakai palet **Flowchart**
> VP mengikuti sumber teks ini (oval = mulai/selesai, jajar genjang =
> input/output, persegi = proses, belah ketupat = keputusan).

---

## FC-01 — Flowchart Utama Proses Klasterisasi

Loop evaluasi k menghitung **tiga metrik terpisah** untuk tiap kandidat k (2–8):
WCSS (bahan Metode Elbow), Silhouette Coefficient, dan Davies-Bouldin Index.
Pemilihan k memakai Silhouette tertinggi; Elbow dan DBI menjadi pembanding
(rincian tiap metrik: FC-02 s.d. FC-05).

```mermaid
%%{init: {'theme': 'default'}}%%
flowchart TD
    A([Mulai]) --> B[/"Input: data mahasiswa layak<br>(status aktif, ≥ 3 catatan IPK)<br>7 fitur: IPK rata-rata, IPK terakhir, tren,<br>konsistensi, skor prestasi, kegiatan, pengabdian"/]
    B --> C["Praproses data:<br>tangani missing value, outlier, encoding"]
    C --> D["Penskalaan fitur dengan StandardScaler:<br>z = (x − μ) / σ"]
    D --> E{"Nilai k ditentukan<br>manual?"}
    E -- "Ya" --> M
    E -- "Tidak" --> F["Tetapkan k = 2 (k_min)"]

    F --> G["Latih K-Means percobaan dengan k klaster<br>(inisialisasi k-means++) — lihat FC-02"]
    G --> H["PROSES ELBOW:<br>hitung WCSS untuk k ini — lihat FC-03"]
    H --> I["PROSES SILHOUETTE:<br>hitung Silhouette Coefficient untuk k ini — lihat FC-04"]
    I --> J["PROSES DAVIES-BOULDIN:<br>hitung DBI untuk k ini — lihat FC-05"]
    J --> K{"k < 8 (k_max)?"}
    K -- "Ya" --> K1["k = k + 1"] --> G
    K -- "Tidak" --> L["Pilih k optimal = k dengan Silhouette tertinggi<br>(kurva Elbow dan DBI sebagai pembanding)"]

    L --> M["Latih model K-Means final dengan k optimal<br>(k-means++, random_state = 42, n_init = 10)"]
    M --> N["Hitung metrik evaluasi final:<br>Silhouette, DBI, WCSS/inertia"]
    N --> O{"Jumlah data ≥ 100<br>(ambang ideal)?"}
    O -- "Tidak" --> P["Tandai peringatan:<br>hasil bersifat indikatif"] --> Q
    O -- "Ya" --> Q["Interpretasi klaster:<br>profil centroid per fitur + proyeksi PCA 2D"]
    Q --> R[/"Output: label klaster tiap mahasiswa,<br>centroid, metrik evaluasi, tabel evaluasi per-k,<br>koordinat PCA (JSON)"/]
    R --> S([Selesai])
```

---

## FC-02 — Flowchart Algoritma K-Means (jarak Euclidean)

Tahapan inti algoritma yang dipanggil pada setiap pelatihan (percobaan per-k
maupun final).

```mermaid
%%{init: {'theme': 'default'}}%%
flowchart TD
    A([Mulai]) --> B[/"Input: data terskala Z, jumlah klaster k"/]
    B --> C["Tentukan k centroid awal dengan k-means++<br>(centroid pertama acak; berikutnya dipilih<br>berpeluang sebanding kuadrat jarak<br>ke centroid terdekat)"]
    C --> D["Hitung jarak Euclidean tiap titik x_i<br>ke tiap centroid c_j:<br>d(x_i, c_j) = √( Σ_m (x_im − c_jm)² )"]
    D --> E["Tugaskan tiap titik ke klaster<br>dengan centroid terdekat (d minimum)"]
    E --> F["Hitung ulang tiap centroid:<br>c_j = rata-rata seluruh titik anggota klaster j"]
    F --> G{"Centroid masih<br>berubah?"}
    G -- "Ya" --> D
    G -- "Tidak" --> H[/"Output: label klaster tiap titik,<br>centroid akhir"/]
    H --> I([Selesai])
```

---

## FC-03 — Flowchart Proses Metode Elbow (WCSS)

```mermaid
%%{init: {'theme': 'default'}}%%
flowchart TD
    A([Mulai]) --> B[/"Input: hasil K-Means tiap kandidat k<br>(label klaster + centroid, k = 2..8)"/]
    B --> C["Untuk k berjalan, hitung WCSS:<br>WCSS = Σ_j Σ_(x∈C_j) ‖x − c_j‖²<br>(jumlah kuadrat jarak tiap titik<br>ke centroid klasternya)"]
    C --> D{"Semua kandidat k<br>sudah dihitung?"}
    D -- "Belum" --> C
    D -- "Sudah" --> E["Susun kurva k terhadap WCSS<br>(WCSS selalu menurun saat k bertambah)"]
    E --> F["Tentukan titik siku (elbow):<br>k tempat penurunan WCSS mulai melandai"]
    F --> G[/"Output: tabel WCSS per-k + kandidat k versi Elbow"/]
    G --> H([Selesai])
```

---

## FC-04 — Flowchart Proses Silhouette Coefficient

```mermaid
%%{init: {'theme': 'default'}}%%
flowchart TD
    A([Mulai]) --> B[/"Input: data terskala + label klaster<br>hasil K-Means untuk k berjalan"/]
    B --> C["Untuk tiap titik i, hitung a(i):<br>rata-rata jarak i ke seluruh titik lain<br>DALAM klaster yang sama (kohesi)"]
    C --> D["Untuk tiap titik i, hitung b(i):<br>rata-rata jarak i ke titik-titik klaster lain,<br>ambil yang terkecil antar-klaster (separasi)"]
    D --> E["Hitung nilai silhouette titik i:<br>s(i) = ( b(i) − a(i) ) / max( a(i), b(i) )"]
    E --> F{"Semua titik<br>sudah dihitung?"}
    F -- "Belum" --> C
    F -- "Sudah" --> G["Silhouette Coefficient klaster =<br>rata-rata s(i) seluruh titik"]
    G --> H["Interpretasi: mendekati +1 = klaster baik;<br>sekitar 0 = tumpang tindih; negatif = salah kelompok"]
    H --> I[/"Output: Silhouette Coefficient untuk k berjalan"/]
    I --> J([Selesai])
```

---

## FC-05 — Flowchart Proses Davies-Bouldin Index

```mermaid
%%{init: {'theme': 'default'}}%%
flowchart TD
    A([Mulai]) --> B[/"Input: data terskala + label klaster + centroid<br>hasil K-Means untuk k berjalan"/]
    B --> C["Untuk tiap klaster i, hitung sebaran intra-klaster:<br>S_i = rata-rata jarak anggota klaster i<br>ke centroid c_i"]
    C --> D["Untuk tiap pasangan klaster (i, j), hitung:<br>M_ij = jarak antar-centroid ‖c_i − c_j‖"]
    D --> E["Hitung rasio kemiripan tiap pasangan:<br>R_ij = ( S_i + S_j ) / M_ij"]
    E --> F["Untuk tiap klaster i, ambil rasio terburuk:<br>R_i = max_(j≠i) R_ij"]
    F --> G["DBI = (1/k) Σ_i R_i"]
    G --> H["Interpretasi: makin KECIL makin baik<br>(klaster rapat dan saling berjauhan)"]
    H --> I[/"Output: DBI untuk k berjalan"/]
    I --> J([Selesai])
```

---

## Keterkaitan dengan kode

| Flowchart | Implementasi |
|---|---|
| FC-01 | `ml/pipeline/orchestrator.py` — `jalankan_klasterisasi()` |
| FC-02 | `ml/pipeline/train.py` — `train_kmeans()` (scikit-learn `KMeans`, `init="k-means++"`) |
| FC-03 | `ml/pipeline/train.py` + `evaluate.py` — `inertia_` per-k (tabel `evaluasi_k`) |
| FC-04 | `ml/pipeline/evaluate.py` — `silhouette_score()`; pemilihan k di `pilih_k_optimal()` |
| FC-05 | `ml/pipeline/evaluate.py` — `davies_bouldin_score()` |

Rumus lengkap tiap metode beserta **contoh perhitungan numerik** (manual vs
scikit-learn, hasil identik) ada di `docs/bab2-scikit-learn-integrasi.md`.
