"""Uji stabilitas bootstrap-Jaccard: klaster data emas (terpisah jelas) stabil
(rata-rata Jaccard > 0,75) dan deterministik dengan seed tetap."""
from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess
from pipeline.train import train_kmeans
from pipeline.validasi import stabilitas_bootstrap


def _labels_X(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    return train_kmeans(X, 3).labels_, X


def test_jaccard_klaster_stabil(golden, golden_data):
    labels, X = _labels_X(golden, golden_data)
    hasil = stabilitas_bootstrap(X, labels, 3, n_bootstrap=100)

    for pk in hasil["per_klaster"]:
        assert pk["jaccard"] > 0.75, pk
    assert hasil["rata_rata"] > 0.75
    assert hasil["kategori_keseluruhan"] in ("stabil", "cukup stabil")


def test_jaccard_deterministik_dengan_seed(golden, golden_data):
    labels, X = _labels_X(golden, golden_data)
    a = stabilitas_bootstrap(X, labels, 3, n_bootstrap=50, random_state=42)
    b = stabilitas_bootstrap(X, labels, 3, n_bootstrap=50, random_state=42)
    assert a == b
