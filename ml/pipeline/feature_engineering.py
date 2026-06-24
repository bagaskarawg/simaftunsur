"""
Tahap rekayasa fitur (feature engineering).

Memilih atribut yang dipakai untuk klasterisasi, melakukan one-hot encoding
pada atribut kategorikal (program studi), lalu menyeragamkan skala antar-fitur
memakai StandardScaler (default) atau MinMaxScaler. Penyeragaman skala penting
karena K-Means berbasis jarak Euclidean — fitur berskala besar (mis. semester)
akan mendominasi bila tidak diskalakan.
"""

from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.preprocessing import MinMaxScaler, StandardScaler

# Fitur numerik bawaan untuk klasterisasi profil akademik mahasiswa.
# Diturunkan dari riwayat IPK per semester (lihat Model Mahasiswa di Laravel:
# ipkRataRata(), ipkTerakhir(), tren(), konsistensi()).
FITUR_NUMERIK_DEFAULT = [
    "ipk_rata_rata",
    "ipk_terakhir",
    "tren",
    "konsistensi",
    "semester_aktif",
]

# Atribut kategorikal yang didukung untuk one-hot encoding.
FITUR_KATEGORIKAL = ["program_studi"]


def feature_engineering(
    df: pd.DataFrame,
    fitur: list[str] | None = None,
    skema_penskalaan: str = "standard",
) -> tuple[np.ndarray, list[str], object]:
    """
    Bentuk matriks fitur terskala dari DataFrame bersih.

    Parameter
    ---------
    df : pandas.DataFrame
        Keluaran tahap preprocess.
    fitur : list[str] | None
        Daftar nama atribut yang dipakai. Bila None, memakai
        FITUR_NUMERIK_DEFAULT. Boleh menyertakan "program_studi" untuk
        mengikutkan prodi (akan di-one-hot).
    skema_penskalaan : str
        "standard" (StandardScaler, default) atau "minmax" (MinMaxScaler).

    Mengembalikan
    -------------
    tuple
        (X, nama_fitur, scaler)
        - X : np.ndarray matriks fitur terskala.
        - nama_fitur : list[str] nama kolom akhir (termasuk hasil one-hot).
        - scaler : objek scaler yang sudah dilatih (untuk audit/reuse).
    """
    fitur = list(fitur) if fitur else list(FITUR_NUMERIK_DEFAULT)

    kolom_numerik = [k for k in fitur if k in FITUR_NUMERIK_DEFAULT and k in df.columns]
    kolom_kategorikal = [k for k in fitur if k in FITUR_KATEGORIKAL and k in df.columns]

    if not kolom_numerik and not kolom_kategorikal:
        raise ValueError(
            "Tidak ada fitur valid yang dapat dipakai. "
            f"Diminta: {fitur}; tersedia di data: {list(df.columns)}."
        )

    bagian = []
    nama_fitur: list[str] = []

    # Fitur numerik.
    if kolom_numerik:
        bagian.append(df[kolom_numerik].to_numpy(dtype=float))
        nama_fitur.extend(kolom_numerik)

    # Fitur kategorikal → one-hot (pakai pandas agar tanpa dependensi versi
    # OneHotEncoder yang berubah-ubah antar rilis scikit-learn).
    if kolom_kategorikal:
        dummies = pd.get_dummies(
            df[kolom_kategorikal], prefix=kolom_kategorikal, dtype=float
        )
        bagian.append(dummies.to_numpy(dtype=float))
        nama_fitur.extend(dummies.columns.tolist())

    matriks = np.hstack(bagian)

    scaler = _buat_scaler(skema_penskalaan)
    X = scaler.fit_transform(matriks)

    return X, nama_fitur, scaler


def _buat_scaler(skema: str):
    """Kembalikan instance scaler sesuai skema yang diminta."""
    skema = (skema or "standard").lower()
    if skema == "minmax":
        return MinMaxScaler()
    if skema == "standard":
        return StandardScaler()
    raise ValueError(f"Skema penskalaan tidak dikenal: '{skema}' (pakai 'standard' atau 'minmax').")
