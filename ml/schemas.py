"""
Skema permintaan & tanggapan API klasterisasi (Pydantic v2).

Menjadi kontrak data antara Laravel (klien) dan service Python. Bila kolom
di sini berubah, sesuaikan pula App\\Services\\KlasterisasiService di Laravel.
"""

from __future__ import annotations

from pydantic import BaseModel, Field


class MahasiswaFitur(BaseModel):
    """Satu baris mahasiswa beserta fitur turunannya."""

    id: int = Field(..., description="ID mahasiswa di basis data Laravel")
    npm: str | None = Field(None, description="Nomor Pokok Mahasiswa")
    nama: str | None = Field(None, description="Nama mahasiswa (untuk tampilan)")
    ipk_rata_rata: float = Field(0.0, ge=0.0, le=4.0)
    ipk_terakhir: float = Field(0.0, ge=0.0, le=4.0)
    tren: float = Field(0.0, description="Slope tren IPK antar-semester")
    konsistensi: float = Field(0.0, ge=0.0, description="Standar deviasi IPK")
    semester_aktif: int = Field(1, ge=1, le=14)
    program_studi: str | None = Field(None, description="Kode prodi, mis. TIF")


class PermintaanKlasterisasi(BaseModel):
    """Badan permintaan POST /klasterisasi."""

    data: list[MahasiswaFitur] = Field(..., min_length=1)
    fitur: list[str] | None = Field(
        None,
        description="Atribut yang dipakai; None = default (IPK rata, terakhir, "
        "tren, konsistensi, semester_aktif).",
    )
    k: int | None = Field(
        None, ge=2, description="Jumlah klaster; None = ditentukan otomatis."
    )
    k_min: int = Field(2, ge=2)
    k_max: int = Field(8, ge=2)
    skema_penskalaan: str = Field("standard", pattern="^(standard|minmax)$")


class MetrikEvaluasi(BaseModel):
    inertia: float
    silhouette: float | None
    davies_bouldin: float | None


class BarisEvaluasiK(BaseModel):
    k: int
    inertia: float
    silhouette: float
    davies_bouldin: float


class ProfilKlaster(BaseModel):
    cluster: int
    jumlah: int
    centroid: dict[str, float]
    label_deskriptif: str


class TitikHasil(BaseModel):
    id: int
    cluster: int
    pca_x: float
    pca_y: float


class TanggapanKlasterisasi(BaseModel):
    """Badan tanggapan POST /klasterisasi."""

    k_terpilih: int
    metode_pemilihan_k: str
    fitur_dipakai: list[str]
    skema_penskalaan: str
    jumlah_data: int
    metrik: MetrikEvaluasi
    evaluasi_k: list[BarisEvaluasiK]
    profil_klaster: list[ProfilKlaster]
    hasil: list[TitikHasil]
    peringatan: list[str]
