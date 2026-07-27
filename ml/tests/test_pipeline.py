"""Pipeline ujung-ke-ujung pada data emas: partisi benar (label-invariant),
Elbow memilih k=3, dan hasil deterministik (random_state tetap)."""
from pipeline import jalankan_klasterisasi


def _partisi(hasil) -> set:
    """Himpunan anggota tiap klaster (label-invariant)."""
    grup: dict = {}
    for titik in hasil["hasil"]:
        grup.setdefault(titik["cluster"], set()).add(titik["id"])
    return {frozenset(s) for s in grup.values()}


def _partisi_harapan(golden) -> set:
    # 'M1' -> id 1, dst.
    return {frozenset(int(k[1:]) for k in anggota) for anggota in golden["partisi_k3"].values()}


def test_pipeline_partisi_sesuai_emas(golden, golden_data):
    hasil = jalankan_klasterisasi(golden_data, fitur=golden["fitur_akademik"], k=3, n_bootstrap=0)
    assert _partisi(hasil) == _partisi_harapan(golden)


def test_pipeline_elbow_memilih_k3(golden, golden_data):
    hasil = jalankan_klasterisasi(
        golden_data, fitur=golden["fitur_akademik"], k=None, k_min=2, k_max=6, n_bootstrap=0
    )
    assert hasil["k_terpilih"] == golden["k_otomatis_elbow"] == 3


def test_pipeline_deterministik(golden, golden_data):
    a = jalankan_klasterisasi(golden_data, fitur=golden["fitur_akademik"], k=3, n_bootstrap=0)
    b = jalankan_klasterisasi(golden_data, fitur=golden["fitur_akademik"], k=3, n_bootstrap=0)
    assert _partisi(a) == _partisi(b)
