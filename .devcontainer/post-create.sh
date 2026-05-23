#!/usr/bin/env bash
# Dijalankan SEKALI saat Codespace dibuat atau di-rebuild.
# Tugas: pasang dependency, siapkan .env + DB, build asset, install Claude Code,
# dan tautkan direktori memory user-level ke .claude/memory dalam repo.

set -euo pipefail
cd "$(dirname "$0")/.."

print_step() { printf "\n\033[1;34m==>\033[0m \033[1m%s\033[0m\n" "$1"; }
print_ok()   { printf "    \033[32m✓\033[0m %s\n" "$1"; }

print_step "Memastikan ekstensi PHP (gd, zip) siap untuk maatwebsite/excel"
# phpoffice/phpspreadsheet (dep maatwebsite/excel) WAJIB ext-gd & ext-zip.
# Image base php:8.4-bookworm tidak menyertakan keduanya, jadi pasang manual.
EXT_CONF_DIR="$(php -r 'echo PHP_CONFIG_FILE_SCAN_DIR;')"
needs_install=0
php -m | grep -q '^gd$'  || needs_install=1
php -m | grep -q '^zip$' || needs_install=1

if [ "$needs_install" -eq 1 ]; then
    sudo apt-get update -qq
    sudo apt-get install -y -qq libfreetype-dev libjpeg-dev libpng-dev libzip-dev

    if ! php -m | grep -q '^gd$'; then
        sudo docker-php-ext-configure gd --with-freetype --with-jpeg >/dev/null
        sudo docker-php-ext-install -j"$(nproc)" gd >/dev/null
        echo 'extension=gd.so'  | sudo tee "$EXT_CONF_DIR/docker-php-ext-gd.ini"  >/dev/null
    fi
    if ! php -m | grep -q '^zip$'; then
        sudo docker-php-ext-install zip >/dev/null
        echo 'extension=zip.so' | sudo tee "$EXT_CONF_DIR/docker-php-ext-zip.ini" >/dev/null
    fi
    print_ok "ekstensi gd & zip terpasang"
else
    print_ok "ekstensi gd & zip sudah aktif"
fi

print_step "Memasang dependency Composer"
composer install --no-interaction --prefer-dist --optimize-autoloader

print_step "Memasang dependency npm"
# Catatan: pakai `npm install` (bukan `npm ci`) supaya transitive optional deps
# Linux yang tidak ada di lock file Windows (mis. @emnapi/*, *-linux-x64-gnu)
# bisa di-resolve ulang. Trade-off: lock file mungkin sedikit berubah lintas OS.
npm install

print_step "Menyiapkan berkas .env"
if [ ! -f .env ]; then
    cp .env.example .env
    print_ok ".env disalin dari .env.example"
fi
php artisan key:generate --force
print_ok "APP_KEY diperbarui"

print_step "Menyiapkan database SQLite"
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate:fresh --seed --force
print_ok "Migrasi & seed selesai (3 akun demo terisi)"

print_step "Membangun aset frontend (vite build)"
npm run build

print_step "Memasang Claude Code CLI (global)"
if ! command -v claude &>/dev/null; then
    npm install -g @anthropic-ai/claude-code
    print_ok "claude-code terpasang"
else
    print_ok "claude-code sudah terpasang"
fi

print_step "Menautkan memory & sessions ke repo (lintas-Codespace + lintas-device)"
# Claude Code menulis session transcripts (.jsonl) LANGSUNG di
# ~/.claude/projects/<slug>/ (bukan di subfolder). Jadi untuk membuat
# session ikut git, kita symlink seluruh <slug>/ ke repo .claude/sessions/.
# Memory subfolder di-symlink-balik ke repo .claude/memory/ supaya tetap
# bisa diakses lewat path Claude (~/.claude/projects/<slug>/memory/).
#
# Hasil layout:
#   ~/.claude/projects/<slug>/         → symlink → repo .claude/sessions/
#       ├── *.jsonl                    (real files, di repo)
#       └── memory/                    → symlink → repo .claude/memory/
PROJECT_PATH="$(pwd)"
SLUG="$(echo "$PROJECT_PATH" | sed 's|/|-|g' | sed 's|^-||')"
USER_PROJECT_DIR="$HOME/.claude/projects/$SLUG"
REPO_SESSIONS="$PROJECT_PATH/.claude/sessions"
REPO_MEMORY="$PROJECT_PATH/.claude/memory"

mkdir -p "$REPO_SESSIONS" "$REPO_MEMORY"
mkdir -p "$(dirname "$USER_PROJECT_DIR")"

# Jika dir <slug> sudah ada sebagai dir nyata (bukan symlink) — mis. dari
# instalasi Claude Code yang sudah berjalan sebelumnya — pindahkan isinya
# ke repo dulu, baru ganti dengan symlink.
if [ -d "$USER_PROJECT_DIR" ] && [ ! -L "$USER_PROJECT_DIR" ]; then
    if [ "$(ls -A "$USER_PROJECT_DIR" 2>/dev/null)" ]; then
        # Pindahkan jsonl & file lain ke repo (skip duplikat)
        cp -rn "$USER_PROJECT_DIR/." "$REPO_SESSIONS/" 2>/dev/null || true
    fi
    rm -rf "$USER_PROJECT_DIR"
fi

# Symlink <slug>/ → repo .claude/sessions/
ln -sfn "$REPO_SESSIONS" "$USER_PROJECT_DIR"
print_ok "~/.claude/projects/$SLUG → .claude/sessions/"

# Symlink <sessions>/memory → repo .claude/memory/ (agar memory tetap di
# .claude/memory/, terpisah dari sessions, sambil tetap accessible lewat
# path Claude yang menanti <slug>/memory/).
ln -sfn "$REPO_MEMORY" "$REPO_SESSIONS/memory"
print_ok ".claude/sessions/memory → .claude/memory/"

print_step "Memulihkan OAuth credential Claude Code (jika ada secret)"
# Tujuan: tidak perlu login ulang setiap Codespace baru.
# Cara: simpan isi ~/.claude/.credentials.json sebagai user-secret
# `CLAUDE_CREDENTIALS_B64` di GitHub. Lihat .devcontainer/README.md.
CRED_FILE="$HOME/.claude/.credentials.json"
if [ -n "${CLAUDE_CREDENTIALS_B64:-}" ] && [ ! -f "$CRED_FILE" ]; then
    mkdir -p "$HOME/.claude"
    if echo "$CLAUDE_CREDENTIALS_B64" | base64 -d > "$CRED_FILE" 2>/dev/null; then
        chmod 600 "$CRED_FILE"
        print_ok "credential dipulihkan dari secret — tidak perlu login ulang"
    else
        rm -f "$CRED_FILE"
        echo "    ⚠ CLAUDE_CREDENTIALS_B64 ada tapi gagal di-decode; lewati"
    fi
elif [ -f "$CRED_FILE" ]; then
    print_ok "credential sudah ada (mungkin dari volume mount) — lewati restore"
else
    echo "    (CLAUDE_CREDENTIALS_B64 belum diset — jalankan 'claude' untuk login OAuth)"
fi

print_step "Selesai!"
cat <<'EOF'

  Langkah berikutnya:
    • Jalankan dev server:   composer run dev    (Laravel + Vite + Queue + Pail)
      atau manual:           php artisan serve   &  npm run dev
    • Mulai Claude Code:     claude
      (otomatis baca CLAUDE.md + memory di .claude/memory/)

  Akun demo (kata sandi: rahasia123):
    • admin                  — Administrator Sistem
    • 197003051998031001     — Dr. Ir. Budi Santoso (Wakil Dekan III)
    • 198506152012121002     — Siti Nurhaliza (Staf)

EOF
