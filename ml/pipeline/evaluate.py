"""
Tahap evaluasi kualitas klaster memakai metrik INTERNAL (unsupervised).

Karena tidak ada label kebenaran (status akhir mahasiswa belum tersedia),
evaluasi TIDAK memakai Accuracy/Precision/Recall, melainkan:
    - Silhouette Coefficient : [-1, 1], makin tinggi makin baik (klaster
      rapat & terpisah jelas).
    - Davies-Bouldin Index   : >= 0, makin RENDAH makin baik (rasio sebaran
      dalam-klaster terhadap jarak antar-klaster).
    - Inertia (WCSS)         : jumlah kuadrat jarak ke centroid; dipakai untuk
      Elbow Method.
"""

from __future__ import annotations

import numpy as np
from sklearn.cluster import KMeans
from sklearn.metrics import davies_bouldin_score, silhouette_score


def evaluate(model: KMeans, X: np.ndarray) -> dict:
    """
    Hitung metrik evaluasi internal untuk model K-Means terlatih.

    Parameter
    ---------
    model : sklearn.cluster.KMeans
        Model yang sudah dilatih.
    X : np.ndarray
        Matriks fitur terskala yang dipakai melatih model.

    Mengembalikan
    -------------
    dict
        {silhouette, davies_bouldin, inertia}. Nilai silhouette &
        davies_bouldin bernilai None bila hanya terbentuk 1 klaster
        (metrik tak terdefinisi).
    """
    label = model.labels_
    jumlah_klaster = len(set(label))

    metrik = {
        "inertia": float(model.inertia_),
        "silhouette": None,
        "davies_bouldin": None,
    }

    if jumlah_klaster >= 2:
        metrik["silhouette"] = float(silhouette_score(X, label))
        metrik["davies_bouldin"] = float(davies_bouldin_score(X, label))

    return metrik
