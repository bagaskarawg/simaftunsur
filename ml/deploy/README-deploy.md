# Deploy Service Klasterisasi ke VPS (DigitalOcean)

Panduan menempatkan service Python (`ml/`) di VPS terpisah, diakses oleh
aplikasi Laravel (di cPanel) via HTTPS. Shared hosting tidak cocok untuk
proses Python persisten, sehingga service ini berdiri sendiri.

```
Laravel (cPanel, https)  ──HTTPS──▶  https://ml.domainmu  ──▶  uvicorn 127.0.0.1:8001
      KlasterisasiService              nginx + TLS (VPS)          systemd: simaftunsur-ml
      kirim header X-API-Key
```

Arsitektur: uvicorn hanya dengar di `127.0.0.1`; nginx yang menghadap publik
dengan TLS; autentikasi via shared secret `X-API-Key`.

---

## 0. Prasyarat
- Droplet DigitalOcean (Ubuntu 22.04/24.04 LTS), akses `root`/sudo.
- Subdomain, mis. `ml.domainmu`, dengan **A record → IP droplet** (atur di DNS).
- Firewall DO/ufw mengizinkan port 80 & 443 (JANGAN buka 8001 ke publik).

## 1. Paket dasar
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y python3-venv python3-pip nginx
# firewall (opsional tapi disarankan)
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

## 2. User khusus + kode service
```bash
sudo useradd -r -m -d /opt/simaftunsur-ml -s /usr/sbin/nologin simaftunsur
```
Salin isi folder `ml/` proyek ke `/opt/simaftunsur-ml/` (via `git clone`
lalu copy subfolder `ml/`, atau `scp`). Yang WAJIB ada: `api.py`, `schemas.py`,
`requirements.txt`, dan folder `pipeline/`. `.venv/`, `tests/`, cache TIDAK perlu.

```bash
# contoh via git (repo publik/privat dengan akses):
sudo -u simaftunsur git clone <URL_REPO> /tmp/simaftunsur-src
sudo -u simaftunsur cp -r /tmp/simaftunsur-src/ml/. /opt/simaftunsur-ml/
sudo rm -rf /tmp/simaftunsur-src
```

## 3. Virtualenv + dependensi
```bash
cd /opt/simaftunsur-ml
sudo -u simaftunsur python3 -m venv .venv
sudo -u simaftunsur .venv/bin/pip install --upgrade pip
sudo -u simaftunsur .venv/bin/pip install -r requirements.txt
```

## 4. Kunci API (shared secret)
```bash
openssl rand -hex 32          # SALIN hasilnya
sudo cp /opt/simaftunsur-ml/deploy/simaftunsur-ml.env.example /etc/simaftunsur-ml.env
sudo nano /etc/simaftunsur-ml.env    # isi ML_API_KEY dengan hasil di atas
sudo chown root:simaftunsur /etc/simaftunsur-ml.env
sudo chmod 640 /etc/simaftunsur-ml.env
```
> Kunci yang sama nanti dipasang di `.env` Laravel (cPanel) sebagai `ML_API_KEY`.

## 5. Jalankan sebagai service (systemd)
```bash
sudo cp /opt/simaftunsur-ml/deploy/simaftunsur-ml.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now simaftunsur-ml
sudo systemctl status simaftunsur-ml         # harus "active (running)"
curl -s http://127.0.0.1:8001/sehat          # {"status":"ok",...}
```

## 6. nginx + HTTPS
```bash
sudo cp /opt/simaftunsur-ml/deploy/nginx-ml.conf /etc/nginx/sites-available/ml.domainmu
sudo sed -i 's/ml.domainmu/ml.DOMAIN_ASLIMU/g' /etc/nginx/sites-available/ml.domainmu
sudo ln -s /etc/nginx/sites-available/ml.domainmu /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# TLS Let's Encrypt
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ml.DOMAIN_ASLIMU
```
Uji dari luar (harus 401 tanpa kunci — artinya auth aktif):
```bash
curl -s https://ml.DOMAIN_ASLIMU/sehat                       # 200 ok (sehat terbuka)
curl -s -X POST https://ml.DOMAIN_ASLIMU/klasterisasi        # 401 kunci tidak valid
```

## 7. Sisi Laravel (cPanel `.env`)
Tambahkan/ubah:
```
ML_BASE_URL=https://ml.DOMAIN_ASLIMU
ML_API_KEY=<kunci_hex_yang_sama_dengan_langkah_4>
ML_TIMEOUT=120
```
Lalu bersihkan cache config (via file: hapus `bootstrap/cache/config.php`, atau
jalankan `optimize:clear` bila memungkinkan). Buka halaman Klasterisasi →
tombol Jalankan. Bila service sehat, klaster akan terisi.

## 8. Update service saat kode berubah
```bash
# salin ulang isi ml/ ke /opt/simaftunsur-ml, lalu:
cd /opt/simaftunsur-ml && sudo -u simaftunsur .venv/bin/pip install -r requirements.txt
sudo systemctl restart simaftunsur-ml
```

## Troubleshooting
- `journalctl -u simaftunsur-ml -n 50` — log service.
- 502 dari nginx → uvicorn mati; cek `systemctl status simaftunsur-ml`.
- 401 saat Laravel memanggil → `ML_API_KEY` beda antara VPS dan cPanel.
- Timeout → naikkan `ML_TIMEOUT` (Laravel) dan `proxy_read_timeout` (nginx).
