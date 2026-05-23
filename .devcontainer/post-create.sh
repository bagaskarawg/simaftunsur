#!/usr/bin/env bash
# Dijalankan SEKALI saat Codespace dibuat atau di-rebuild.
# Tugas: pasang dependency, siapkan .env + DB, build asset, install Claude Code,
# dan tautkan direktori memory user-level ke .claude/memory dalam repo.

set -euo pipefail
cd "$(dirname "$0")/.."

print_step() { printf "\n\033[1;34m==>\033[0m \033[1m%s\033[0m\n" "$1"; }
print_ok()   { printf "    \033[32m✓\033[0m %s\n" "$1"; }

print_step "Memasang dependency Composer"
composer install --no-interaction --prefer-dist --optimize-autoloader

print_step "Memasang dependency npm"
npm ci

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

print_step "Menautkan direktori memory ke repo (.claude/memory)"
# Claude Code menyimpan memory di ~/.claude/projects/<slug>/memory.
# Slug diturunkan dari path absolut proyek, dengan / diganti -.
PROJECT_PATH="$(pwd)"
SLUG="$(echo "$PROJECT_PATH" | sed 's|/|-|g' | sed 's|^-||')"
USER_MEMORY_DIR="$HOME/.claude/projects/$SLUG"
USER_MEMORY_LINK="$USER_MEMORY_DIR/memory"
REPO_MEMORY="$PROJECT_PATH/.claude/memory"

mkdir -p "$USER_MEMORY_DIR"
mkdir -p "$REPO_MEMORY"

# Bila lokasi memory sudah ada sebagai direktori biasa (bukan symlink),
# pindahkan isinya ke repo lalu hapus untuk dibuatkan symlink.
if [ -d "$USER_MEMORY_LINK" ] && [ ! -L "$USER_MEMORY_LINK" ]; then
    if [ "$(ls -A "$USER_MEMORY_LINK" 2>/dev/null)" ]; then
        cp -rn "$USER_MEMORY_LINK/." "$REPO_MEMORY/" || true
    fi
    rm -rf "$USER_MEMORY_LINK"
fi

ln -sfn "$REPO_MEMORY" "$USER_MEMORY_LINK"
print_ok "$USER_MEMORY_LINK → $REPO_MEMORY"

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
