# Penjelasan Scatter PCA 2D untuk Sidang (Antisipasi Pertanyaan Penguji)

> Catatan persiapan sidang. Menjelaskan mengapa titik pada scatter "Sebaran
> Klaster (proyeksi PCA 2D)" bisa tampak menyebar, dan cara menjawabnya secara
> jujur & dapat dipertanggungjawabkan.

---

## Kalimat kunci (inti jawaban)

> "Scatter itu hasil **reduksi dimensi PCA dari 7 dimensi ke 2 dimensi**, murni
> untuk visualisasi. K-Means menghitung keanggotaan berdasarkan **jarak Euclidean
> di ruang 7 dimensi terskala**, bukan di gambar 2D. Jadi kedekatan yang benar
> diukur di 7D — dibuktikan dengan metrik, bukan dari kerapatan visual."

Tujuh fitur klasterisasi (F1–F7): IPK rata-rata, IPK terakhir, tren IPK,
konsistensi IPK, skor prestasi, skor kegiatan/organisasi, skor pengabdian/hibah.

---

## Kenapa titik bisa tampak menyebar padahal klaster valid

1. **7D → 2D pasti kehilangan informasi.** PC1 & PC2 hanya menangkap sebagian
   *ragam (variance)* total; sisanya tidak tergambar. Dua titik yang berdekatan
   di 7D bisa tampak berjauhan di 2D, dan sebaliknya.
2. **Jarak di proyeksi ≠ jarak klasterisasi.** Anggota satu klaster bisa "tinggi"
   karena kombinasi fitur yang berbeda (satu unggul prestasi, satu unggul IPK),
   sehingga tersebar ketika dipipihkan ke 2 sumbu.
3. **"Berdekatan" itu relatif, bukan absolut.** Definisi klaster K-Means: tiap
   anggota **lebih dekat ke centroid-nya sendiri daripada ke centroid lain** —
   bukan "semua titik menempel rapat". Itu yang dijamin algoritma.

---

## Meluruskan pernyataan "klaster harus berdekatan"

Jangan menarik ucapan itu — perjelas maksudnya:

> "Betul, anggota satu klaster relatif berdekatan — tetapi **di ruang fitur 7
> dimensi**, dibuktikan lewat **jarak ke centroid** tiap mahasiswa, bukan dari
> tampilan 2D. Scatter PCA hanya gambaran kasar; kedekatan sebenarnya divalidasi
> dengan metrik, bukan mata."

---

## Bukti kuantitatif yang dibawa (senjata utama)

- **Jarak ke centroid** tiap anggota (tersimpan di `klasterisasi_anggota.
  jarak_ke_centroid`) — anggota memang lebih dekat ke centroidnya.
- **Silhouette Coefficient & Davies-Bouldin Index** — mengukur kohesi (kerapatan
  dalam klaster) & separasi (jarak antar-klaster) secara numerik.
- **Uji stabilitas bootstrap-Jaccard & uji beda Kruskal-Wallis** — klaster
  reprodusibel saat data di-resample & berbeda nyata antar-fitur.

Poinnya: **kualitas klaster dibuktikan dengan metrik internal, bukan dari
kerapatan visual pada proyeksi 2D.**

---

## Antisipasi tanya-jawab

**T: Kenapa anggota klaster "Berprestasi" terlihat berjauhan di grafik?**
J: Karena grafik itu proyeksi 2D dari 7D. Di ruang 7D sebenarnya, jarak mereka
ke centroid tetap wajar (mis. 1,1–2,1 pada skala standar) — sebanding klaster
lain. Mereka "Berprestasi" karena skor komposit tertinggi, dengan keunggulan pada
fitur yang berbeda-beda, sehingga menyebar saat diproyeksikan.

**T: Kalau begitu, apa gunanya scatter ini?**
J: Sebagai gambaran kasar sebaran & pemisahan antar-klaster untuk komunikasi ke
pimpinan. Keputusan & evaluasi kualitas tetap memakai metrik, bukan grafik.

**T: Bagaimana membuktikan pengelompokannya benar?**
J: Lewat Silhouette, Davies-Bouldin, jarak ke centroid, stabilitas Jaccard, dan
uji beda Kruskal-Wallis — semuanya angka yang dapat ditinjau.

**T: Kenapa PC1+PC2 tidak menangkap semua?**
J: PCA mengurutkan komponen berdasarkan ragam terbesar; dua komponen pertama
tidak akan menjelaskan 100% pada data 7 dimensi. Persentase ragam yang dijelaskan
dapat ditampilkan sebagai keterangan pada grafik untuk transparansi.

---

## Cara memperkuat (opsional, sebelum sidang)

1. **Tampilkan "% ragam PC1+PC2"** sebagai keterangan di bawah scatter (mis.
   "PC1+PC2 menjelaskan 62% ragam") — jujur & langsung menjawab "kenapa menyebar".
2. **Gambar titik centroid** tiap klaster pada scatter agar pengelompokan lebih
   terlihat.
3. **Jalankan ulang klasterisasi** dengan pemilihan k via Elbow (perbaikan
   terbaru) — k lebih kecil (mis. 4, bukan 7), gambar lebih koheren, label tidak
   lagi memakai *fallback* "Menengah (tingkat N)".

---

## Batas kejujuran (penting)

Pada **data simulasi/kecil (<100)** struktur klaster memang lemah, sehingga
visualisasi tampak longgar dan metrik rendah. Ini **jangan** diklaim sebagai bukti
kualitas. Pada **data riil ≥100 mahasiswa** dengan rekam akademik & non-akademik
lebih lengkap, struktur akan lebih tegas. (Sesuai Batasan Masalah & prinsip
kejujuran metode.)
