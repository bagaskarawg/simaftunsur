"""
Paket pipeline klasterisasi K-Means SIMAFTUNSUR.

Disusun modular sesuai CLAUDE.md §4 agar tiap komponen dapat diuji
terpisah dan mudah dipakai ulang. Struktur sengaja menyiapkan jalur
migrasi ke Random Forest di masa depan (BAB V) TANPA mengimplementasikannya
sekarang — fungsi train/evaluate cukup diganti, komponen preprocess &
feature engineering tetap dipakai ulang.

Tahapan (kerangka CRISP-DM yang terintegrasi ke Waterfall):
    preprocess            -> pembersihan data (missing value, outlier, encoding)
    feature_engineering   -> seleksi atribut + penskalaan (StandardScaler/MinMax)
    pilih_k_optimal       -> penentuan k (Elbow WCSS + Silhouette)
    train_kmeans          -> pelatihan model (KMeans++)
    evaluate              -> metrik internal (Silhouette, Davies-Bouldin, WCSS)
    interpret_clusters    -> karakteristik centroid + proyeksi PCA 2D
    validasi              -> uji stabilitas (bootstrap-Jaccard) & beda (Kruskal-Wallis)
"""

from .preprocess import preprocess
from .feature_engineering import feature_engineering, FITUR_NUMERIK_DEFAULT
from .train import train_kmeans, pilih_k_optimal
from .evaluate import evaluate
from .interpret import interpret_clusters
from .validasi import stabilitas_bootstrap, uji_beda_kruskal
from .orchestrator import jalankan_klasterisasi

__all__ = [
    "preprocess",
    "feature_engineering",
    "FITUR_NUMERIK_DEFAULT",
    "train_kmeans",
    "pilih_k_optimal",
    "evaluate",
    "interpret_clusters",
    "stabilitas_bootstrap",
    "uji_beda_kruskal",
    "jalankan_klasterisasi",
]
