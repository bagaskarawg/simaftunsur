---
name: subscription
description: User berlangganan Claude Max — jangan menyarankan API key sebagai metode auth default
metadata:
  type: user
---

User berlangganan **Claude Max**. Pemakaian Claude Code seharusnya lewat OAuth login (`claude` → browser flow) supaya terhitung dalam kuota Max, **bukan** lewat `ANTHROPIC_API_KEY` (yang memicu billing pay-per-token terpisah).

**Implikasi:**
- Saat menyiapkan environment baru (devcontainer, dotfiles, CI lokal), default ke OAuth, bukan API key.
- Hanya rekomendasikan `ANTHROPIC_API_KEY` untuk skenario headless/CI yang memang butuh token (jelaskan trade-off billing-nya).
- Saat user bertanya soal billing Claude Code, ingat: Max meng-cover OAuth-based usage; API key adalah jalur terpisah.

Lihat juga: [[laravel-setup]] (Codespaces config) — `.devcontainer/devcontainer.json` me-mount volume `~/.claude` agar credential OAuth bertahan lintas rebuild.
