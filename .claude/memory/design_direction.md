---
name: design-direction
description: "Arah desain UI/UX SIMAFTUNSUR — institusional, biru UNSUR, anti \"AI-banget\""
metadata: 
  node_type: memory
  type: feedback
  originSessionId: b0bd90c0-2470-4875-a475-5152fd34567c
---

Desain SIMAFTUNSUR harus terlihat profesional/institusional, bukan "AI-banget" atau "startup SaaS trendy".

**Why:** User eksplisit minta desain untuk dipakai pimpinan kampus (Dekan, WD III, BAAK FT UNSUR). Audience adalah pejabat akademik usia 40+ yang butuh kepercayaan visual seperti sistem pemerintahan/perbankan, bukan landing page produk AI. Identitas Teknik Informatika UNSUR pakai warna biru.

**How to apply:**
- Palet primary: biru institusional UNSUR — navy `#0F2C5C` (sidebar/header), biru `#1D4ED8` (CTA & branding). HINDARI ungu/pink/cyan neon yang khas SaaS AI 2024.
- Tipografi: Inter (single family) atau IBM Plex Sans / Lexend. JANGAN Space Grotesk / Cal Sans / Geist untuk display.
- Style: kombinasi Executive Dashboard + Data-Dense + Swiss Modernism. HINDARI glassmorphism, neumorphism, brutalism, bento grid ekstrim asimetris.
- Card: `rounded-lg` (8px), `shadow-card` halus (bukan `rounded-3xl` atau `shadow-2xl`).
- Tombol: solid color, no gradient, no glow.
- Background: light mode sebagai default (konvensi kampus Indonesia); dark mode opsional bukan default.
- Animasi: micro-interaction 150–300ms saja; no parallax, no scroll-jacking, no floating orb.
- Ikon: SVG (Heroicons / Lucide), bukan emoji.
- Bahasa UI: Indonesia formal/baku ("Klasterisasi", "Pembinaan", "Beranda"), bukan Inggris atau bahasa kasual.
- Tabel & data: padat tapi terbaca, dengan `tabular-nums` untuk angka.

Referensi visual yang pernah disetujui sebagai benchmark: Linear App, GitHub Primer, Vercel Dashboard, Stripe Dashboard, PDDIKTI, IBM Carbon. Bukan: Vercel V0 demos, Linear marketing site dengan gradient, atau Notion AI features.

Lihat juga: [[stack-target]] untuk konteks Laravel 13 + Livewire 4 + Tailwind.
