"""I-03: validasi keluaran ANTAR-TAHAP pipeline pada data emas, agar tiap tahap
dapat dinyatakan lolos independen (preprocess → scaler → penentuan k)."""
import numpy as np

from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess
from pipeline.train import pilih_k_optimal


def test_i03_preprocess_mempertahankan_9_baris(golden, golden_data):
    df = preprocess(golden_data)
    assert len(df) == 9
    assert set(golden["fitur_akademik"]).issubset(set(df.columns))


def test_i03_scaler_menghasilkan_mean0_std1(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    # Setelah StandardScaler: tiap kolom mean ≈ 0 & std ≈ 1.
    assert np.allclose(X.mean(axis=0), 0.0, atol=1e-9)
    assert np.allclose(X.std(axis=0, ddof=0), 1.0, atol=1e-9)


def test_i03_tabel_evaluasi_k_tersedia_dan_valid(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    k, tabel = pilih_k_optimal(X, 2, 6)

    assert [b["k"] for b in tabel] == [2, 3, 4, 5, 6]
    for b in tabel:
        assert b["inertia"] > 0
        assert -1.0 <= b["silhouette"] <= 1.0
        assert b["davies_bouldin"] >= 0.0
    assert k == 3  # Elbow memilih siku pada data emas
