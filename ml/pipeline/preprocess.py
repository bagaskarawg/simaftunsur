"""
Tahap pra-pemrosesan data mahasiswa sebelum klasterisasi.

Menangani: validasi struktur, penanganan nilai kosong (missing value),
pembatasan outlier pada rentang IPK yang sah, serta normalisasi tipe data.
Encoding atribut kategorikal (mis. program studi) ditangani terpisah di
tahap feature_engineering agar penskalaan & encoding terpusat di satu tempat.
"""

from __future__ import annotations

import pandas as pd

# Kolom identitas yang dibawa apa adanya (tidak ikut dihitung sebagai fitur).
KOLOM_IDENTITAS = ["id", "npm", "nama"]

# Batas sah IPK pada skala 4,00. Nilai di luar ini dianggap galat input
# dan dijepit (clip) ke batas terdekat, bukan dibuang, agar baris mahasiswa
# tetap utuh.
IPK_MIN = 0.0
IPK_MAKS = 4.0

# Kolom numerik yang mungkin muncul dan perlu dijaga rentangnya.
KOLOM_IPK = ["ipk_rata_rata", "ipk_terakhir"]


def preprocess(data: list[dict]) -> pd.DataFrame:
    """
    Ubah data mentah (daftar dict dari Laravel) menjadi DataFrame bersih.

    Parameter
    ---------
    data : list[dict]
        Tiap elemen mewakili satu mahasiswa beserta fitur turunannya
        (ipk_rata_rata, ipk_terakhir, tren, konsistensi, semester_aktif,
        program_studi, dan identitas id/npm/nama).

    Mengembalikan
    -------------
    pandas.DataFrame
        Data terurut berdasarkan `id` (bila ada) dengan nilai kosong terisi
        dan IPK terjepit pada rentang [0, 4].

    Memunculkan
    -----------
    ValueError
        Bila data kosong atau seluruh baris gugur saat validasi.
    """
    if not data:
        raise ValueError("Data klasterisasi kosong: tidak ada mahasiswa untuk diproses.")

    df = pd.DataFrame(data)

    # Buang baris tanpa identitas inti (id) — tidak dapat dipetakan balik
    # ke basis data Laravel sehingga tidak berguna untuk disimpan.
    if "id" in df.columns:
        df = df[df["id"].notna()].copy()

    if df.empty:
        raise ValueError("Seluruh baris gugur pada validasi: tidak ada `id` valid.")

    # Penanganan nilai kosong pada fitur numerik:
    #   - tren & konsistensi: 0.0 (mahasiswa dengan <2 catatan IPK dianggap
    #     belum punya kecenderungan/variasi).
    #   - ipk_*: 0.0 sebagai penanda "belum ada nilai" (akan tampak sebagai
    #     klaster tersendiri bila dominan; ini jujur, bukan disembunyikan).
    nilai_default = {
        "ipk_rata_rata": 0.0,
        "ipk_terakhir": 0.0,
        "tren": 0.0,
        "konsistensi": 0.0,
        "skor_prestasi": 0.0,
        "skor_kegiatan": 0.0,
        "skor_pengabdian": 0.0,
        "semester_aktif": 1,
    }
    for kolom, default in nilai_default.items():
        if kolom in df.columns:
            df[kolom] = pd.to_numeric(df[kolom], errors="coerce").fillna(default)

    # Pembatasan outlier IPK ke rentang sah skala 4,00.
    for kolom in KOLOM_IPK:
        if kolom in df.columns:
            df[kolom] = df[kolom].clip(lower=IPK_MIN, upper=IPK_MAKS)

    # Semester aktif wajib bilangan bulat positif.
    if "semester_aktif" in df.columns:
        df["semester_aktif"] = (
            pd.to_numeric(df["semester_aktif"], errors="coerce")
            .fillna(1)
            .clip(lower=1)
            .astype(int)
        )

    # Program studi sebagai kategori string; kosong → "TIDAK_DIKETAHUI".
    if "program_studi" in df.columns:
        df["program_studi"] = (
            df["program_studi"].astype("string").fillna("TIDAK_DIKETAHUI")
        )

    if "id" in df.columns:
        df = df.sort_values("id").reset_index(drop=True)

    return df
