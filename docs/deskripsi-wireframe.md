# Deskripsi Wireframe Antarmuka SIMAFTUNSUR

Dokumen ini memuat deskripsi setiap rancangan antarmuka (*wireframe*) hasil tahap Perancangan Sistem pada metode Waterfall. Wireframe disajikan dalam bentuk *low-fidelity* (tanpa warna) agar pembahasan terfokus pada struktur, fungsi, dan alur kerja tiap halaman, bukan pada estetika visual. Seluruh teks di bawah dapat langsung disalin ke naskah laporan.

---

## Gambar 3.x Wireframe Halaman Masuk (Login) — `WF-01-Login.png`

Wireframe halaman masuk merancang gerbang autentikasi tunggal menuju SIMAFTUNSUR. Halaman ini menerapkan tata letak dua kolom (*split layout*).

**Bagian-bagian halaman:**

1. **Panel branding (kiri).** Menampilkan logo dan identitas Universitas Suryakancana – Fakultas Teknik, nama sistem SIMAFTUNSUR, serta ringkasan tujuan sistem, yaitu platform pengelolaan data kemahasiswaan terintegrasi untuk mendukung pengambilan keputusan strategis pimpinan fakultas melalui klasterisasi profil mahasiswa. Di bagian bawah terdapat keterangan bahwa akses dibatasi hanya untuk pimpinan dan staf FT UNSUR, disertai penanda versi sistem.
2. **Panel formulir masuk (kanan).** Terdiri atas kolom isian **NIP/NIDN** sebagai identitas pengguna, kolom **Kata Sandi** yang disamarkan dan dilengkapi tombol untuk menampilkan/menyembunyikan karakter, opsi **Tetap masuk di perangkat ini** (*remember me*), tautan **Lupa kata sandi?** menuju alur pemulihan kata sandi, serta tombol utama **Masuk**.
3. **Panel informasi akses.** Memberi tahu bahwa akun tidak dapat didaftarkan secara mandiri; permintaan akses diajukan melalui Bagian Akademik FT UNSUR.

**Fungsi:** memverifikasi identitas pengguna sebelum sistem menentukan hak akses berdasarkan peran (*role*). Karena SIMAFTUNSUR merupakan sistem internal, tidak disediakan menu registrasi mandiri sehingga penambahan akun sepenuhnya dikendalikan oleh Administrator melalui modul Pengguna.

---

## Gambar 3.x Wireframe Halaman Beranda (Dashboard) — `WF-02-Beranda.png`

Wireframe beranda merancang halaman pertama yang ditemui pengguna setelah berhasil masuk, berfungsi sebagai ringkasan kondisi data kemahasiswaan sekaligus titik navigasi ke seluruh modul.

**Bagian-bagian halaman:**

1. **Bilah sisi navigasi (kiri).** Memuat menu yang dikelompokkan ke dalam dua kategori. Kelompok **Utama** berisi Beranda, Data Mahasiswa (dengan submenu Impor Mahasiswa dan Impor IPK), Klasterisasi, Prestasi, Kegiatan & Organisasi, Pengabdian & Hibah, Tracer Study, Beasiswa, KKN, Promosi/PMB, dan Laporan. Kelompok **Administrasi** berisi Pengguna, Pengaturan Sistem, dan Log Aktivitas. Menu yang tampil menyesuaikan hak akses peran pengguna.
2. **Bilah atas.** Berisi *breadcrumb* penunjuk posisi halaman serta identitas pengguna aktif beserta perannya.
3. **Kartu sambutan.** Menampilkan nama, peran, dan NIP pengguna, disertai dua tombol pintas menuju **Data Mahasiswa** dan **Klasterisasi**, serta ringkasan jumlah mahasiswa terdaftar dan aktif.
4. **Kartu Klasterisasi Terkini.** Meringkas hasil eksekusi K-Means terakhir: jumlah klaster yang terbentuk (ditentukan otomatis dari data), jumlah mahasiswa yang diklaster, nilai metrik evaluasi internal (Silhouette dan Davies-Bouldin), serta rincian label dan jumlah anggota tiap klaster. Terdapat tautan menuju detail klaster.
5. **Empat kartu statistik ringkas.** Menampilkan jumlah **Mahasiswa Aktif**, jumlah **Program Studi**, **Rata-rata IPK** seluruh catatan semester, dan jumlah mahasiswa **Siap Klaster**, yaitu mahasiswa berstatus aktif yang telah menempuh minimal tiga semester sesuai batasan masalah penelitian.

**Fungsi:** memberikan gambaran menyeluruh kondisi data kemahasiswaan dalam satu layar sehingga pimpinan fakultas dapat langsung menangkap informasi kunci tanpa membuka modul satu per satu.

---

## Gambar 3.x Wireframe Halaman Daftar Mahasiswa — `WF-03-Daftar-Mahasiswa.png`

Wireframe ini merancang halaman utama modul Data Mahasiswa, yaitu modul yang menjadi sumber data bagi proses klasterisasi.

**Bagian-bagian halaman:**

1. **Judul halaman dan keterangan singkat.**
2. **Panel pencarian dan penyaringan.** Terdiri atas kolom pencarian bebas berdasarkan NPM atau nama, serta enam penyaring: Program Studi, Angkatan, Status (aktif/cuti/non-aktif), Jenis Kelamin, dan rentang IPK rata-rata (nilai minimum dan maksimum). Tombol **Reset** mengembalikan seluruh penyaring ke kondisi awal.
3. **Tombol Tambah Mahasiswa.** Membuka formulir penambahan data mahasiswa baru.
4. **Tabel data mahasiswa.** Menampilkan kolom NPM, Nama (beserta jenis kelamin), Program Studi, Angkatan, Semester berjalan, Status, dan IPK rata-rata. Kotak centang di setiap baris memungkinkan pemilihan banyak data untuk tindakan massal.
5. **Panel paginasi.** Menampilkan keterangan jumlah data yang sedang ditampilkan dari keseluruhan data beserta navigasi antar halaman.

**Fungsi:** memfasilitasi pengelolaan dan penelusuran data mahasiswa dalam jumlah besar. Penyaring rentang IPK dan status secara khusus membantu staf memastikan kelayakan data sebelum proses klasterisasi dijalankan.

---

## Gambar 3.x Wireframe Halaman Detail Mahasiswa — `WF-04-Detail-Mahasiswa.png`

Wireframe ini merancang tampilan profil lengkap seorang mahasiswa sekaligus memperlihatkan seluruh atribut yang menjadi fitur masukan algoritma K-Means.

**Bagian-bagian halaman:**

1. **Kepala halaman.** Memuat nama mahasiswa, NPM, program studi, angkatan, semester berjalan, serta tombol **Ubah Data** dan **Hapus**.
2. **Kartu fitur akademik (F1–F4).** Menampilkan **IPK Rata-rata**, **IPK Terakhir**, **Tren** (arah perubahan IPK antarsemester), dan **Konsistensi** (simpangan baku IPK). Keempat nilai ini merupakan fitur akademik yang dihitung dari riwayat IPK per semester.
3. **Kartu fitur non-akademik (F5–F7).** Menampilkan **Skor Prestasi (F5)**, **Skor Kegiatan (F6)**, dan **Skor Pengabdian (F7)** yang dihitung dari rubrik penilaian SKKM.
4. **Panel Profil Mahasiswa.** Berisi data identitas: jenis kelamin, status studi, program studi, surel, dan nomor telepon.
5. **Panel Riwayat IPK per Semester.** Menampilkan tabel dengan kolom semester, tahun akademik, periode (ganjil/genap), SKS diambil, SKS lulus, dan nilai IPK, dilengkapi aksi Ubah dan Hapus per baris. Terdapat tombol **Impor File** untuk unggah massal dan **+ Tambah IPK** untuk entri manual.
6. **Panel Prestasi.** Menampilkan daftar prestasi mahasiswa beserta jenis, tingkat, poin, dan tanggal, dengan tautan menuju modul Prestasi.

**Fungsi:** menyatukan seluruh data yang tersebar menjadi satu profil utuh per mahasiswa. Halaman ini penting bagi penguji karena memperlihatkan secara transparan asal-usul ketujuh fitur yang digunakan dalam klasterisasi.

---

## Gambar 3.x Wireframe Formulir Tambah Mahasiswa — `WF-05-Form-Tambah-Mahasiswa.png`

Wireframe ini merancang formulir entri data mahasiswa baru secara manual.

**Bagian-bagian halaman:**

1. **Judul dan keterangan.** Menegaskan bahwa data IPK per semester ditambahkan setelah data induk mahasiswa dibuat, sehingga entri identitas dan entri nilai terpisah.
2. **Panel Identitas.** Berisi kolom **NPM** (11 digit), **Nama Lengkap**, dan pilihan **Jenis Kelamin** berupa tombol radio.
3. **Panel Kontak (opsional).** Berisi kolom surel dan nomor telepon.
4. **Panel Akademik.** Berisi pilihan **Program Studi**, **Angkatan**, **Semester Aktif**, dan **Status** studi.
5. **Tombol aksi.** **Simpan** untuk menyimpan data dan **Batal** untuk membatalkan.

**Fungsi:** menyediakan jalur entri data satuan sebagai pelengkap fitur impor massal, sekaligus memberlakukan validasi masukan (format NPM, kelengkapan atribut wajib) agar kualitas data yang masuk ke proses klasterisasi tetap terjaga.

---

## Gambar 3.x Wireframe Dashboard Klasterisasi — `WF-06-Dashboard-Klasterisasi.png`

Wireframe ini merupakan rancangan halaman inti penelitian, yaitu antarmuka menjalankan dan menampilkan hasil klasterisasi K-Means.

**Bagian-bagian halaman:**

1. **Judul dan keterangan metode.** Menegaskan bahwa jumlah klaster (k) ditentukan secara otomatis dari data melalui kombinasi Elbow Method dan Silhouette Coefficient, bukan ditetapkan manual oleh pengguna.
2. **Panel Kesiapan Data Klasterisasi.** Menampilkan bilah kemajuan terhadap ambang ideal (minimal 100 mahasiswa layak) beserta empat rincian: jumlah **Total** mahasiswa, jumlah **Aktif**, jumlah **Layak** diklaster, dan jumlah mahasiswa aktif yang **datanya belum lengkap**. Penanda status "Siap" muncul apabila ambang minimum terpenuhi.
3. **Panel Jalankan Klasterisasi.** Panel ini sengaja dirancang **tanpa satu pun parameter yang dapat dipilih operator**; isinya hanya keterangan metode dan satu tombol **Jalankan Klasterisasi**. Keterangan menyebutkan bahwa proses memakai tujuh fitur SKKM (F1–F4 akademik dan F5–F7 non-akademik), bahwa jumlah klaster (k) ditentukan dari data melalui Elbow Method dan Silhouette Coefficient, serta bahwa penskalaan fitur memakai StandardScaler. Rancangan ini merupakan keputusan metodologis: apabila nilai k atau skema penskalaan dapat dipilih sendiri oleh pengguna, hasil klasterisasi menjadi bergantung pada penilaian subjektif operator dan tidak lagi dapat dipertanggungjawabkan secara ilmiah. Karena itu penentuannya diserahkan sepenuhnya kepada data melalui metrik evaluasi internal.
4. **Empat kartu hasil evaluasi.** Menampilkan **Jumlah Klaster (k)** yang terpilih, nilai **Silhouette Coefficient**, nilai **Davies-Bouldin Index**, dan jumlah mahasiswa yang diklaster. Ketiga metrik ini merupakan metrik evaluasi internal yang sesuai untuk algoritma *unsupervised*.
5. **Panel Sebaran Klaster.** Menampilkan diagram pencar (*scatter plot*) hasil reduksi dimensi dengan PCA dua dimensi; setiap titik mewakili satu mahasiswa dan warna menandai keanggotaan klaster. Legenda di bawahnya menampilkan label dan jumlah anggota tiap klaster.
6. **Panel Evaluasi Jumlah Klaster.** Menampilkan dua grafik penentuan k: kurva **Elbow Method** (nilai WCSS/inertia untuk setiap k) dan grafik **Silhouette per k** dengan penanda pada nilai k yang terpilih.
7. **Panel Perbandingan Profil Antar-Klaster.** Menampilkan diagram radar yang membandingkan karakteristik centroid antarklaster pada dimensi IPK rata-rata, IPK akhir, tren, konsistensi, dan semester.
8. **Panel Profil & Rekomendasi Pembinaan per Klaster.** Setiap klaster ditampilkan sebagai kartu berisi label, jumlah anggota, karakteristik ringkas, dan rekomendasi tindak lanjut pembinaan, dilengkapi tautan menuju halaman detail klaster.
9. **Panel Daftar Anggota.** Menampilkan pratinjau anggota klaster terpilih beserta NPM, nama, program studi, IPK rata-rata, dan semester.
10. **Catatan kaki jejak eksekusi.** Mencantumkan waktu eksekusi terakhir, pengguna yang menjalankan, metode penskalaan, dan jumlah fitur yang dipakai.

**Fungsi:** menjadi antarmuka tunggal yang menjembatani proses *Modeling*, *Evaluation*, dan *Deployment* pada kerangka CRISP-DM. Halaman ini memungkinkan pengguna menjalankan pipeline K-Means, menilai kualitas klaster melalui metrik internal, dan langsung menafsirkan hasilnya menjadi rekomendasi pembinaan.

---

## Gambar 3.x Wireframe Halaman Detail Klaster — `WF-07-Detail-Klaster.png`

Wireframe ini merancang halaman penelusuran (*tracing*) satu klaster, yaitu penjelasan mengapa sekelompok mahasiswa berada dalam klaster yang sama.

**Bagian-bagian halaman:**

1. **Kepala halaman.** Menampilkan nomor dan label klaster, jumlah anggota, serta rujukan ke eksekusi asal (tanggal eksekusi dan nilai k yang ditentukan otomatis).
2. **Empat kartu ringkasan.** Menampilkan jumlah **Anggota**, **Label** klaster, nilai **Silhouette** eksekusi, dan jumlah **Fitur** yang dipakai.
3. **Panel Karakteristik Centroid.** Menampilkan nilai centroid klaster untuk tiap fitur (IPK rata-rata, tren, konsistensi, skor prestasi) dalam bentuk bilah posisi relatif terhadap rentang minimum–maksimum seluruh klaster, disertai penanda "Tertinggi" atau "Terendah". Keterangan di bawah panel menjelaskan bahwa titik pada bilah menunjukkan posisi centroid klaster ini di antara seluruh klaster.
4. **Panel Interpretasi & Rekomendasi.** Berisi penafsiran naratif atas karakteristik centroid serta rekomendasi pembinaan yang diturunkan darinya.
5. **Tabel Anggota Klaster.** Menampilkan seluruh anggota beserta nilai ketujuh fitur pada saat eksekusi (IPK rata-rata, IPK akhir, tren, konsistensi, skor prestasi, kegiatan, pengabdian) dan **jarak** tiap mahasiswa ke centroid. Baris diurutkan dari yang terdekat ke centroid, sehingga mahasiswa paling representatif bagi klaster tampil paling atas.
6. **Catatan kaki jejak eksekusi.** Mencantumkan waktu, pelaksana, metode penskalaan, dan kohort data yang digunakan.

**Fungsi:** menjamin hasil klasterisasi bersifat *auditable* dan tidak menjadi kotak hitam. Nilai fitur yang ditampilkan merupakan *snapshot* pada saat eksekusi sehingga hasil klaster tetap dapat ditelusuri meskipun data mahasiswa berubah setelahnya.

---

## Gambar 3.x Wireframe Halaman Daftar Prestasi — `WF-08-Daftar-Prestasi.png`

Wireframe ini merancang modul Prestasi, yaitu modul pendukung yang menjadi sumber perhitungan skor prestasi (fitur F5).

**Bagian-bagian halaman:**

1. **Judul dan keterangan.** Menjelaskan bahwa halaman memuat catatan prestasi akademik dan non-akademik yang menjadi sumber skor SKKM F5.
2. **Panel pencarian dan penyaringan.** Berisi kolom pencarian berdasarkan judul prestasi, nama mahasiswa, atau penyelenggara, serta penyaring jenis prestasi dan tingkat prestasi.
3. **Tombol Tambah Prestasi.** Membuka formulir pencatatan prestasi dalam bentuk modal.
4. **Tabel prestasi.** Menampilkan kolom mahasiswa (nama dan NPM), judul prestasi beserta tautan bukti, jenis (akademik/non-akademik), tingkat dan capaian, poin SKKM, tanggal, serta aksi Ubah dan Hapus.
5. **Panel paginasi.**
6. **Identitas pengguna** pada bilah atas menunjukkan peran Staf Prodi, memperlihatkan bahwa modul ini dapat dikelola oleh peran di tingkat program studi.

**Fungsi:** mencatat capaian mahasiswa secara terverifikasi. Poin yang dihasilkan modul ini terakumulasi menjadi fitur F5 yang turut menentukan penempatan klaster, sehingga modul pendukung ini berperan langsung terhadap kualitas masukan klasterisasi meskipun tidak mengandung sistem cerdas.

---

## Gambar 3.x Wireframe Formulir Tambah Prestasi (Modal) — `WF-09-Form-Prestasi-Modal.png`

Wireframe ini merancang formulir pencatatan prestasi yang tampil sebagai jendela modal di atas halaman daftar, sehingga pengguna tidak kehilangan konteks daftar yang sedang dilihat.

**Bagian-bagian formulir:**

1. **Kepala modal.** Berisi judul formulir dan tombol tutup.
2. **Pemilih Mahasiswa.** Berupa daftar pilihan dengan pencarian (*searchable select*) untuk menemukan mahasiswa dari data yang berjumlah besar.
3. **Kolom Judul Prestasi.**
4. **Tiga pilihan berjenjang: Jenis, Tingkat, dan Capaian.** Di bawahnya terdapat keterangan bahwa kombinasi capaian menentukan poin SKKM (F5) secara otomatis, sehingga penilaian tidak diserahkan pada penilaian subjektif operator.
5. **Kolom opsional.** Terdiri atas Peringkat, Tanggal, Penyelenggara, dan URL bukti.
6. **Area unggah berkas bukti.** Menerima berkas PDF/JPG/PNG dengan batas ukuran maksimum 2 MB.
7. **Kaki modal.** Berisi tombol **Simpan** dan **Batal**.

**Fungsi:** menjamin konsistensi dan objektivitas pemberian poin SKKM. Karena poin dihitung otomatis dari rubrik berdasarkan jenis, tingkat, dan capaian, nilai fitur F5 yang masuk ke klasterisasi bersifat terstandar dan dapat dipertanggungjawabkan.

---

## Gambar 3.x Wireframe Halaman Manajemen Pengguna — `WF-10-Manajemen-Pengguna.png`

Wireframe ini merancang modul administrasi pengguna yang menjadi wujud penerapan kontrol akses berbasis peran (*Role-Based Access Control*).

**Bagian-bagian halaman:**

1. **Judul dan keterangan.**
2. **Tombol Impor dan Tambah Pengguna.** Memungkinkan penambahan akun secara massal melalui berkas maupun satuan melalui formulir.
3. **Panel pencarian dan penyaringan.** Berisi kolom pencarian berdasarkan nama, NIP, atau surel, serta penyaring peran.
4. **Tabel pengguna.** Menampilkan kolom nama (dengan inisial), NIP/NIDN, surel, label peran, dan aksi Ubah/Hapus.
5. **Daftar peran yang tersedia.** Tampak lima peran yang menjadi aktor sistem: **Administrator**, **Wakil Dekan III**, **Staf WD III**, **Ketua Program Studi**, dan **Staf Prodi**. Pembagian ini menentukan menu yang tampak dan tindakan yang diizinkan bagi setiap pengguna; sebagai contoh, Wakil Dekan III memiliki akses baca terhadap hasil klasterisasi, sedangkan eksekusi klasterisasi dilakukan oleh Staf WD III.
6. **Panel paginasi.**

**Fungsi:** memastikan setiap pengguna hanya dapat mengakses data dan menjalankan fungsi yang sesuai dengan kewenangannya, sekaligus menjaga keamanan dan akuntabilitas data kemahasiswaan yang bersifat sensitif.

---

## Catatan untuk Sidang

Beberapa hal yang dapat ditekankan saat penguji menanyakan rancangan antarmuka:

1. **Wireframe sengaja tanpa warna.** Rancangan berada pada tahap *System Design* dalam metode Waterfall; fokusnya adalah struktur informasi dan alur interaksi, sedangkan pewarnaan dan identitas visual ditetapkan pada tahap Implementasi.
2. **Jumlah klaster tidak ditentukan manual.** Pada WF-06, kolom jumlah klaster terisi "Otomatis dari data" untuk menegaskan bahwa nilai k ditetapkan berdasarkan Elbow Method dan Silhouette Coefficient, bukan berdasarkan asumsi peneliti.
3. **Evaluasi bersifat internal.** Metrik yang ditampilkan hanya Silhouette Coefficient, Davies-Bouldin Index, dan WCSS, karena K-Means merupakan algoritma *unsupervised* sehingga metrik berbasis label seperti akurasi tidak dapat digunakan.
4. **Hasil klasterisasi dapat ditelusuri.** WF-07 memperlihatkan bahwa sistem menyimpan nilai fitur dan jarak ke centroid saat eksekusi, sehingga alasan penempatan setiap mahasiswa dalam klaster dapat dipertanggungjawabkan.
5. **Modul pendukung tidak mengandung sistem cerdas.** Modul Prestasi, Kegiatan, Pengabdian, Beasiswa, KKN, Promosi/PMB, dan Tracer Study dirancang sebagai CRUD biasa; perannya adalah memasok data bagi fitur klasterisasi, bukan menjalankan pemodelan.
