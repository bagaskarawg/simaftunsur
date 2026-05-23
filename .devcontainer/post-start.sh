#!/usr/bin/env bash
# Dijalankan SETIAP container start (termasuk resume dari stopped).
# Hanya memastikan kondisi minimum tetap konsisten — bukan setup berat.

set -euo pipefail
cd "$(dirname "$0")/.."

# Pastikan file SQLite ada (jaga-jaga kalau terhapus)
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

# Pastikan symlink memory & sessions tetap aktif (rebuild kadang menghapus
# symlink di ~). Cek per-direktori, recreate bila putus.
PROJECT_PATH="$(pwd)"
SLUG="$(echo "$PROJECT_PATH" | sed 's|/|-|g' | sed 's|^-||')"
USER_PROJECT_DIR="$HOME/.claude/projects/$SLUG"

for nama in memory sessions; do
    user_path="$USER_PROJECT_DIR/$nama"
    repo_path="$PROJECT_PATH/.claude/$nama"
    if [ -d "$repo_path" ] && [ ! -L "$user_path" ]; then
        mkdir -p "$USER_PROJECT_DIR"
        ln -sfn "$repo_path" "$user_path"
    fi
done
