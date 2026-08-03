"""
Service REST API klasterisasi K-Means SIMAFTUNSUR (FastAPI).

Menjalankan:
    uvicorn api:app --host 127.0.0.1 --port 8001 --reload

Endpoint:
    GET  /sehat        -> cek kesehatan service.
    POST /klasterisasi -> jalankan klasterisasi, kembalikan hasil + metrik.

Service ini SENGAJA dijalankan terpisah dari Laravel (lihat keputusan
arsitektur: "Python service via REST API"). Laravel memanggilnya via HTTP
dari App\\Services\\KlasterisasiService.
"""

from __future__ import annotations

import os
import secrets

from fastapi import Depends, FastAPI, Header, HTTPException

from pipeline import jalankan_klasterisasi
from schemas import PermintaanKlasterisasi, TanggapanKlasterisasi

app = FastAPI(
    title="SIMAFTUNSUR — Service Klasterisasi K-Means",
    description="Klasterisasi profil mahasiswa FT UNSUR (unsupervised).",
    version="1.0.0",
)

# Kunci API bersama (shared secret) antara Laravel dan service ini.
# Diambil dari environment; bila KOSONG, autentikasi dinonaktifkan
# (mode pengembangan lokal). Di server WAJIB diisi.
KUNCI_API = os.getenv("ML_API_KEY", "").strip()


def verifikasi_kunci(x_api_key: str | None = Header(default=None)) -> None:
    """
    Dependency FastAPI: verifikasi header `X-API-Key` terhadap ML_API_KEY.

    - Bila ML_API_KEY tidak diset (lokal), pemeriksaan dilewati.
    - Perbandingan memakai compare_digest agar aman dari timing attack.
    """
    if not KUNCI_API:
        return
    if not x_api_key or not secrets.compare_digest(x_api_key, KUNCI_API):
        raise HTTPException(status_code=401, detail="Kunci API tidak valid.")


@app.get("/sehat")
def sehat() -> dict:
    """Cek kesehatan service (dipanggil Laravel sebelum mengirim data)."""
    return {"status": "ok", "service": "klasterisasi-kmeans"}


@app.post(
    "/klasterisasi",
    response_model=TanggapanKlasterisasi,
    dependencies=[Depends(verifikasi_kunci)],
)
def klasterisasi(permintaan: PermintaanKlasterisasi) -> dict:
    """
    Jalankan pipeline klasterisasi atas data mahasiswa yang dikirim Laravel.

    Galat domain (mis. data terlalu sedikit, fitur tidak valid) dikembalikan
    sebagai HTTP 422 dengan pesan Bahasa Indonesia agar mudah ditampilkan
    di antarmuka.
    """
    try:
        return jalankan_klasterisasi(
            data=[m.model_dump() for m in permintaan.data],
            fitur=permintaan.fitur,
            k=permintaan.k,
            k_min=permintaan.k_min,
            k_max=permintaan.k_max,
            skema_penskalaan=permintaan.skema_penskalaan,
            konfigurasi_label=permintaan.konfigurasi_label,
            n_bootstrap=permintaan.n_bootstrap,
        )
    except ValueError as galat:
        raise HTTPException(status_code=422, detail=str(galat)) from galat
