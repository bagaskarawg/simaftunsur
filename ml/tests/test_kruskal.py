"""Uji Kruskal-Wallis (non-parametrik): fitur IPK berbeda NYATA antar 3 klaster
data emas (p < 0,05)."""
from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess
from pipeline.train import train_kmeans
from pipeline.validasi import uji_beda_kruskal


def test_kruskal_ipk_signifikan(golden, golden_data):
    fitur = golden["fitur_akademik"]
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=fitur)
    labels = train_kmeans(X, 3).labels_

    hasil = uji_beda_kruskal(df, fitur, labels, alpha=0.05)
    per = {r["fitur"]: r for r in hasil["per_fitur"]}

    assert per["ipk_rata_rata"]["signifikan"] is True
    assert per["ipk_rata_rata"]["p_value"] < 0.05
    assert per["ipk_terakhir"]["p_value"] < 0.05
    assert hasil["jumlah_fitur_signifikan"] >= 2
