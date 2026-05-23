#!/usr/bin/env bash
# Dijalankan SETIAP container start (termasuk resume dari stopped).
# Hanya memastikan kondisi minimum tetap konsisten — bukan setup berat.

set -euo pipefail
cd "$(dirname "$0")/.."

# Pastikan file SQLite ada (jaga-jaga kalau terhapus)
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

# Pastikan symlink memory tetap aktif (rebuild kadang menghapus symlink di ~)
PROJECT_PATH="$(pwd)"
SLUG="$(echo "$PROJECT_PATH" | sed 's|/|-|g' | sed 's|^-||')"
USER_MEMORY_LINK="$HOME/.claude/projects/$SLUG/memory"
REPO_MEMORY="$PROJECT_PATH/.claude/memory"

if [ ! -L "$USER_MEMORY_LINK" ] && [ -d "$REPO_MEMORY" ]; then
    mkdir -p "$(dirname "$USER_MEMORY_LINK")"
    ln -sfn "$REPO_MEMORY" "$USER_MEMORY_LINK"
fi
