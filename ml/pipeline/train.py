"""
Tahap pelatihan model K-Means dan penentuan jumlah klaster optimal (k).

Penentuan k memakai kombinasi dua pendekatan sesuai naskah:
    - Elbow Method (WCSS / inertia) — titik "siku" penurunan inertia.
    - Silhouette Coefficient — k dengan silhouette tertinggi.
Pemilihan otomatis di sini berbasis Silhouette tertinggi (lebih objektif
daripada membaca siku secara visual), sementara tabel inertia tetap
dikembalikan agar Elbow dapat divisualisasikan & dibahas di BAB IV.
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
    k terpilih = k dengan Silhouette tertinggi.

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

    # k terbaik = Silhouette tertinggi (semakin mendekati 1 semakin baik).
    terbaik = max(tabel, key=lambda baris: baris["silhouette"])
    return terbaik["k"], tabel
