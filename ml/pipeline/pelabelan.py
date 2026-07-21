"""
Pelabelan klaster berbasis SKOR KOMPOSIT MULTI-FITUR.

Menggantikan pelabelan lama yang hanya memeringkat klaster dari rata-rata IPK.
Di sini label ditentukan dari gabungan berbobot fitur AKADEMIK (IPK rata-rata,
IPK terakhir, tren, konsistensi) DAN NON-AKADEMIK (prestasi, kegiatan,
pengabdian). Dengan begitu mahasiswa ber-IPK tinggi namun tanpa aktivitas /
prestasi tidak otomatis tergolong "Berprestasi" secara menyeluruh — sesuai
kebutuhan WD III (mis. penilaian kelayakan beasiswa yang tidak semata IPK).

Dasar perhitungan: CENTROID TERSKALA (z-score dari StandardScaler). Tiap nilai
centroid = rata-rata z-score fitur pada klaster, sehingga fitur beda satuan
(IPK 0–4 vs poin SKKM) sudah setara dan boleh dijumlahkan berbobot. Nilai
komposit di sekitar 0 = rata-rata populasi; positif = di atas rata-rata;
negatif = di bawah rata-rata.

Seluruh bobot & arah TERKONFIGURASI (KONFIG_LABEL_DEFAULT) agar mudah ditinjau
pimpinan dan — ke depan — dikelola lewat CRUD "Kategori Klaster" di Laravel.

Catatan kejujuran: label & skor ini alat bantu interpretasi berbasis aturan
yang transparan, BUKAN klaim ilmiah. Karakteristik sebenarnya tetap dibaca
dari centroid tiap fitur.
"""

from __future__ import annotations

# arah: +1 = makin tinggi makin baik; -1 = makin tinggi makin buruk.
#   (konsistensi = standar deviasi IPK → makin KECIL makin stabil → arah -1)
# bobot: kontribusi relatif fitur DI DALAM dimensinya (dinormalisasi otomatis).
# bobot dimensi: porsi blok akademik vs non-akademik pada skor komposit.
KONFIG_LABEL_DEFAULT: dict = {
    "dimensi": {
        "akademik": {
            "bobot": 0.5,
            "fitur": {
                "ipk_rata_rata": {"bobot": 0.40, "arah": 1},
                "ipk_terakhir": {"bobot": 0.30, "arah": 1},
                "tren": {"bobot": 0.20, "arah": 1},
                "konsistensi": {"bobot": 0.10, "arah": -1},
            },
        },
        "non_akademik": {
            "bobot": 0.5,
            "fitur": {
                "skor_prestasi": {"bobot": 0.50, "arah": 1},
                "skor_kegiatan": {"bobot": 0.30, "arah": 1},
                "skor_pengabdian": {"bobot": 0.20, "arah": 1},
            },
        },
    },
    # Nama label per peringkat komposit (indeks 0 = komposit tertinggi).
    "katalog": ["Berprestasi", "Menengah", "Perlu Bimbingan"],
    # Ambang z-score untuk deskripsi kualitatif sub-dimensi.
    "ambang_tinggi": 0.25,
    "ambang_rendah": -0.25,
}


def gabung_konfig(konfig: dict | None) -> dict:
    """
    Gabungkan konfigurasi (mungkin parsial, mis. hanya `katalog` dari Laravel)
    dengan KONFIG_LABEL_DEFAULT sehingga seluruh kunci (dimensi/katalog/ambang)
    selalu tersedia. Mencegah KeyError bila klien mengirim sebagian saja.
    """
    if not konfig:
        return KONFIG_LABEL_DEFAULT
    return {
        "dimensi": konfig.get("dimensi") or KONFIG_LABEL_DEFAULT["dimensi"],
        "katalog": konfig.get("katalog") or KONFIG_LABEL_DEFAULT["katalog"],
        "ambang_tinggi": konfig.get("ambang_tinggi", KONFIG_LABEL_DEFAULT["ambang_tinggi"]),
        "ambang_rendah": konfig.get("ambang_rendah", KONFIG_LABEL_DEFAULT["ambang_rendah"]),
    }


def _skor_dimensi(centroid_terskala: dict, fitur_konfig: dict) -> float:
    """Rata-rata BERBOBOT dari z-score bertanda (arah) untuk satu dimensi."""
    total_bobot = 0.0
    total = 0.0
    for nama, cfg in fitur_konfig.items():
        if nama in centroid_terskala:
            bobot = float(cfg.get("bobot", 0.0))
            arah = float(cfg.get("arah", 1))
            total += bobot * arah * float(centroid_terskala[nama])
            total_bobot += bobot
    return total / total_bobot if total_bobot else 0.0


def hitung_skor(centroid_terskala: dict | None, konfig: dict | None = None) -> dict:
    """
    Skor komposit satu klaster dari centroid terskalanya.

    Mengembalikan {skor_akademik, skor_non_akademik, skor_komposit}.
    """
    konfig = konfig or KONFIG_LABEL_DEFAULT
    centroid_terskala = centroid_terskala or {}
    dim = konfig["dimensi"]

    s_aka = _skor_dimensi(centroid_terskala, dim["akademik"]["fitur"])
    s_non = _skor_dimensi(centroid_terskala, dim["non_akademik"]["fitur"])

    w_aka = float(dim["akademik"].get("bobot", 0.5))
    w_non = float(dim["non_akademik"].get("bobot", 0.5))
    total_w = (w_aka + w_non) or 1.0
    komposit = (w_aka * s_aka + w_non * s_non) / total_w

    return {
        "skor_akademik": round(s_aka, 4),
        "skor_non_akademik": round(s_non, 4),
        "skor_komposit": round(komposit, 4),
    }


def _mutu(nilai: float, konfig: dict) -> str:
    if nilai >= konfig.get("ambang_tinggi", 0.25):
        return "tinggi"
    if nilai <= konfig.get("ambang_rendah", -0.25):
        return "rendah"
    return "sedang"


def deskripsi_profil(
    skor_akademik: float, skor_non_akademik: float, konfig: dict | None = None
) -> str:
    """Deskripsi kualitatif ringkas berdasarkan dua sub-dimensi (transparansi)."""
    konfig = konfig or KONFIG_LABEL_DEFAULT
    a = _mutu(skor_akademik, konfig)
    n = _mutu(skor_non_akademik, konfig)
    peta = {
        ("tinggi", "tinggi"): "Akademik & non-akademik sama-sama menonjol.",
        ("tinggi", "sedang"): "Akademik menonjol; aktivitas non-akademik memadai.",
        ("tinggi", "rendah"): "Unggul akademik, tetapi minim prestasi/kegiatan non-akademik.",
        ("sedang", "tinggi"): "Aktif & berprestasi non-akademik; akademik menengah.",
        ("sedang", "sedang"): "Profil menengah pada akademik maupun non-akademik.",
        ("sedang", "rendah"): "Akademik menengah; aktivitas non-akademik rendah.",
        ("rendah", "tinggi"): "Aktif non-akademik; akademik perlu perhatian.",
        ("rendah", "sedang"): "Akademik rendah; aktivitas non-akademik memadai.",
        ("rendah", "rendah"): "Perlu perhatian menyeluruh (akademik & non-akademik rendah).",
    }
    return peta[(a, n)]


def nama_label(peringkat: int, total: int, konfig: dict | None = None) -> str:
    """
    Nama label dari katalog berdasarkan peringkat skor komposit
    (0 = komposit tertinggi).
    """
    konfig = konfig or KONFIG_LABEL_DEFAULT
    katalog = konfig.get("katalog") or ["Berprestasi", "Menengah", "Perlu Bimbingan"]
    if total <= 1:
        return "Klaster Tunggal"
    if peringkat == 0:
        return katalog[0]
    if peringkat == total - 1:
        return katalog[-1]
    if 0 < peringkat < len(katalog) - 1:
        return katalog[peringkat]
    return f"Menengah (tingkat {peringkat})"
