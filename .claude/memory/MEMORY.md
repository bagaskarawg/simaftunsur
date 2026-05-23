# Memory Index — SIMAFTUNSUR

- [Design Direction](design_direction.md) — Institusional, biru UNSUR, anti "AI-banget"; palet, tipografi, dan style yang dipakai vs dihindari.
- [Laravel Setup](laravel_setup.md) — Keputusan struktural: model Pengguna, kolom kata_sandi, NIP-as-username, Fortify minimal, akun demo & cara run.
- [Subscription](subscription.md) — User berlangganan Claude Max → default ke OAuth login, bukan ANTHROPIC_API_KEY.
- [Konvensi NPM FT UNSUR](project_npm_konvensi.md) — Pakai `npm` (bukan `nim`); jangan tertukar dengan `pengguna.nip` SDM/dosen.
- [Lokasi Memory](feedback_memory_lokasi.md) — Simpan memory di `.claude/memory/` project (ikut git), BUKAN di home `~/.claude/`.
- [Sidebar Submenu](feedback_sidebar_submenu.md) — Submenu sidebar hidden sampai parent route aktif; route-driven, bukan Alpine toggle.
