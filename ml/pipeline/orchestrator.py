"""
Perangkai (orchestrator) seluruh tahap pipeline menjadi satu pemanggilan.

Dipakai oleh lapisan API (FastAPI) maupun skrip uji mandiri. Mengembalikan
struktur hasil siap-JSON untuk dikonsumsi Laravel: k terpilih, metrik
evaluasi, tabel evaluasi per-k (Elbow/Silhouette), profil klaster, serta
koordinat PCA per mahasiswa.
"""

from __future__ import annotations

from .evaluate import evaluate
from .feature_engineering import FITUR_NUMERIK_DEFAULT, feature_engineering
from .interpret import interpret_clusters
from .preprocess import preprocess
from .train import pilih_k_optimal, train_kmeans

# Ambang volume data minimum sesuai Batasan Masalah (100 mahasiswa aktif
# >= 3 semester). Di bawah ini hasil tetap dihitung TAPI diberi peringatan
# agar tidak diklaim sebagai bukti kualitas (lihat CLAUDE.md §2 & §8).
AMBANG_VOLUME_MINIMUM = 100


def jalankan_klasterisasi(
    data: list[dict],
    fitur: list[str] | None = None,
    k: int | None = None,
    k_min: int = 2,
    k_max: int = 8,
    skema_penskalaan: str = "standard",
) -> dict:
    """
    Jalankan klasterisasi K-Means ujung-ke-ujung.

    Parameter
    ---------
    data : list[dict]
        Data mahasiswa + fitur turunan dari Laravel.
    fitur : list[str] | None
        Atribut yang dipakai; None → FITUR_NUMERIK_DEFAULT.
    k : int | None
        Jumlah klaster. None → ditentukan otomatis (Silhouette tertinggi)
        pada rentang [k_min, k_max].
    k_min, k_max : int
        Rentang pencarian k saat k otomatis.
    skema_penskalaan : str
        "standard" atau "minmax".

    Mengembalikan
    -------------
    dict
        Struktur hasil siap-JSON (lihat modul schemas untuk bentuk pastinya).
    """
    fitur = fitur or list(FITUR_NUMERIK_DEFAULT)

    # 1) Pra-pemrosesan.
    df = preprocess(data)

    # 2) Rekayasa fitur (seleksi + encoding + penskalaan).
    X, nama_fitur, _scaler = feature_engineering(df, fitur, skema_penskalaan)

    # 3) Penentuan k.
    if k is None:
        k_terpilih, tabel_evaluasi = pilih_k_optimal(X, k_min, k_max)
        metode_k = "otomatis (Silhouette tertinggi)"
    else:
        k_terpilih = int(k)
        # Tetap hitung tabel evaluasi rentang untuk grafik Elbow/Silhouette.
        try:
            _, tabel_evaluasi = pilih_k_optimal(X, k_min, k_max)
        except ValueError:
            tabel_evaluasi = []
        metode_k = "manual (ditentukan pengguna)"

    # 4) Pelatihan model final pada k terpilih.
    model = train_kmeans(X, k_terpilih)

    # 5) Evaluasi metrik internal.
    metrik = evaluate(model, X)

    # 6) Interpretasi & visualisasi.
    fitur_asli = [f for f in FITUR_NUMERIK_DEFAULT if f in df.columns]
    profil_klaster, titik = interpret_clusters(model, df, X, fitur_asli)

    return {
        "k_terpilih": int(k_terpilih),
        "metode_pemilihan_k": metode_k,
        "fitur_dipakai": nama_fitur,
        "skema_penskalaan": skema_penskalaan,
        "jumlah_data": int(len(df)),
        "metrik": metrik,
        "evaluasi_k": tabel_evaluasi,
        "profil_klaster": profil_klaster,
        "hasil": titik,
        "peringatan": _susun_peringatan(len(df)),
    }


def _susun_peringatan(jumlah: int) -> list[str]:
    """Kumpulkan peringatan kejujuran data untuk ditampilkan ke pengguna."""
    peringatan: list[str] = []
    if jumlah < AMBANG_VOLUME_MINIMUM:
        peringatan.append(
            f"Volume data ({jumlah}) di bawah ambang minimum "
            f"({AMBANG_VOLUME_MINIMUM} mahasiswa). Hasil bersifat indikatif/"
            "simulasi — JANGAN diklaim sebagai bukti kualitas klaster."
        )
    return peringatan
