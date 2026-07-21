"""
Validasi lanjutan hasil klaster — PELENGKAP metrik internal (Silhouette, DBI,
Elbow) yang tetap menjadi metrik utama karena K-Means bersifat unsupervised.

Dua uji disediakan:
    1. Uji STABILITAS klaster via bootstrap-Jaccard (Hennig, 2007).
       Menjawab: "Bila data di-resample, apakah klaster yang sama terbentuk
       kembali?" Skor Jaccard rata-rata per klaster diinterpretasi sbb:
           >= 0.85  → klaster STABIL / dapat dipercaya
           0.60–0.85 → cukup stabil (pola nyata tapi ada ketidakpastian)
           < 0.60   → TIDAK stabil (jangan dipercaya sebagai klaster nyata)
       Ambang ini mengikuti rekomendasi Hennig (fpc::clusterboot).
    2. Uji BEDA antar-klaster via Kruskal-Wallis H (non-parametrik).
       Menjawab: "Apakah tiap fitur benar-benar berbeda nyata antar-klaster?"
       Dipilih non-parametrik agar tidak bergantung asumsi normalitas maupun
       homogenitas ragam. p-value < alpha (default 0,05) → beda signifikan.

CATATAN KEJUJURAN: uji ini menilai KEANDALAN struktur klaster, bukan
kebenaran label (yang memang tidak tersedia). Hasil pada data sintetik/dummy
tetap tidak boleh diklaim sebagai bukti ilmiah.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from scipy.stats import kruskal
from sklearn.cluster import KMeans

from .train import N_INIT, RANDOM_STATE

# Ambang interpretasi skor Jaccard (lihat docstring modul).
AMBANG_STABIL = 0.85
AMBANG_MINIMUM = 0.60

# Iterasi bootstrap bawaan. 100 = rekomendasi umum (Hennig); dapat diperkecil
# lewat parameter bila data sangat besar dan waktu jadi kendala.
N_BOOTSTRAP_DEFAULT = 100


def _kategori_jaccard(nilai: float) -> str:
    """Petakan skor Jaccard ke kategori kualitatif."""
    if nilai >= AMBANG_STABIL:
        return "stabil"
    if nilai >= AMBANG_MINIMUM:
        return "cukup stabil"
    return "tidak stabil"


def stabilitas_bootstrap(
    X: np.ndarray,
    labels_ref: np.ndarray,
    k: int,
    n_bootstrap: int = N_BOOTSTRAP_DEFAULT,
    random_state: int = RANDOM_STATE,
) -> dict | None:
    """
    Uji stabilitas klaster dengan bootstrap-Jaccard (metode Hennig).

    Prosedur tiap iterasi:
        1. Ambil sampel bootstrap (acak dengan pengembalian) sebesar n.
        2. Klaster ulang sampel tsb dengan k yang sama.
        3. Untuk tiap klaster referensi, hitung Jaccard maksimum terhadap
           klaster hasil bootstrap (dibatasi pada titik yang ikut tersampel).
    Skor stabilitas klaster = rata-rata Jaccard maksimum tsb atas seluruh
    iterasi. Makin mendekati 1 makin stabil.

    Parameter
    ---------
    X : np.ndarray
        Matriks fitur terskala yang dipakai melatih model referensi.
    labels_ref : np.ndarray
        Label klaster model referensi (model.labels_).
    k : int
        Jumlah klaster.
    n_bootstrap : int
        Banyak iterasi bootstrap. 0 → uji dilewati (mengembalikan None).
    random_state : int
        Seed dasar demi reproduktibilitas.

    Mengembalikan
    -------------
    dict | None
        Ringkasan stabilitas, atau None bila tidak dapat dihitung (k < 2,
        data terlalu sedikit, atau n_bootstrap <= 0).
    """
    n = len(X)
    if k < 2 or n_bootstrap <= 0 or n < k * 2:
        return None

    labels_ref = np.asarray(labels_ref)
    referensi = [set(np.where(labels_ref == c)[0].tolist()) for c in range(k)]

    akumulasi = np.zeros(k, dtype=float)
    tercakup = np.zeros(k, dtype=int)
    rng = np.random.default_rng(random_state)

    for _ in range(n_bootstrap):
        indeks = rng.integers(0, n, size=n)
        tersampel = set(indeks.tolist())

        model_boot = KMeans(
            n_clusters=k,
            init="k-means++",
            n_init=N_INIT,
            random_state=random_state,
        ).fit(X[indeks])

        # Petakan tiap klaster bootstrap ke himpunan indeks ASLI (buang
        # duplikat akibat pengembalian — Jaccard dihitung atas himpunan).
        label_boot = model_boot.labels_
        klaster_boot = [
            set(indeks[np.where(label_boot == c)[0]].tolist()) for c in range(k)
        ]

        for i, ref in enumerate(referensi):
            ref_tersampel = ref & tersampel
            if not ref_tersampel:
                continue
            terbaik = 0.0
            for boot in klaster_boot:
                gabungan = ref_tersampel | boot
                if not gabungan:
                    continue
                skor = len(ref_tersampel & boot) / len(gabungan)
                if skor > terbaik:
                    terbaik = skor
            akumulasi[i] += terbaik
            tercakup[i] += 1

    per_klaster_nilai = akumulasi / np.where(tercakup > 0, tercakup, 1)

    per_klaster = [
        {
            "cluster": int(i),
            "jaccard": round(float(per_klaster_nilai[i]), 4),
            "kategori": _kategori_jaccard(float(per_klaster_nilai[i])),
        }
        for i in range(k)
    ]

    rata_rata = float(per_klaster_nilai.mean())
    minimum = float(per_klaster_nilai.min())

    return {
        "metode": "bootstrap-Jaccard (Hennig)",
        "n_bootstrap": int(n_bootstrap),
        "ambang_stabil": AMBANG_STABIL,
        "ambang_minimum": AMBANG_MINIMUM,
        "per_klaster": per_klaster,
        "rata_rata": round(rata_rata, 4),
        "minimum": round(minimum, 4),
        # Kesimpulan konservatif: ditentukan klaster TERLEMAH (minimum), bukan
        # rata-rata, agar tidak menutupi klaster yang tak stabil.
        "kategori_keseluruhan": _kategori_jaccard(minimum),
    }


def uji_beda_kruskal(
    df: pd.DataFrame,
    fitur: list[str],
    labels: np.ndarray,
    alpha: float = 0.05,
) -> dict | None:
    """
    Uji beda antar-klaster untuk tiap fitur dengan Kruskal-Wallis H.

    Menguji hipotesis nol "distribusi fitur sama di semua klaster". p-value
    kecil (< alpha) → minimal satu klaster berbeda nyata pada fitur tsb,
    memperkuat argumen bahwa klaster memang terpisah secara bermakna.

    Parameter
    ---------
    df : pandas.DataFrame
        Data (skala asli) berisi kolom fitur yang diuji.
    fitur : list[str]
        Nama fitur yang diuji (mis. 7 fitur SKKM).
    labels : np.ndarray
        Label klaster tiap baris (selaras urutan df).
    alpha : float
        Taraf signifikansi (default 0,05).

    Mengembalikan
    -------------
    dict | None
        Ringkasan hasil uji, atau None bila hanya terbentuk 1 klaster.
    """
    label_arr = np.asarray(labels)
    klaster_unik = sorted(set(label_arr.tolist()))
    if len(klaster_unik) < 2:
        return None

    per_fitur: list[dict] = []
    for nama in fitur:
        if nama not in df.columns:
            continue

        nilai = pd.to_numeric(df[nama], errors="coerce").to_numpy(dtype=float)
        grup = [nilai[label_arr == c] for c in klaster_unik]

        if any(len(g) == 0 for g in grup):
            continue

        # Kruskal-Wallis tak terdefinisi bila SELURUH nilai identik (tak ada
        # ranking) → tandai tidak signifikan secara eksplisit.
        gabungan = np.concatenate(grup)
        if np.all(gabungan == gabungan[0]):
            per_fitur.append(
                {"fitur": nama, "statistik_h": 0.0, "p_value": 1.0, "signifikan": False}
            )
            continue

        try:
            statistik, p_value = kruskal(*grup)
        except ValueError:
            # Mis. semua grup konstan; abaikan fitur ini.
            continue

        per_fitur.append(
            {
                "fitur": nama,
                "statistik_h": round(float(statistik), 4),
                "p_value": round(float(p_value), 6),
                "signifikan": bool(p_value < alpha),
            }
        )

    jumlah_signifikan = sum(1 for r in per_fitur if r["signifikan"])

    return {
        "metode": "Kruskal-Wallis H (non-parametrik)",
        "alpha": alpha,
        "per_fitur": per_fitur,
        "jumlah_fitur": len(per_fitur),
        "jumlah_fitur_signifikan": jumlah_signifikan,
    }
