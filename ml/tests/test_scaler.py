"""U-02: StandardScaler service mereproduksi μ/σ (ddof=0) & matriks standardisasi
data emas BAB III. Assert eksplisit ddof=0 (varian populasi, sama scikit-learn)."""
import numpy as np

from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess


def test_u02_scaler_mean_std_ddof0(golden, golden_data):
    fitur = golden["fitur_akademik"]
    df = preprocess(golden_data)
    _, nama, scaler = feature_engineering(df, fitur=fitur)

    assert nama == fitur
    for i, kol in enumerate(fitur):
        # StandardScaler memakai varian POPULASI (ddof=0).
        std_pop = float(np.std(df[kol].to_numpy(dtype=float), ddof=0))
        assert np.isclose(float(np.sqrt(scaler.var_[i])), std_pop, atol=1e-9)
        # Cocok dengan acuan emas (toleransi 1e-4).
        acuan = golden["scaler_ddof0"][kol]
        assert np.isclose(float(scaler.mean_[i]), acuan["mean"], atol=1e-4)
        assert np.isclose(std_pop, acuan["std"], atol=1e-4)


def test_u02_matriks_standardisasi(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])

    kode = [m["kode"] for m in golden["mahasiswa"]]
    for i, k in enumerate(kode):
        acuan = golden["matriks_standardisasi"][k]
        assert np.allclose(X[i], acuan, atol=1e-4), f"{k}: {X[i]} != {acuan}"
