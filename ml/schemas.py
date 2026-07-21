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
    # Skor non-akademik SKKM (fitur F5–F7) — total poin objektif.
    skor_prestasi: float = Field(0.0, ge=0.0, description="Total poin prestasi/kejuaraan (F5)")
    skor_kegiatan: float = Field(0.0, ge=0.0, description="Total poin kegiatan & organisasi (F6)")
    skor_pengabdian: float = Field(0.0, ge=0.0, description="Total poin pengabdian & hibah (F7)")
    semester_aktif: int = Field(1, ge=1, le=14)
    program_studi: str | None = Field(None, description="Kode prodi, mis. TIF")


class PermintaanKlasterisasi(BaseModel):
    """Badan permintaan POST /klasterisasi."""

    data: list[MahasiswaFitur] = Field(..., min_length=1)
    fitur: list[str] | None = Field(
        None,
        description="Atribut yang dipakai; None = default 7 fitur SKKM (IPK rata, "
        "terakhir, tren, konsistensi, skor_prestasi, skor_kegiatan, skor_pengabdian).",
    )
    k: int | None = Field(
        None, ge=2, description="Jumlah klaster; None = ditentukan otomatis."
    )
    k_min: int = Field(2, ge=2)
    k_max: int = Field(8, ge=2)
    skema_penskalaan: str = Field("standard", pattern="^(standard|minmax)$")
    n_bootstrap: int = Field(
        100,
        ge=0,
        le=500,
        description="Iterasi bootstrap untuk uji stabilitas Jaccard; 0 = lewati.",
    )
    konfigurasi_label: dict | None = Field(
        None,
        description="Bobot/arah fitur + katalog nama label (dari Laravel). "
        "None = pakai KONFIG_LABEL_DEFAULT bawaan service.",
    )


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
    # Centroid pada ruang terskala yang dipakai KMeans (untuk audit/tracing).
    centroid_terskala: dict[str, float] | None = None
    # Skor komposit multi-fitur (dasar penentuan label) + deskripsi kualitatif.
    skor_akademik: float | None = None
    skor_non_akademik: float | None = None
    skor_komposit: float | None = None
    ringkasan_profil: str | None = None


class TitikHasil(BaseModel):
    id: int
    cluster: int
    pca_x: float
    pca_y: float
    # Data keterlacakan (tracing) — dasar penempatan tiap mahasiswa.
    fitur_terskala: dict[str, float] | None = None
    jarak_ke_centroid: float | None = None


class JaccardKlaster(BaseModel):
    cluster: int
    jaccard: float
    kategori: str


class HasilStabilitas(BaseModel):
    """Uji stabilitas klaster via bootstrap-Jaccard (PELENGKAP)."""

    metode: str
    n_bootstrap: int
    ambang_stabil: float
    ambang_minimum: float
    per_klaster: list[JaccardKlaster]
    rata_rata: float
    minimum: float
    kategori_keseluruhan: str


class BarisUjiBeda(BaseModel):
    fitur: str
    statistik_h: float
    p_value: float
    signifikan: bool


class HasilUjiBeda(BaseModel):
    """Uji beda antar-klaster via Kruskal-Wallis H (PELENGKAP)."""

    metode: str
    alpha: float
    per_fitur: list[BarisUjiBeda]
    jumlah_fitur: int
    jumlah_fitur_signifikan: int


class TanggapanKlasterisasi(BaseModel):
    """Badan tanggapan POST /klasterisasi."""

    k_terpilih: int
    metode_pemilihan_k: str
    fitur_dipakai: list[str]
    skema_penskalaan: str
    random_state: int | None = None
    versi_algoritma: str | None = None
    jumlah_data: int
    metrik: MetrikEvaluasi
    evaluasi_k: list[BarisEvaluasiK]
    profil_klaster: list[ProfilKlaster]
    hasil: list[TitikHasil]
    # Validasi lanjutan (PELENGKAP) — None bila tak terdefinisi (mis. 1 klaster).
    stabilitas: HasilStabilitas | None = None
    uji_beda: HasilUjiBeda | None = None
    peringatan: list[str]
