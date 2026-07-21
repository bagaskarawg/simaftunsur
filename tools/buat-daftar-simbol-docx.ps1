# =====================================================================
# buat-daftar-simbol-docx.ps1 — Rakit "Daftar Simbol" menjadi berkas
# WORD BARU (.docx): satu tabel berisi 33 simbol diagram dengan gambar
# TERTANAM di dalam sel, plus tabel simbol matematika.
#
# PENTING: skrip ini TIDAK membuka Microsoft Word dan TIDAK menyentuh
# naskah TA. Berkas .docx dirakit langsung sebagai Office Open XML (zip).
# Naskah milik user JANGAN PERNAH diedit otomatis — user menyalin sendiri
# isi tabel ini ke naskahnya.
#
# Prasyarat: gambar simbol sudah dirender:
#   powershell -ExecutionPolicy Bypass -File tools\render-simbol.ps1
#
# Jalankan:
#   powershell -ExecutionPolicy Bypass -File tools\buat-daftar-simbol-docx.ps1
#
# Keluaran: docs\Daftar-Simbol-SIMAFTUNSUR.docx
# =====================================================================

Add-Type -AssemblyName System.Drawing
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$akar   = Split-Path $PSScriptRoot -Parent
$simbol = Join-Path $akar 'docs\gambar\simbol'
$keluar = Join-Path $akar 'docs\Daftar-Simbol-SIMAFTUNSUR.docx'

if (-not (Test-Path $simbol)) {
    throw "Folder gambar simbol tidak ada. Jalankan dulu tools\render-simbol.ps1"
}

# --- Satuan ----------------------------------------------------------
$EMU_PER_CM    = 360000    # 1 cm = 360000 EMU (ukuran gambar)
$TWIP_PER_CM   = 567       # 1 cm = 567 twip  (lebar kolom tabel)
$LEBAR_GBR_CM  = 2.6       # kotak muat gambar simbol
$TINGGI_GBR_CM = 1.5

# --- Data simbol: berkas | nama | diagram | fungsi --------------------
$daftar = @(
  @('SIM-01-terminator',      'Terminator',              'Flowchart', 'Menyatakan mulai atau selesai proses'),
  @('SIM-02-proses',          'Process',                 'Flowchart', 'Langkah proses / komputasi'),
  @('SIM-03-keputusan',       'Decision',                'Flowchart', 'Percabangan keputusan (Ya/Tidak)'),
  @('SIM-04-data',            'Data (Input/Output)',     'Flowchart', 'Masukan / keluaran data'),
  @('SIM-05-konektor',        'Connector',               'Flowchart', 'Penghubung alur dalam satu halaman'),
  @('SIM-06-arah-alur',       'Flow line',               'Flowchart', 'Arah / urutan alur antar simbol'),
  @('ERD-01-entitas',         'Entitas',                 'ERD',       'Objek/tabel data; nama di kepala, atribut di badan, PK/FK ditandai'),
  @('ERD-02-relasi',          'Garis relasi',            'ERD',       'Hubungan antar-entitas'),
  @('ERD-03-satu',            'Satu (1)',                'ERD',       'Kardinalitas satu dan hanya satu (dua garis)'),
  @('ERD-04-nol-atau-satu',   'Nol atau satu (0..1)',    'ERD',       'Opsional, maksimum satu (lingkaran + garis)'),
  @('ERD-05-satu-atau-banyak','Satu atau banyak (1..*)', 'ERD',       'Minimal satu, boleh banyak (kaki gagak + garis)'),
  @('ERD-06-nol-atau-banyak', 'Nol atau banyak (0..*)',  'ERD',       'Opsional, boleh banyak (kaki gagak + lingkaran)'),
  @('UC-01-aktor',            'Aktor',                   'Use Case',  'Pengguna / sistem eksternal yang berinteraksi dengan sistem'),
  @('UC-02-usecase',          'Use Case',                'Use Case',  'Fungsi / layanan yang disediakan sistem'),
  @('UC-03-boundary',         'Batas Sistem (boundary)', 'Use Case',  'Ruang lingkup sistem'),
  @('UC-04-asosiasi',         'Asosiasi',                'Use Case',  'Interaksi aktor dengan use case'),
  @('UC-05-include',          [char]0x00AB + 'include' + [char]0x00BB, 'Use Case', 'Use case selalu memanggil use case lain'),
  @('UC-06-extend',           [char]0x00AB + 'extend' + [char]0x00BB,  'Use Case', 'Perluasan opsional/kondisional sebuah use case'),
  @('UC-07-generalisasi',     'Generalisasi',            'Use Case',  'Pewarisan antar-aktor / use case (segitiga kosong)'),
  @('AD-01-initial',          'Initial node',            'Activity',  'Titik awal aktivitas'),
  @('AD-02-action',           'Action',                  'Activity',  'Satu langkah aktivitas/aksi'),
  @('AD-03-decision',         'Decision / Merge',        'Activity',  'Percabangan / penggabungan alur (guard [Ya]/[Tidak])'),
  @('AD-04-fork-join',        'Fork / Join',             'Activity',  'Pemisahan / penggabungan alur paralel'),
  @('AD-05-final',            'Activity Final node',     'Activity',  'Titik akhir aktivitas'),
  @('AD-06-alur',             'Control flow',            'Activity',  'Arah / urutan aktivitas'),
  @('AD-07-swimlane',         'Swimlane / Partition',    'Activity',  'Pengelompokan aktivitas per pelaku'),
  @('CD-01-kelas',            'Kelas',                   'Class',     'Nama, atribut, operasi; visibilitas + publik, ' + [char]0x2212 + ' privat, # terlindungi'),
  @('CD-02-asosiasi',         'Asosiasi',                'Class',     'Hubungan antar-kelas'),
  @('CD-03-agregasi',         'Agregasi',                'Class',     'Relasi "punya" ' + [char]0x2014 + ' bagian bisa berdiri sendiri (belah ketupat kosong)'),
  @('CD-04-komposisi',        'Komposisi',               'Class',     'Relasi "bagian dari" ' + [char]0x2014 + ' bagian bergantung penuh (belah ketupat terisi)'),
  @('CD-05-generalisasi',     'Generalisasi',            'Class',     'Pewarisan (segitiga kosong)'),
  @('CD-06-dependensi',       'Dependensi',              'Class',     'Ketergantungan (panah putus-putus)'),
  @('CD-07-multiplisitas',    'Multiplisitas',           'Class',     'Jumlah objek yang berelasi (1, 0..*)')
)

# --- Data simbol matematika: simbol | nama | unicode | makna ----------
$matematika = @(
  @([char]0x2212, 'Tanda minus',          '2212', 'Pengurangan (bukan tanda hubung)'),
  @([char]0x221A, 'Akar kuadrat',         '221A', 'Akar pada jarak Euclidean'),
  @([char]0x03A3, 'Sigma (penjumlahan)',  '03A3', 'Penjumlahan'),
  @([char]0x2016, 'Norm (garis ganda)',   '2016', 'Norma/panjang vektor'),
  @([char]0x2208, 'Elemen dari',          '2208', 'Titik anggota klaster'),
  @([char]0x2260, 'Tidak sama dengan',    '2260', 'Pasangan klaster berbeda'),
  @([char]0x2265, 'Lebih besar atau sama','2265', 'Syarat data (minimal 3 semester)'),
  @([char]0x2264, 'Lebih kecil atau sama','2264', 'Batas rentang nilai'),
  @([char]0x00B2, 'Pangkat dua',          '00B2', 'Kuadrat'),
  @([char]0x03BC, 'Mu',                   '03BC', 'Rata-rata fitur'),
  @([char]0x03C3, 'Sigma kecil',          '03C3', 'Simpangan baku fitur'),
  @([char]0x00D7, 'Kali',                 '00D7', 'Perkalian'),
  @([char]0x2248, 'Kira-kira sama',       '2248', 'Nilai hampiran'),
  @([char]0x2192, 'Panah kanan',          '2192', 'Menuju / menghasilkan')
)

# --- Fungsi bantu ----------------------------------------------------
function Escape-Xml([string]$teks) {
    if ($null -eq $teks) { return '' }
    $teks.Replace('&', '&amp;').Replace('<', '&lt;').Replace('>', '&gt;').Replace('"', '&quot;')
}

# Paragraf teks biasa (Times New Roman)
function Paragraf([string]$teks, [int]$ukuran = 22, [bool]$tebal = $false, [string]$rata = 'left') {
    $rpr = '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/>' +
           $(if ($tebal) { '<w:b/>' } else { '' }) + "<w:sz w:val=""$ukuran""/><w:szCs w:val=""$ukuran""/></w:rPr>"
    '<w:p><w:pPr>' + "<w:jc w:val=""$rata""/>" +
    '<w:spacing w:before="40" w:after="40" w:line="240" w:lineRule="auto"/>' + $rpr + '</w:pPr>' +
    '<w:r>' + $rpr + '<w:t xml:space="preserve">' + (Escape-Xml $teks) + '</w:t></w:r></w:p>'
}

# Sel tabel
function Sel([string]$isiParagraf, [int]$lebarTwip) {
    '<w:tc><w:tcPr>' + "<w:tcW w:w=""$lebarTwip"" w:type=""dxa""/>" +
    '<w:vAlign w:val="center"/></w:tcPr>' + $isiParagraf + '</w:tc>'
}

# Paragraf berisi gambar tertanam
function ParagrafGambar([string]$rId, [int]$idGbr, [long]$cx, [long]$cy, [string]$nama) {
    '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="60" w:after="60"/></w:pPr><w:r><w:drawing>' +
    '<wp:inline distT="0" distB="0" distL="0" distR="0">' +
    "<wp:extent cx=""$cx"" cy=""$cy""/><wp:effectExtent l=""0"" t=""0"" r=""0"" b=""0""/>" +
    "<wp:docPr id=""$idGbr"" name=""$nama""/>" +
    '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>' +
    '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">' +
    '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">' +
    '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">' +
    "<pic:nvPicPr><pic:cNvPr id=""$idGbr"" name=""$nama""/><pic:cNvPicPr/></pic:nvPicPr>" +
    '<pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' +
    "r:embed=""$rId""/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>" +
    '<pic:spPr><a:xfrm><a:off x="0" y="0"/>' + "<a:ext cx=""$cx"" cy=""$cy""/></a:xfrm>" +
    '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>' +
    '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'
}

$garis = '<w:tblBorders>' +
    ('<w:top w:val="single" w:sz="6" w:color="000000"/><w:left w:val="single" w:sz="6" w:color="000000"/>' +
     '<w:bottom w:val="single" w:sz="6" w:color="000000"/><w:right w:val="single" w:sz="6" w:color="000000"/>' +
     '<w:insideH w:val="single" w:sz="6" w:color="000000"/><w:insideV w:val="single" w:sz="6" w:color="000000"/>') +
    '</w:tblBorders>'

# =====================================================================
# TABEL 1 — Simbol diagram (33 baris)
# =====================================================================
Write-Host 'Menyusun tabel simbol diagram (33 baris)...'

$kolCm   = @(1.0, 3.0, 3.2, 2.0, 6.3)
$kolTwip = $kolCm | ForEach-Object { [int]($_ * $TWIP_PER_CM) }

$sb = New-Object System.Text.StringBuilder
[void]$sb.Append('<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/>' + $garis + '<w:tblLayout w:type="fixed"/></w:tblPr><w:tblGrid>')
foreach ($t in $kolTwip) { [void]$sb.Append("<w:gridCol w:w=""$t""/>") }
[void]$sb.Append('</w:tblGrid>')

# Baris judul (diulang tiap halaman)
[void]$sb.Append('<w:tr><w:trPr><w:tblHeader/></w:trPr>')
$judul = @('No', 'Simbol', 'Nama Simbol', 'Diagram', 'Fungsi')
for ($c = 0; $c -lt 5; $c++) {
    [void]$sb.Append((Sel (Paragraf $judul[$c] 22 $true 'center') $kolTwip[$c]))
}
[void]$sb.Append('</w:tr>')

$gambarDipakai = @()   # daftar berkas gambar untuk dimasukkan ke zip
$idGbr = 1

for ($i = 0; $i -lt $daftar.Count; $i++) {
    $item = $daftar[$i]
    $png  = Join-Path $simbol ($item[0] + '.png')
    if (-not (Test-Path $png)) { throw "Gambar tidak ditemukan: $png" }

    # Ukuran tampil: muat kotak 2,6 x 1,5 cm, rasio dipertahankan
    $img = [System.Drawing.Image]::FromFile($png)
    $w = $img.Width; $h = $img.Height
    $img.Dispose()
    $maxCx = [long]($LEBAR_GBR_CM * $EMU_PER_CM)
    $maxCy = [long]($TINGGI_GBR_CM * $EMU_PER_CM)
    if (($w / $h) -gt ($LEBAR_GBR_CM / $TINGGI_GBR_CM)) {
        $cx = $maxCx; $cy = [long]($maxCx * $h / $w)
    } else {
        $cy = $maxCy; $cx = [long]($maxCy * $w / $h)
    }

    $rId = 'rIdImg' + $idGbr
    $gambarDipakai += [pscustomobject]@{ rId = $rId; Berkas = $png; Nama = "image$idGbr.png" }

    [void]$sb.Append('<w:tr>')
    [void]$sb.Append((Sel (Paragraf ([string]($i + 1)) 22 $false 'center') $kolTwip[0]))
    [void]$sb.Append((Sel (ParagrafGambar $rId $idGbr $cx $cy $item[0]) $kolTwip[1]))
    [void]$sb.Append((Sel (Paragraf $item[1]) $kolTwip[2]))
    [void]$sb.Append((Sel (Paragraf $item[2]) $kolTwip[3]))
    [void]$sb.Append((Sel (Paragraf $item[3]) $kolTwip[4]))
    [void]$sb.Append('</w:tr>')

    $idGbr++
}
[void]$sb.Append('</w:tbl>')
$tabelSimbol = $sb.ToString()

# =====================================================================
# TABEL 2 — Simbol matematika
# =====================================================================
Write-Host 'Menyusun tabel simbol matematika...'
$kolMCm   = @(2.0, 4.0, 3.0, 6.5)
$kolMTwip = $kolMCm | ForEach-Object { [int]($_ * $TWIP_PER_CM) }

$sbm = New-Object System.Text.StringBuilder
[void]$sbm.Append('<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/>' + $garis + '<w:tblLayout w:type="fixed"/></w:tblPr><w:tblGrid>')
foreach ($t in $kolMTwip) { [void]$sbm.Append("<w:gridCol w:w=""$t""/>") }
[void]$sbm.Append('</w:tblGrid>')
[void]$sbm.Append('<w:tr><w:trPr><w:tblHeader/></w:trPr>')
$judulM = @('Simbol', 'Nama', 'Kode Unicode', 'Makna dalam Naskah')
for ($c = 0; $c -lt 4; $c++) {
    [void]$sbm.Append((Sel (Paragraf $judulM[$c] 22 $true 'center') $kolMTwip[$c]))
}
[void]$sbm.Append('</w:tr>')
foreach ($m in $matematika) {
    [void]$sbm.Append('<w:tr>')
    [void]$sbm.Append((Sel (Paragraf ([string]$m[0]) 24 $true 'center') $kolMTwip[0]))
    [void]$sbm.Append((Sel (Paragraf $m[1]) $kolMTwip[1]))
    [void]$sbm.Append((Sel (Paragraf $m[2] 22 $false 'center') $kolMTwip[2]))
    [void]$sbm.Append((Sel (Paragraf $m[3]) $kolMTwip[3]))
    [void]$sbm.Append('</w:tr>')
}
[void]$sbm.Append('</w:tbl>')
$tabelMatematika = $sbm.ToString()

# =====================================================================
# Rakit document.xml
# =====================================================================
$isi = (Paragraf 'DAFTAR SIMBOL' 28 $true 'center') +
       (Paragraf ('Notasi yang digunakan: Flowchart mengikuti ISO 5807:1985; ERD mengikuti notasi crow' + [char]0x2019 + 's foot (Information Engineering); Use Case, Activity, dan Class Diagram mengikuti UML 2.5.1 (OMG).') 22 $false 'both') +
       $tabelSimbol +
       (Paragraf '' 22) +
       (Paragraf 'Simbol Matematika' 26 $true 'left') +
       $tabelMatematika +
       (Paragraf ('Catatan: karakter pada tabel di atas dapat diketik di Word dengan mengetik kode Unicode lalu menekan Alt+X (misalnya 221A lalu Alt+X menghasilkan ' + [char]0x221A + '). Subskrip: Ctrl+=; superskrip: Ctrl+Shift+=.') 20 $false 'both')

# A4 potret, margin 3/2,5/2,5/2,5 cm (gaya naskah FT UNSUR)
$sectPr = '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>' +
          '<w:pgMar w:top="1418" w:right="1418" w:bottom="1418" w:left="1701" w:header="708" w:footer="708" w:gutter="0"/>' +
          '</w:sectPr>'

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
  '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ' +
  'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' +
  'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" ' +
  'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' +
  'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">' +
  '<w:body>' + $isi + $sectPr + '</w:body></w:document>'

# --- Bagian XML pendukung --------------------------------------------
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
  '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
  '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
  '<Default Extension="xml" ContentType="application/xml"/>' +
  '<Default Extension="png" ContentType="image/png"/>' +
  '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' +
  '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>' +
  '</Types>'

$relsUtama = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
  '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
  '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' +
  '</Relationships>'

$sbRel = New-Object System.Text.StringBuilder
[void]$sbRel.Append('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">')
[void]$sbRel.Append('<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>')
foreach ($g in $gambarDipakai) {
    [void]$sbRel.Append("<Relationship Id=""$($g.rId)"" Type=""http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"" Target=""media/$($g.Nama)""/>")
}
[void]$sbRel.Append('</Relationships>')
$relsDokumen = $sbRel.ToString()

# Gaya dasar + gaya tabel (agar garis tabel rapi di Word)
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
  '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' +
  '<w:docDefaults><w:rPrDefault><w:rPr>' +
  '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>' +
  '<w:sz w:val="24"/><w:szCs w:val="24"/><w:lang w:val="id-ID"/></w:rPr></w:rPrDefault></w:docDefaults>' +
  '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>' +
  '<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/>' +
  '<w:tblPr>' +
  '<w:tblBorders><w:top w:val="single" w:sz="6" w:color="000000"/><w:left w:val="single" w:sz="6" w:color="000000"/>' +
  '<w:bottom w:val="single" w:sz="6" w:color="000000"/><w:right w:val="single" w:sz="6" w:color="000000"/>' +
  '<w:insideH w:val="single" w:sz="6" w:color="000000"/><w:insideV w:val="single" w:sz="6" w:color="000000"/></w:tblBorders>' +
  '<w:tblCellMar><w:top w:w="60" w:type="dxa"/><w:left w:w="80" w:type="dxa"/>' +
  '<w:bottom w:w="60" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar>' +
  '</w:tblPr></w:style>' +
  '</w:styles>'

# =====================================================================
# Tulis paket .docx (zip) — TANPA membuka Word
# =====================================================================
Write-Host 'Merakit paket .docx (OOXML)...'
if (Test-Path $keluar) { Remove-Item $keluar -Force }
$utf8 = New-Object System.Text.UTF8Encoding($false)   # UTF-8 tanpa BOM

$zip = [System.IO.Compression.ZipFile]::Open($keluar, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    function Tulis-Entri($zip, [string]$nama, [string]$teks, $enc) {
        $e = $zip.CreateEntry($nama, [System.IO.Compression.CompressionLevel]::Optimal)
        $s = $e.Open()
        $b = $enc.GetBytes($teks)
        $s.Write($b, 0, $b.Length)
        $s.Close()
    }

    Tulis-Entri $zip '[Content_Types].xml'        $contentTypes $utf8
    Tulis-Entri $zip '_rels/.rels'                $relsUtama    $utf8
    Tulis-Entri $zip 'word/document.xml'          $documentXml  $utf8
    Tulis-Entri $zip 'word/styles.xml'            $stylesXml    $utf8
    Tulis-Entri $zip 'word/_rels/document.xml.rels' $relsDokumen $utf8

    foreach ($g in $gambarDipakai) {
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip, $g.Berkas, "word/media/$($g.Nama)",
            [System.IO.Compression.CompressionLevel]::Optimal)
    }
}
finally {
    $zip.Dispose()
}

$info = Get-Item $keluar
Write-Host ''
Write-Host "Selesai. Berkas Word BARU (naskah TA tidak disentuh):"
Write-Host "  $keluar"
Write-Host ("  {0} simbol, {1} KB" -f $gambarDipakai.Count, [math]::Round($info.Length / 1KB))
