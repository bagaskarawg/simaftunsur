# =====================================================================
# render-gambar.ps1 — Render SEMUA diagram SIMAFTUNSUR menjadi PNG
# latar putih (siap tempel ke naskah DOCX). HANYA butuh Java + PlantUML;
# tidak lagi memakai Mermaid/Node/Chromium (lebih andal & offline).
#
# Cara pakai (dari akar proyek):
#   powershell -ExecutionPolicy Bypass -File tools\render-gambar.ps1
#
# Prasyarat: Java. Saat pertama dijalankan, skrip mengunduh plantuml.jar
# (sekali saja; setelah itu offline).
#
# Keluaran: docs\gambar\*.png  — Use Case, Class, Activity (AD-01..AD-04),
#   ERD terpecah (ERD-00 peta + ERD-01..05), Flowchart (FC-01..FC-05),
#   dan sequence diagram integrasi Laravel<->Python.
#
# ERD versi Visual Paradigm (bila diperlukan) diekspor langsung dari VP:
#   klik kanan diagram -> Export -> Active Diagram as Image (JANGAN centang
#   transparent background).
# =====================================================================

# Catatan: sengaja TIDAK memakai $ErrorActionPreference='Stop' global,
# karena perintah native (java) yang menulis ke stderr akan salah dianggap
# error oleh PowerShell. Penanganan galat via $LASTEXITCODE + throw eksplisit.
$akar   = Split-Path $PSScriptRoot -Parent
$jar    = Join-Path $PSScriptRoot 'plantuml.jar'
$keluar = Join-Path $akar 'docs\gambar'
New-Item -ItemType Directory -Force $keluar | Out-Null

# --- 1. Pastikan Java tersedia ---------------------------------------
if (-not (Get-Command java -ErrorAction SilentlyContinue)) {
    throw 'Java tidak ditemukan di PATH. Pasang JDK/JRE lalu ulangi.'
}

# --- 2. Pastikan plantuml.jar tersedia -------------------------------
if (-not (Test-Path $jar) -or (Get-Item $jar).Length -lt 5MB) {
    Write-Host '[1/2] Mengunduh plantuml.jar (sekali saja)...'
    curl.exe -sL -o $jar 'https://github.com/plantuml/plantuml/releases/latest/download/plantuml.jar'
    if (-not (Test-Path $jar) -or (Get-Item $jar).Length -lt 5MB) {
        throw 'Unduhan plantuml.jar gagal — periksa koneksi internet.'
    }
} else {
    Write-Host '[1/2] plantuml.jar sudah ada.'
}

# --- 3. Render seluruh file .puml ------------------------------------
Write-Host '[2/2] Merender seluruh diagram PlantUML (.puml) ke docs\gambar ...'
$daftar = Get-ChildItem (Join-Path $akar 'docs\vp-import\*.puml') | Sort-Object Name
foreach ($f in $daftar) {
    Write-Host "      - $($f.Name)"
    & java -jar $jar -charset UTF-8 -tpng -o $keluar $f.FullName
    if ($LASTEXITCODE -ne 0) { throw "PlantUML gagal merender $($f.Name)" }
}

Write-Host ''
Write-Host "Selesai. Semua PNG (latar putih) ada di: $keluar"
Get-ChildItem $keluar -Filter *.png |
    Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB)}} |
    Sort-Object Name | Format-Table -AutoSize
