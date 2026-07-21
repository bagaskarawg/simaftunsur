"""
Uji mandiri pipeline klasterisasi TANPA menjalankan API.

Membangkitkan data sintetik bertanda jelas (3 kelompok laten) lalu menjalankan
pipeline ujung-ke-ujung dan mencetak ringkasan. Berguna untuk memverifikasi
logika pipeline di mesin mana pun:

    python uji_pipeline.py

CATATAN KEJUJURAN: data di sini SINTETIK untuk uji teknis belaka. Angka metrik
yang muncul TIDAK boleh dikutip sebagai hasil penelitian — hasil ilmiah hanya
dari data IPK riil FT UNSUR.
"""

from __future__ import annotations

import json

import numpy as np

from pipeline import jalankan_klasterisasi


def bangkitkan_data_sintetik(jumlah: int = 120, seed: int = 42) -> list[dict]:
    """Buat data uji dengan 3 kelompok laten (tinggi/menengah/rendah)."""
    rng = np.random.default_rng(seed)
    pusat = [
        {"ipk": 3.7, "tren": 0.08, "kons": 0.10, "skor": 120},  # berprestasi
        {"ipk": 3.1, "tren": 0.00, "kons": 0.18, "skor": 50},   # menengah
        {"ipk": 2.5, "tren": -0.06, "kons": 0.30, "skor": 10},  # perlu pembinaan
    ]
    prodi = ["TIF", "TSI", "TMI", "TID"]

    def skor(basis: float) -> float:
        """Skor SKKM non-negatif di sekitar basis kelompok laten."""
        return round(max(0.0, float(rng.normal(basis, basis * 0.35))), 0)

    data: list[dict] = []
    for i in range(jumlah):
        g = pusat[i % 3]
        ipk = float(np.clip(rng.normal(g["ipk"], 0.18), 0, 4))
        data.append(
            {
                "id": i + 1,
                "npm": f"552011{9000 + i}",
                "nama": f"Mahasiswa {i + 1}",
                "ipk_rata_rata": round(ipk, 2),
                "ipk_terakhir": round(float(np.clip(ipk + rng.normal(0, 0.1), 0, 4)), 2),
                "tren": round(float(rng.normal(g["tren"], 0.03)), 4),
                "konsistensi": round(abs(float(rng.normal(g["kons"], 0.05))), 4),
                "skor_prestasi": skor(g["skor"]),
                "skor_kegiatan": skor(g["skor"] * 0.6),
                "skor_pengabdian": skor(g["skor"] * 0.4),
                "semester_aktif": int(rng.integers(3, 8)),
                "program_studi": prodi[i % len(prodi)],
            }
        )
    return data


def main() -> None:
    data = bangkitkan_data_sintetik()
    hasil = jalankan_klasterisasi(data, k=None, k_min=2, k_max=6)

    print("=== RINGKASAN HASIL (DATA SINTETIK — BUKAN HASIL PENELITIAN) ===")
    print(f"k terpilih        : {hasil['k_terpilih']} ({hasil['metode_pemilihan_k']})")
    print(f"Jumlah data       : {hasil['jumlah_data']}")
    print(f"Fitur dipakai     : {hasil['fitur_dipakai']}")
    print(f"Metrik            : {json.dumps(hasil['metrik'], indent=2)}")
    print("\nProfil klaster:")
    for p in hasil["profil_klaster"]:
        print(
            f"  - Klaster {p['cluster']} [{p['label_deskriptif']}] "
            f"({p['jumlah']} mhs) centroid={p['centroid']}"
        )
    print("\nTabel evaluasi per-k (Elbow/Silhouette):")
    for b in hasil["evaluasi_k"]:
        print(
            f"  k={b['k']}: inertia={b['inertia']:.2f} "
            f"silhouette={b['silhouette']:.4f} DBI={b['davies_bouldin']:.4f}"
        )

    stab = hasil.get("stabilitas")
    if stab:
        print(
            f"\nStabilitas (bootstrap-Jaccard, B={stab['n_bootstrap']}): "
            f"rata={stab['rata_rata']:.4f} min={stab['minimum']:.4f} "
            f"-> {stab['kategori_keseluruhan']}"
        )
        for pk in stab["per_klaster"]:
            print(f"  Klaster {pk['cluster']}: {pk['jaccard']:.4f} ({pk['kategori']})")

    beda = hasil.get("uji_beda")
    if beda:
        print(
            f"\nUji beda antar-klaster ({beda['metode']}, alpha={beda['alpha']}): "
            f"{beda['jumlah_fitur_signifikan']}/{beda['jumlah_fitur']} fitur signifikan"
        )
        for r in beda["per_fitur"]:
            tanda = "signifikan" if r["signifikan"] else "tidak"
            print(
                f"  {r['fitur']}: H={r['statistik_h']:.4f} "
                f"p={r['p_value']:.6f} ({tanda})"
            )

    if hasil["peringatan"]:
        print("\nPeringatan:")
        for w in hasil["peringatan"]:
            print(f"  ! {w}")
    print("\nOK: pipeline berjalan ujung-ke-ujung.")


if __name__ == "__main__":
    main()
