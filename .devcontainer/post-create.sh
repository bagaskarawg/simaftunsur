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

print_step "Menautkan memory & sessions ke repo (lintas-Codespace persistence)"
# Claude Code menyimpan data per-proyek di ~/.claude/projects/<slug>/.
# Slug diturunkan dari path absolut proyek, dengan / diganti -.
PROJECT_PATH="$(pwd)"
SLUG="$(echo "$PROJECT_PATH" | sed 's|/|-|g' | sed 's|^-||')"
USER_PROJECT_DIR="$HOME/.claude/projects/$SLUG"
mkdir -p "$USER_PROJECT_DIR"

# Helper: pindahkan isi direktori user-level ke repo lalu ganti dengan symlink
relink_to_repo() {
    local nama="$1"            # mis. "memory" atau "sessions"
    local user_path="$USER_PROJECT_DIR/$nama"
    local repo_path="$PROJECT_PATH/.claude/$nama"

    mkdir -p "$repo_path"

    if [ -d "$user_path" ] && [ ! -L "$user_path" ]; then
        if [ "$(ls -A "$user_path" 2>/dev/null)" ]; then
            cp -rn "$user_path/." "$repo_path/" || true
        fi
        rm -rf "$user_path"
    fi
    ln -sfn "$repo_path" "$user_path"
    print_ok "$user_path → $repo_path"
}

relink_to_repo "memory"
relink_to_repo "sessions"

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
