# =====================================================================
# render-simbol.ps1 — Render tiap SIMBOL flowchart (ISO 5807) sebagai
# gambar PNG terpisah, latar putih, untuk tabel "Keterangan Simbol" di
# naskah. Sumber: docs/vp-import/simbol/*.puml (blok @startdot / DOT).
# Alur: PlantUML -tsvg (Smetana) -> bungkus HTML (ukuran img dipaksa) ->
#        Chrome/Edge headless -> PNG latar putih.
# Keluaran: docs/gambar/simbol/SIM-*.png
#
#   powershell -ExecutionPolicy Bypass -File tools\render-simbol.ps1
# =====================================================================

$akar = Split-Path $PSScriptRoot -Parent
$jar  = Join-Path $PSScriptRoot 'plantuml.jar'
$src  = Join-Path $akar 'docs\vp-import\simbol'
$out  = Join-Path $akar 'docs\gambar\simbol'
New-Item -ItemType Directory -Force $out | Out-Null

if (-not (Get-Command java -ErrorAction SilentlyContinue)) { throw 'Java tidak ditemukan di PATH.' }
$cands = @(
  "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
  "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
  "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
  "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
)
$browser = $cands | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $browser) { throw 'Chrome/Edge tidak ditemukan.' }
Write-Host "Browser: $browser"

$pad = 14  # padding putih (px) di tiap sisi

foreach ($puml in Get-ChildItem (Join-Path $src '*.puml') | Sort-Object Name) {
  $name = [IO.Path]::GetFileNameWithoutExtension($puml.Name)
  Write-Host "  - $name"
  # 1) DOT -> SVG (mesin Smetana bawaan PlantUML; tanpa Graphviz).
  & java -jar $jar -charset UTF-8 -tsvg -o $out $puml.FullName 2>$null | Out-Null
  $svg = Join-Path $out "$name.svg"
  if (-not (Test-Path $svg)) { Write-Warning "SVG gagal: $name"; continue }

  # 2) Ukuran intrinsik SVG. Dua sumber berbeda satuan:
  #    - Graphviz/@startdot: pt  -> px (1pt = 4/3 px)
  #    - PlantUML native/@startuml: px (langsung)
  $head = (Get-Content $svg -TotalCount 14) -join ' '
  $mw = [regex]::Match($head, 'width="([0-9]+(?:\.[0-9]+)?)(pt|px)"')
  $mh = [regex]::Match($head, 'height="([0-9]+(?:\.[0-9]+)?)(pt|px)"')
  if (-not ($mw.Success -and $mh.Success)) { Write-Warning "Ukuran SVG tak terbaca: $name"; continue }
  $fw = if ($mw.Groups[2].Value -eq 'pt') { 4.0 / 3 } else { 1.0 }
  $fh = if ($mh.Groups[2].Value -eq 'pt') { 4.0 / 3 } else { 1.0 }
  $iw = [math]::Ceiling([double]$mw.Groups[1].Value * $fw)
  $ih = [math]::Ceiling([double]$mh.Groups[1].Value * $fh)
  $wpx = $iw + 2 * $pad
  $hpx = $ih + 2 * $pad

  # 3) Bungkus HTML: ukuran <img> DIPAKSA (iw x ih) agar browser tak
  #    memperbesar bentuk hingga terpotong; padding putih seragam.
  $wrap = Join-Path $out "_wrap_$name.html"
  "<!doctype html><html><head><meta charset='utf-8'><style>html,body{margin:0;background:#fff}body{padding:${pad}px;box-sizing:border-box}img{display:block;width:${iw}px;height:${ih}px}</style></head><body><img src='$name.svg'></body></html>" | Set-Content $wrap -Encoding utf8
  $png = Join-Path $out "$name.png"
  $uri = 'file:///' + ($wrap -replace '\\','/')
  & $browser --headless --disable-gpu --hide-scrollbars --force-device-scale-factor=3 `
      --screenshot="$png" --window-size="$wpx,$hpx" $uri 2>$null | Out-Null

  # 4) Bersihkan berkas antara
  Remove-Item $svg, $wrap -Force -ErrorAction SilentlyContinue
}

Write-Host ''
Write-Host "Selesai. Simbol PNG di: $out"
Get-ChildItem $out -Filter '*.png' | Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB,1)}} | Sort-Object Name | Format-Table -AutoSize

Write-Host ''
Write-Host "Selesai. Simbol PNG di: $out"
Get-ChildItem $out -Filter 'SIM-*.png' | Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB)}} | Sort-Object Name | Format-Table -AutoSize
