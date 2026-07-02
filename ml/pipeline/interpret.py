"""
Tahap interpretasi & visualisasi hasil klaster.

Menghasilkan:
    - Profil tiap klaster: jumlah anggota + rata-rata fitur asli (skala
      sebenarnya, bukan skala standar) per klaster, agar mudah dibaca pimpinan.
    - Label deskriptif heuristik berdasarkan peringkat rata-rata IPK antar
      klaster (mis. "Berprestasi", "Menengah", "Perlu Pembinaan").
    - Koordinat PCA 2D untuk scatter plot pada dashboard.

Catatan kejujuran: label deskriptif hanyalah ALAT BANTU baca berbasis
peringkat IPK, BUKAN klaim ilmiah. Penamaan akhir tiap klaster sebaiknya
ditinjau pimpinan/WD III sesuai konteks.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA

RANDOM_STATE = 42


def interpret_clusters(
    model: KMeans,
    df: pd.DataFrame,
    X: np.ndarray,
    fitur_asli: list[str],
    nama_fitur: list[str] | None = None,
) -> tuple[list[dict], list[dict]]:
    """
    Susun profil tiap klaster dan koordinat PCA 2D per mahasiswa.

    Parameter
    ---------
    model : KMeans
        Model terlatih.
    df : pandas.DataFrame
        Data bersih (skala asli) berisi identitas + fitur numerik asli.
    X : np.ndarray
        Matriks fitur terskala (dipakai untuk proyeksi PCA).
    fitur_asli : list[str]
        Nama fitur numerik pada skala asli yang ingin dirangkum di centroid
        (mis. ipk_rata_rata, tren, konsistensi, semester_aktif).

    Mengembalikan
    -------------
    tuple
        (profil_klaster, titik)
        - profil_klaster : list[dict] ringkasan per klaster.
        - titik : list[dict] {id, cluster, pca_x, pca_y} per mahasiswa.
    """
    label = model.labels_
    df = df.copy()
    df["cluster"] = label.astype(int)

    fitur_ringkas = [k for k in fitur_asli if k in df.columns]

    # Nama kolom ruang terskala (untuk memetakan centroid & vektor terskala).
    nama_fitur = list(nama_fitur) if nama_fitur else []

    # Jarak tiap titik ke SEMUA centroid; dipakai untuk jarak ke centroid sendiri.
    jarak_semua = model.transform(X)  # shape (n_sampel, k)
    centers = model.cluster_centers_  # shape (k, n_fitur_terskala)

    # Rata-rata IPK tiap klaster untuk penentuan peringkat label deskriptif.
    if "ipk_rata_rata" in df.columns:
        urutan = (
            df.groupby("cluster")["ipk_rata_rata"].mean().sort_values(ascending=False)
        )
        peringkat = {klaster: i for i, klaster in enumerate(urutan.index)}
    else:
        peringkat = {k: i for i, k in enumerate(sorted(df["cluster"].unique()))}

    jumlah_klaster = len(peringkat)

    profil_klaster: list[dict] = []
    for klaster in sorted(df["cluster"].unique()):
        anggota = df[df["cluster"] == klaster]
        centroid = {
            kolom: round(float(anggota[kolom].mean()), 4) for kolom in fitur_ringkas
        }
        # Centroid pada ruang terskala (satu baris cluster_centers_ per klaster).
        centroid_terskala = None
        if nama_fitur and int(klaster) < len(centers):
            centroid_terskala = {
                nama: round(float(nilai), 4)
                for nama, nilai in zip(nama_fitur, centers[int(klaster)])
            }
        profil_klaster.append(
            {
                "cluster": int(klaster),
                "jumlah": int(len(anggota)),
                "centroid": centroid,
                "centroid_terskala": centroid_terskala,
                "label_deskriptif": _label_deskriptif(
                    peringkat.get(klaster, 0), jumlah_klaster
                ),
            }
        )

    # Proyeksi PCA 2D. Bila fitur < 2 dimensi, PCA tak relevan → koordinat 0.
    titik: list[dict] = []
    koordinat = _proyeksi_pca(X)
    id_list = df["id"].tolist() if "id" in df.columns else list(range(len(df)))
    for i, baris_id in enumerate(id_list):
        klaster_i = int(label[i])
        fitur_terskala = (
            {nama: round(float(nilai), 4) for nama, nilai in zip(nama_fitur, X[i])}
            if nama_fitur
            else None
        )
        titik.append(
            {
                "id": int(baris_id),
                "cluster": klaster_i,
                "pca_x": round(float(koordinat[i, 0]), 4),
                "pca_y": round(float(koordinat[i, 1]), 4),
                "fitur_terskala": fitur_terskala,
                "jarak_ke_centroid": round(float(jarak_semua[i, klaster_i]), 4),
            }
        )

    return profil_klaster, titik


def _proyeksi_pca(X: np.ndarray) -> np.ndarray:
    """Reduksi fitur ke 2 dimensi untuk scatter plot. Aman untuk 1 fitur."""
    if X.shape[1] < 2:
        # Satu fitur: taruh di sumbu-x, sumbu-y nol.
        return np.column_stack([X[:, 0], np.zeros(len(X))])
    pca = PCA(n_components=2, random_state=RANDOM_STATE)
    return pca.fit_transform(X)


def _label_deskriptif(peringkat: int, total: int) -> str:
    """
    Beri label heuristik berdasarkan peringkat rata-rata IPK klaster
    (0 = IPK tertinggi). Hanya alat bantu baca, bukan klaim ilmiah.
    """
    if total <= 1:
        return "Klaster Tunggal"
    if peringkat == 0:
        return "Berprestasi"
    if peringkat == total - 1:
        return "Perlu Pembinaan"
    if total == 3:
        return "Menengah"
    return f"Menengah (tingkat {peringkat})"
