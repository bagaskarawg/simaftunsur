# Tambahkan blok ini ke DEPLOY SCRIPT Forge
# (Forge → Site k-means-service.bahas.tech → tab "Deploy Script").
#
# Karena site ini HANYA menjalankan service Python (bukan app Laravel),
# hapus baris composer/artisan bawaan Forge; sisakan bagian git pull Forge
# (baris paling atas yang di-generate Forge) lalu tambahkan blok di bawah.
#
# PENTING: Forge menjalankan script ini NON-INTERAKTIF, jadi 'sudo' tidak bisa
# meminta password. User 'forge' TIDAK otomatis passwordless sudo — WAJIB pasang
# aturan sudoers dulu (lihat README-deploy.md langkah "sudoers"), jika tidak
# 'sudo systemctl restart' akan gagal: "a password is required".

SITE=/home/forge/k-means-service.bahas.tech
VENV="$SITE/shared/ml-venv"

# 1) Buat virtualenv di shared/ (sekali; persisten lintas rilis).
if [ ! -d "$VENV" ]; then
    python3 -m venv "$VENV"
fi

# 2) Pasang/segarkan dependensi dari requirements rilis terbaru.
"$VENV/bin/pip" install -q --upgrade pip
"$VENV/bin/pip" install -q -r "$SITE/current/ml/requirements.txt"

# 3) Restart service agar memakai kode rilis terbaru (current sudah di-flip).
sudo systemctl restart simaftunsur-ml
