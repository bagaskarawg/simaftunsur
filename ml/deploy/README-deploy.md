# Deploy Service Klasterisasi — Laravel Forge (zero-downtime)

Service Python (`ml/`) berjalan di VPS yang dikelola **Laravel Forge**, di site
`k-means-service.bahas.tech`, dan dipanggil aplikasi Laravel (cPanel) via HTTPS.

```
Laravel (cPanel, https) ──X-API-Key──▶ https://k-means-service.bahas.tech ──▶ uvicorn 127.0.0.1:8001
   KlasterisasiService              nginx + TLS (Forge)         systemd: simaftunsur-ml
```

Struktur direktori Forge (zero-downtime):
```
/home/forge/k-means-service.bahas.tech/
├── current -> releases/<x>        # berganti tiap deploy
├── releases/
└── shared/                        # PERSISTEN lintas rilis
    ├── ml-venv/                    # virtualenv Python
    └── ml.env                     # ML_API_KEY
```

Prinsip: kode ikut rilis (`current/ml`), tapi **venv & kunci di `shared/`** agar
tidak hilang tiap deploy.

---

## 1. Site di Forge
- Buat/siapkan site **k-means-service.bahas.tech**, aktifkan **Zero Downtime
  Deployment** (menghasilkan `current`+`releases`+`shared`).
- Hubungkan ke repo git yang sama (branch `main`). `ml/` adalah subfolder repo,
  sehingga kode service berada di `current/ml`.
- Pastikan DNS A record `k-means-service.bahas.tech` → IP server (biasanya sudah,
  karena Forge yang mengelola).

## 2. Prasyarat sistem (sekali, via SSH sebagai forge)
```bash
sudo apt update && sudo apt install -y python3-venv python3-pip
```
(nginx sudah dipasang Forge.)

## 3. Kunci API (shared secret) — di shared/
```bash
openssl rand -hex 32                      # SALIN hasilnya
SITE=/home/forge/k-means-service.bahas.tech
cp "$SITE/current/ml/deploy/simaftunsur-ml.env.example" "$SITE/shared/ml.env"
nano "$SITE/shared/ml.env"                 # isi ML_API_KEY = hasil di atas
chmod 600 "$SITE/shared/ml.env"
```
> Kunci yang sama nanti dipasang di `.env` Laravel (cPanel) sebagai `ML_API_KEY`.

## 4. systemd service
```bash
SITE=/home/forge/k-means-service.bahas.tech
sudo cp "$SITE/current/ml/deploy/simaftunsur-ml.service" /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable simaftunsur-ml
# venv belum ada sebelum deploy pertama; deploy script (langkah 5) yang membuatnya.
```

## 4b. Izinkan forge me-restart service tanpa password (WAJIB)
Deploy script berjalan non-interaktif, jadi `sudo systemctl restart` akan gagal
("a password is required") kecuali user `forge` diberi sudoers khusus:
```bash
sudo tee /etc/sudoers.d/simaftunsur-ml >/dev/null <<'EOF'
forge ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart simaftunsur-ml, /usr/bin/systemctl start simaftunsur-ml, /usr/bin/systemctl stop simaftunsur-ml, /usr/bin/systemctl status simaftunsur-ml
EOF
sudo chmod 440 /etc/sudoers.d/simaftunsur-ml
sudo visudo -cf /etc/sudoers.d/simaftunsur-ml     # harus: parsed OK
which systemctl                                    # pastikan /usr/bin/systemctl
sudo -n systemctl restart simaftunsur-ml          # uji: tidak minta password
```

## 5. Deploy Script Forge
Buka **Forge → site → Deploy Script**. Hapus baris composer/artisan bawaan
(site ini bukan app Laravel), sisakan bagian git pull Forge, lalu tempel isi
`ml/deploy/forge-deploy-script.sh` di bawahnya. Inti yang dijalankan tiap deploy:
```bash
SITE=/home/forge/k-means-service.bahas.tech
VENV="$SITE/shared/ml-venv"
[ -d "$VENV" ] || python3 -m venv "$VENV"
"$VENV/bin/pip" install -q --upgrade pip
"$VENV/bin/pip" install -q -r "$SITE/current/ml/requirements.txt"
sudo systemctl restart simaftunsur-ml
```
Klik **Deploy Now**. Setelah selesai, cek:
```bash
sudo systemctl status simaftunsur-ml       # active (running)
curl -s http://127.0.0.1:8001/sehat        # {"status":"ok",...}
```

## 6. nginx + SSL (via UI Forge)
- **Forge → site → Edit Nginx Configuration**: ganti isi blok `location / { … }`
  bawaan dengan blok proxy di `ml/deploy/nginx-ml.conf`. Simpan (Forge auto
  `nginx -t` + reload).
- **Forge → site → SSL → Let's Encrypt**: terbitkan sertifikat.

Uji dari luar:
```bash
curl -s https://k-means-service.bahas.tech/sehat            # 200 ok
curl -s -X POST https://k-means-service.bahas.tech/klasterisasi   # 401 (auth aktif)
```

## 7. Sisi Laravel (cPanel `.env`)
```
ML_BASE_URL=https://k-means-service.bahas.tech
ML_API_KEY=<kunci_hex_yang_sama_dengan_langkah_3>
ML_TIMEOUT=120
```
Bersihkan cache config (hapus `bootstrap/cache/config.php` atau `optimize:clear`),
lalu buka halaman Klasterisasi → Jalankan.

## Update berikutnya
Cukup **Deploy Now** di Forge — deploy script otomatis pip install + restart.
Ubah versi Python? Perbaiki di server lalu hapus `shared/ml-venv` agar dibuat ulang.

## Troubleshooting
- `journalctl -u simaftunsur-ml -n 50` — log service.
- 502 dari nginx → uvicorn mati; `systemctl status simaftunsur-ml`.
- 401 saat Laravel memanggil → `ML_API_KEY` beda antara `shared/ml.env` (VPS)
  dan `.env` (cPanel).
- `sudo` minta password di deploy script ("a password is required") → langkah
  sudoers (4b) belum dipasang, atau path systemctl beda (`which systemctl`).
- Python 3.12 disarankan (wheel scikit-learn paling stabil). Cek `python3 --version`.
