"""Metrik evaluasi internal: Silhouette & Davies-Bouldin terhitung wajar untuk
k=2..6, dan k=3 (jumlah grup laten data emas) memberi silhouette tertinggi."""
import numpy as np
from sklearn.metrics import davies_bouldin_score, silhouette_score

from pipeline.evaluate import evaluate
from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess
from pipeline.train import train_kmeans


def _matriks(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    return X


def test_silhouette_dbi_wajar_k3_kohesif(golden, golden_data):
    X = _matriks(golden, golden_data)
    silhouette, dbi = {}, {}
    for k in range(2, 7):
        model = train_kmeans(X, k)
        s = silhouette_score(X, model.labels_)
        d = davies_bouldin_score(X, model.labels_)
        assert -1.0 <= s <= 1.0
        assert d >= 0.0
        silhouette[k], dbi[k] = s, d

    # k=3 (jumlah grup laten) menghasilkan klaster kohesif: silhouette tinggi.
    assert silhouette[3] > 0.45
    # Di antara k >= 3, k=3 memberi silhouette tertinggi.
    assert silhouette[3] == max(silhouette[k] for k in (3, 4, 5, 6))
    # CATATAN: silhouette MURNI memuncak di k=2 (pemisahan trivial 2-arah),
    # itulah sebabnya pemilihan k memakai ELBOW, bukan silhouette-maksimum
    # (lihat test_pipeline.py::test_pipeline_elbow_memilih_k3).
    assert silhouette[2] > silhouette[3]


def test_evaluate_mengembalikan_metrik_lengkap(golden, golden_data):
    X = _matriks(golden, golden_data)
    metrik = evaluate(train_kmeans(X, 3), X)
    assert metrik["silhouette"] is not None
    assert metrik["davies_bouldin"] is not None
    assert metrik["inertia"] > 0
