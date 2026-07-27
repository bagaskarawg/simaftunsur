"""
Tahap pelatihan model K-Means dan penentuan jumlah klaster optimal (k).

Penentuan k memakai dua pendekatan sesuai naskah, dengan PERAN BERBEDA:
    - Elbow Method (WCSS / inertia) — MENENTUKAN k pada titik "siku" penurunan
      inertia (dideteksi otomatis via metode jarak-ke-garis/kneedle).
    - Silhouette Coefficient & Davies-Bouldin — METRIK EVALUASI kualitas klaster
      pada k terpilih (bukan pemilih k).
Elbow dipakai sebagai pemilih agar tidak over-segmentasi ketika kurva silhouette
mendatar (silhouette tertinggi bisa jatuh pada k besar yang tak bermakna). Tabel
per-k tetap dikembalikan agar Elbow & Silhouette dapat divisualisasikan di BAB IV.
"""

from __future__ import annotations

import numpy as np
from sklearn.cluster import KMeans
from sklearn.metrics import davies_bouldin_score, silhouette_score

# State acak tetap demi REPRODUSIBILITAS hasil — penting agar klaster yang
# ditunjukkan saat sidang dapat diproduksi ulang dengan angka yang sama.
RANDOM_STATE = 42

# Jumlah inisialisasi ulang KMeans++ untuk menghindari minimum lokal.
N_INIT = 10


def train_kmeans(X: np.ndarray, k: int, random_state: int = RANDOM_STATE) -> KMeans:
    """
    Latih satu model K-Means dengan k klaster.

    Memakai inisialisasi 'k-means++' agar centroid awal tersebar baik.

    Parameter
    ---------
    X : np.ndarray
        Matriks fitur terskala (keluaran feature_engineering).
    k : int
        Jumlah klaster (>= 2).

    Mengembalikan
    -------------
    sklearn.cluster.KMeans
        Model yang sudah dilatih (sudah memuat labels_, cluster_centers_,
        inertia_).
    """
    if k < 2:
        raise ValueError(f"Jumlah klaster k harus >= 2, diterima k={k}.")
    if k > len(X):
        raise ValueError(f"k ({k}) tidak boleh melebihi jumlah data ({len(X)}).")

    model = KMeans(
        n_clusters=k,
        init="k-means++",
        n_init=N_INIT,
        random_state=random_state,
    )
    model.fit(X)
    return model


def pilih_k_optimal(
    X: np.ndarray,
    k_min: int = 2,
    k_max: int = 8,
    random_state: int = RANDOM_STATE,
) -> tuple[int, list[dict]]:
    """
    Cari k optimal dengan mengevaluasi rentang [k_min, k_max].

    Untuk tiap k dihitung inertia (WCSS), Silhouette, dan Davies-Bouldin.
    k terpilih = "siku" (knee) kurva WCSS via Elbow Method (deteksi otomatis).
    Silhouette & Davies-Bouldin dikembalikan sebagai metrik EVALUASI.

    Parameter
    ---------
    X : np.ndarray
        Matriks fitur terskala.
    k_min, k_max : int
        Rentang k yang diuji. k_max otomatis dijepit agar tidak melebihi
        (jumlah_data - 1), karena Silhouette butuh minimal 2 sampel/klaster.

    Mengembalikan
    -------------
    tuple
        (k_terpilih, tabel_evaluasi)
        tabel_evaluasi : list[dict] berisi {k, inertia, silhouette,
        davies_bouldin} untuk tiap k — bahan grafik Elbow & Silhouette.

    Memunculkan
    -----------
    ValueError
        Bila jumlah data terlalu sedikit untuk membentuk >= 2 klaster.
    """
    jumlah = len(X)
    if jumlah < 3:
        raise ValueError(
            f"Data terlalu sedikit untuk klasterisasi ({jumlah} baris). "
            "Minimal 3 baris untuk membentuk 2 klaster yang dapat dievaluasi."
        )

    batas_atas = min(k_max, jumlah - 1)
    if batas_atas < k_min:
        batas_atas = k_min

    tabel: list[dict] = []
    for k in range(k_min, batas_atas + 1):
        model = train_kmeans(X, k, random_state=random_state)
        label = model.labels_

        # Silhouette & Davies-Bouldin hanya bermakna bila benar-benar
        # terbentuk >= 2 klaster berbeda.
        if len(set(label)) < 2:
            continue

        tabel.append(
            {
                "k": k,
                "inertia": float(model.inertia_),
                "silhouette": float(silhouette_score(X, label)),
                "davies_bouldin": float(davies_bouldin_score(X, label)),
            }
        )

    if not tabel:
        raise ValueError("Gagal membentuk klaster yang valid pada rentang k yang diberikan.")

    # k terpilih = "siku" (knee) kurva WCSS/inertia — Elbow Method. Silhouette &
    # Davies-Bouldin pada tabel dipakai sebagai metrik EVALUASI, bukan pemilih k.
    ks = [b["k"] for b in tabel]
    inertias = [b["inertia"] for b in tabel]
    return _knee_elbow(ks, inertias), tabel


def _knee_elbow(ks: list[int], inertias: list[float]) -> int:
    """
    Deteksi "siku" (knee) kurva Elbow secara otomatis dengan metode jarak-ke-garis
    (kneedle sederhana): titik pada kurva (k, inertia) dengan jarak tegak lurus
    TERBESAR terhadap garis penghubung titik pertama & terakhir. Kedua sumbu
    dinormalisasi ke [0, 1] agar jarak tidak bergantung skala sumbu.

    Mengembalikan k pada siku. Bila hanya ada satu kandidat, kembalikan k itu.
    """
    if len(ks) == 1:
        return int(ks[0])

    x = np.asarray(ks, dtype=float)
    y = np.asarray(inertias, dtype=float)
    rentang_x = (x.max() - x.min()) or 1.0
    rentang_y = (y.max() - y.min()) or 1.0
    xn = (x - x.min()) / rentang_x
    yn = (y - y.min()) / rentang_y

    dx, dy = xn[-1] - xn[0], yn[-1] - yn[0]
    penyebut = float(np.hypot(dx, dy)) or 1.0
    jarak = np.abs(dy * (xn - xn[0]) - dx * (yn - yn[0])) / penyebut

    return int(x[int(np.argmax(jarak))])
