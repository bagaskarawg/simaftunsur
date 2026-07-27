"""Konfigurasi bersama pytest untuk service klasterisasi.

Menyediakan akses ke paket `pipeline` dan ke data emas BAB III
(tests/fixtures/golden_bab3.json di root proyek).
"""
import json
import sys
from pathlib import Path

import numpy as np
import pytest

ML_DIR = Path(__file__).resolve().parents[1]
if str(ML_DIR) not in sys.path:
    sys.path.insert(0, str(ML_DIR))

GOLDEN_PATH = ML_DIR.parent / "tests" / "fixtures" / "golden_bab3.json"

# Fitur akademik yang dipakai contoh emas BAB III (4 fitur, bukan 7).
FITUR_AKADEMIK = ["ipk_rata_rata", "ipk_terakhir", "tren", "konsistensi"]


@pytest.fixture(scope="session")
def golden() -> dict:
    """Muatan mentah data emas."""
    with open(GOLDEN_PATH, encoding="utf-8") as fh:
        return json.load(fh)


@pytest.fixture(scope="session")
def golden_data(golden) -> list[dict]:
    """Data siap-pipeline: id + 4 fitur akademik per mahasiswa (id 1..9 = M1..M9)."""
    return [
        {"id": i + 1, **{k: m[k] for k in FITUR_AKADEMIK}}
        for i, m in enumerate(golden["mahasiswa"])
    ]


@pytest.fixture(scope="session")
def golden_matriks(golden):
    """(kode, kolom, X) — matriks 9x4 fitur akademik pada SKALA ASLI."""
    kode = [m["kode"] for m in golden["mahasiswa"]]
    X = np.array(
        [[m[k] for k in FITUR_AKADEMIK] for m in golden["mahasiswa"]], dtype=float
    )
    return kode, FITUR_AKADEMIK, X
