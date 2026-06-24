# Service Klasterisasi K-Means — SIMAFTUNSUR

Service Python (FastAPI + scikit-learn) untuk klasterisasi profil mahasiswa.
Dijalankan terpisah dari aplikasi Laravel dan dipanggil via REST API.

## Struktur

```
ml/
├── api.py                 # Endpoint FastAPI (/sehat, /klasterisasi)
├── schemas.py             # Kontrak data Pydantic (request/response)
├── uji_pipeline.py        # Uji mandiri tanpa API (data sintetik)
├── requirements.txt
└── pipeline/              # Pipeline modular (CLAUDE.md §4)
    ├── preprocess.py            # missing value, outlier, normalisasi tipe
    ├── feature_engineering.py   # seleksi atribut, one-hot, penskalaan
    ├── train.py                 # train_kmeans + pilih_k_optimal (Elbow+Silhouette)
    ├── evaluate.py              # Silhouette, Davies-Bouldin, Inertia (WCSS)
    ├── interpret.py             # profil centroid + label heuristik + PCA 2D
    └── orchestrator.py          # perangkai seluruh tahap → hasil siap-JSON
```

> Struktur modular ini menyiapkan jalur migrasi ke Random Forest (BAB V):
> cukup mengganti `train.py`/`evaluate.py`, sedangkan `preprocess.py` &
> `feature_engineering.py` dipakai ulang. **Random Forest belum diimplementasikan.**

## Versi Python

Disarankan **Python 3.12** (atau 3.11) — scikit-learn punya wheel paling stabil
di sana. Python 3.14 sangat baru; sebagian paket ML mungkin belum menyediakan
wheel sehingga `pip install` bisa gagal/mengkompilasi dari sumber.

## Setup

```bash
cd ml
py -3.12 -m venv .venv          # atau: python -m venv .venv
.venv\Scripts\activate          # Windows (PowerShell/CMD)
# source .venv/bin/activate     # Linux/Mac
pip install -r requirements.txt
```

## Menjalankan

### Uji pipeline (tanpa API, paling cepat untuk verifikasi)

```bash
python uji_pipeline.py
```

### Menjalankan service API

```bash
uvicorn api:app --host 127.0.0.1 --port 8001 --reload
```

Dokumentasi interaktif otomatis tersedia di <http://127.0.0.1:8001/docs>.

### Contoh permintaan

```bash
curl -X POST http://127.0.0.1:8001/klasterisasi ^
  -H "Content-Type: application/json" ^
  -d "{\"data\":[{\"id\":1,\"ipk_rata_rata\":3.6,\"ipk_terakhir\":3.7,\"tren\":0.05,\"konsistensi\":0.1,\"semester_aktif\":5}, {\"id\":2,\"ipk_rata_rata\":2.4,\"ipk_terakhir\":2.3,\"tren\":-0.05,\"konsistensi\":0.3,\"semester_aktif\":5}]}"
```

## Kontrak data

- **Request** `POST /klasterisasi` — lihat `schemas.PermintaanKlasterisasi`.
  Field utama: `data[]` (id, ipk_rata_rata, ipk_terakhir, tren, konsistensi,
  semester_aktif, program_studi), `fitur` (opsional), `k` (opsional; None =
  otomatis), `k_min`, `k_max`, `skema_penskalaan` (`standard`|`minmax`).
- **Response** — `schemas.TanggapanKlasterisasi`: `k_terpilih`, `metrik`
  (silhouette/davies_bouldin/inertia), `evaluasi_k[]` (untuk grafik Elbow/
  Silhouette), `profil_klaster[]` (centroid + label), `hasil[]` (id, cluster,
  pca_x, pca_y), `peringatan[]`.

## Catatan integritas (WAJIB dibaca)

Evaluasi memakai metrik **internal** (Silhouette, Davies-Bouldin, Elbow) karena
data **tidak berlabel** (unsupervised) — BUKAN Accuracy/Precision/Recall.
Bila volume data < 100 mahasiswa, service mengembalikan `peringatan` bahwa
hasil bersifat indikatif. **Jangan mengklaim kualitas klaster dari data
sintetik/dummy** (CLAUDE.md §2 & §8).
