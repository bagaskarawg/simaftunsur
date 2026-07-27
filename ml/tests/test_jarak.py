"""U-03: jarak Euclidean pada ruang terskala konsisten dengan definisi K-Means,
dan tiap titik paling dekat ke centroid klasternya sendiri (dasar "kedekatan")."""
import numpy as np

from pipeline.feature_engineering import feature_engineering
from pipeline.preprocess import preprocess
from pipeline.train import train_kmeans


def test_u03_euclidean_konsisten_dengan_transform(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    model = train_kmeans(X, 3)

    jarak = model.transform(X)  # jarak Euclidean ke tiap centroid
    for i in range(len(X)):
        for c in range(3):
            manual = float(np.linalg.norm(X[i] - model.cluster_centers_[c]))
            assert np.isclose(jarak[i, c], manual, atol=1e-6)


def test_u03_setiap_titik_terdekat_ke_centroid_sendiri(golden, golden_data):
    df = preprocess(golden_data)
    X, _, _ = feature_engineering(df, fitur=golden["fitur_akademik"])
    model = train_kmeans(X, 3)

    jarak = model.transform(X)
    for i in range(len(X)):
        assert int(np.argmin(jarak[i])) == int(model.labels_[i])
