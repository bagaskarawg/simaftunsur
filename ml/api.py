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

from fastapi import FastAPI, HTTPException

from pipeline import jalankan_klasterisasi
from schemas import PermintaanKlasterisasi, TanggapanKlasterisasi

app = FastAPI(
    title="SIMAFTUNSUR — Service Klasterisasi K-Means",
    description="Klasterisasi profil mahasiswa FT UNSUR (unsupervised).",
    version="1.0.0",
)


@app.get("/sehat")
def sehat() -> dict:
    """Cek kesehatan service (dipanggil Laravel sebelum mengirim data)."""
    return {"status": "ok", "service": "klasterisasi-kmeans"}


@app.post("/klasterisasi", response_model=TanggapanKlasterisasi)
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
