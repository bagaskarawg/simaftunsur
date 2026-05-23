#!/usr/bin/env bash
# Capture ~/.claude/.credentials.json dan simpan/perbarui sebagai
# Codespaces user-secret CLAUDE_CREDENTIALS_B64. Jalankan di Codespace yang
# sudah login OAuth Claude Code.
#
#   bash .devcontainer/capture-credentials.sh
#
# Mencoba pakai `gh` CLI untuk set secret otomatis. Bila tidak ter-otorisasi,
# fallback ke instruksi manual (print base64 + URL settings).

set -euo pipefail

CRED_FILE="$HOME/.claude/.credentials.json"
SECRET_NAME="CLAUDE_CREDENTIALS_B64"

bold()  { printf "\033[1m%s\033[0m\n" "$1"; }
ok()    { printf "  \033[32m✓\033[0m %s\n" "$1"; }
warn()  { printf "  \033[33m⚠\033[0m %s\n" "$1"; }
err()   { printf "  \033[31m✗\033[0m %s\n" "$1"; }

# ---------- Validasi ----------
if [ ! -f "$CRED_FILE" ]; then
    err "$CRED_FILE tidak ada."
    echo "    Login dulu: jalankan 'claude' lalu pilih OAuth login."
    exit 1
fi

if ! command -v base64 &>/dev/null; then
    err "base64 tidak ditemukan."
    exit 1
fi

# ---------- Encode ----------
bold "[1/3] Mengenkripsi credential ke base64..."
B64="$(base64 -w 0 "$CRED_FILE")"
B64_LEN="${#B64}"
ok "encoded (${B64_LEN} karakter)"

# ---------- Coba gh CLI ----------
bold ""
bold "[2/3] Mencoba update secret lewat gh CLI..."

GH_AVAILABLE=0
if command -v gh &>/dev/null; then
    GH_AVAILABLE=1
else
    warn "gh CLI tidak terpasang"
fi

REPO=""
if [ "$GH_AVAILABLE" -eq 1 ]; then
    # Deteksi repo dari git remote
    if REPO="$(gh repo view --json nameWithOwner --jq .nameWithOwner 2>/dev/null)"; then
        ok "repo terdeteksi: $REPO"
    else
        warn "gh repo view gagal — coba git remote"
        if REMOTE="$(git config --get remote.origin.url 2>/dev/null)"; then
            REPO="$(echo "$REMOTE" | sed -E 's|^.*[:/]([^/]+/[^/]+)$|\1|' | sed 's|\.git$||')"
            ok "repo dari git remote: $REPO"
        fi
    fi
fi

GH_SUCCESS=0
if [ "$GH_AVAILABLE" -eq 1 ] && [ -n "$REPO" ]; then
    # Cek otorisasi gh untuk Codespaces secrets
    if gh auth status &>/dev/null; then
        echo "    menjalankan: gh secret set $SECRET_NAME --user --app codespaces --repos $REPO"
        if echo "$B64" | gh secret set "$SECRET_NAME" --user --app codespaces --repos "$REPO" --body - 2>/dev/null; then
            ok "secret berhasil di-set"
            GH_SUCCESS=1
        else
            warn "gh secret set gagal (kemungkinan butuh scope 'codespace:secrets')"
            echo "    Coba: gh auth refresh -h github.com -s codespace:secrets"
        fi
    else
        warn "gh belum login"
    fi
fi

# ---------- Fallback manual ----------
bold ""
bold "[3/3] Status"

if [ "$GH_SUCCESS" -eq 1 ]; then
    cat <<EOF

  ✅ Selesai. Secret \`$SECRET_NAME\` ter-update untuk repo \`$REPO\`.

  Codespace baru yang dibuat dari sekarang akan otomatis pulih credential.
  Tidak perlu login Claude Code ulang.

EOF
else
    cat <<EOF

  ⚠ Tidak bisa update otomatis. Lakukan manual:

  1. Salin base64 di bawah ini (single line, panjang ${B64_LEN} karakter):

----- BEGIN BASE64 -----
$B64
----- END BASE64 -----

  2. Buka: https://github.com/settings/codespaces

  3. Cari secret \`$SECRET_NAME\`:
     • Bila SUDAH ADA → klik Update, paste nilai baru
     • Bila BELUM ADA → klik "New secret":
         - Name: $SECRET_NAME
         - Value: paste base64 di atas
         - Repository access: pilih repo simaftunsur

  4. Save.

  Alternatif (kalau mau pakai gh CLI lain kali):
     gh auth refresh -h github.com -s codespace:secrets
     bash .devcontainer/capture-credentials.sh   # ulangi

EOF
fi
