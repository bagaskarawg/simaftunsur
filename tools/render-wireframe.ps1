# =====================================================================
# render-wireframe.ps1 — Render mockup antarmuka (HTML/CSS) menjadi PNG
# via Chrome/Edge headless. Sumber: docs/wireframe-html/WF-*.html
# Keluaran: docs/gambar/WF-*.png  (siap tempel ke naskah, beri caption di Word)
#
# Cara pakai (dari akar proyek):
#   powershell -ExecutionPolicy Bypass -File tools\render-wireframe.ps1
# =====================================================================

$akar = Split-Path $PSScriptRoot -Parent
$src  = Join-Path $akar 'docs\wireframe-html'
$out  = Join-Path $akar 'docs\gambar'
New-Item -ItemType Directory -Force $out | Out-Null

# --- Cari Chrome / Edge ---
$cands = @(
  "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
  "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
  "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
  "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
)
$browser = $cands | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $browser) { throw 'Chrome/Edge tidak ditemukan. Pasang salah satunya lalu ulangi.' }
Write-Host "Browser: $browser"

# --- Daftar layar: nama = LebarxTinggi (CSS px, dirender 2x agar tajam) ---
$layar = [ordered]@{
  'WF-01-Login'                 = '1360x720'
  'WF-02-Beranda'               = '1440x720'
  'WF-03-Daftar-Mahasiswa'      = '1440x800'
  'WF-04-Detail-Mahasiswa'      = '1440x1220'
  'WF-05-Form-Tambah-Mahasiswa' = '1440x720'
  'WF-06-Dashboard-Klasterisasi'= '1440x1810'
  'WF-07-Detail-Klaster'        = '1440x1060'
  'WF-08-Daftar-Prestasi'       = '1440x740'
  'WF-09-Form-Prestasi-Modal'   = '1000x900'
  'WF-10-Manajemen-Pengguna'    = '1440x740'
}

foreach ($nama in $layar.Keys) {
  $wh = $layar[$nama].Split('x')
  $htmlWin = (Join-Path $src "$nama.html")
  $uri  = 'file:///' + ($htmlWin -replace '\\','/')
  $png  = Join-Path $out "$nama.png"
  Write-Host "  - $nama ($($layar[$nama]))"
  & $browser --headless --disable-gpu --hide-scrollbars --force-device-scale-factor=2 `
      --screenshot="$png" --window-size="$($wh[0]),$($wh[1])" $uri 2>$null | Out-Null
}

Write-Host ''
Write-Host "Selesai. PNG wireframe ada di: $out"
Get-ChildItem $out -Filter 'WF-*.png' |
  Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB)}} |
  Sort-Object Name | Format-Table -AutoSize
