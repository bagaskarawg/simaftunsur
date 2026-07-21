# =====================================================================
# render-flowchart.ps1 — Render flowchart ISO 5807 (simbol baku: oval
# terminal, persegi proses, belah ketupat keputusan, jajar genjang I/O).
# Sumber: docs/vp-import/flowchart/*.puml  (blok @startdot / Graphviz DOT)
# Alur: PlantUML -tsvg (Graphviz) -> bungkus HTML -> Chrome/Edge -> PNG.
# Keluaran: docs/gambar/FC-*.png
#
#   powershell -ExecutionPolicy Bypass -File tools\render-flowchart.ps1
# =====================================================================

$akar = Split-Path $PSScriptRoot -Parent
$jar  = Join-Path $PSScriptRoot 'plantuml.jar'
$src  = Join-Path $akar 'docs\vp-import\flowchart'
$out  = Join-Path $akar 'docs\gambar'
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

foreach ($puml in Get-ChildItem (Join-Path $src '*.puml') | Sort-Object Name) {
  $name = [IO.Path]::GetFileNameWithoutExtension($puml.Name)
  Write-Host "  - $name"
  # 1) DOT -> SVG (via PlantUML + Graphviz)
  & java -jar $jar -charset UTF-8 -tsvg -o $out $puml.FullName 2>$null | Out-Null
  $svg = Join-Path $out "$name.svg"
  if (-not (Test-Path $svg)) { Write-Warning "SVG gagal: $name"; continue }

  # 2) Baca ukuran SVG (pt) -> px (1pt = 4/3 px)
  $head = (Get-Content $svg -TotalCount 12) -join ' '
  $m = [regex]::Match($head, 'width="([0-9]+)pt" height="([0-9]+)pt"')
  if (-not $m.Success) { Write-Warning "Ukuran SVG tak terbaca: $name"; continue }
  $wpx = [math]::Ceiling([int]$m.Groups[1].Value * 4 / 3) + 6
  $hpx = [math]::Ceiling([int]$m.Groups[2].Value * 4 / 3) + 6

  # 3) Bungkus HTML (latar putih, gambar mepet) + screenshot
  $wrap = Join-Path $out "_wrap_$name.html"
  "<!doctype html><html><head><meta charset='utf-8'><style>html,body{margin:0;background:#fff}img{display:block}</style></head><body><img src='$name.svg'></body></html>" | Set-Content $wrap -Encoding utf8
  $png = Join-Path $out "$name.png"
  $uri = 'file:///' + ($wrap -replace '\\','/')
  & $browser --headless --disable-gpu --hide-scrollbars --force-device-scale-factor=2 `
      --screenshot="$png" --window-size="$wpx,$hpx" $uri 2>$null | Out-Null

  # 4) Bersihkan berkas antara
  Remove-Item $svg, $wrap -Force -ErrorAction SilentlyContinue
}

Write-Host ''
Write-Host "Selesai. Flowchart PNG di: $out"
Get-ChildItem $out -Filter 'FC-*.png' | Select-Object Name, @{n='KB';e={[math]::Round($_.Length/1KB)}} | Sort-Object Name | Format-Table -AutoSize
