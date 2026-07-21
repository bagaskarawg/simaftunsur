daftar tabel
[ ] format

daftar gambar
[ ] format

[x] daftar simbol

bab ii
[x] enam fase crisp-dm
[x] k-means++
[x] evaluasi jaccard coefficient
[x] Uji Kruskal-Wallis
[ ] Simulasi Perhitungan
[ ] standard scaler

bab iii

daftar pustaka
[ ] cek apakah sudah lengkap

[ ] file presentasi

## 3 Juli 2026
- Di BAB II tambahkan model pengujian cara validasi/evaluasi hasil dari klasterisasi
    - model pengujian ini ditujukan untuk menunjukkan bahwa hasil klasterisasi itu kredibel dan dapat dipertanggungjawabkan
    - cari model pengujian yang cocok dan bisa menunjukkan nilai persentase nya
- ERD Full secara Landscape

## 2 Juli 2026
- Simbol FLowchart disesuaikan
- Jumlah Klaster Sesuai data:
    - Berprestasi
    - Perlu Bimbingan
- Bisa dinamis

- Jelaskan di tinjauan pustaka bahwa untuk menyelesaikan fitur machine learning nya dengan Python, konsep scikit-learn itu apa? Jelaskan secara detail dan bagaimana cara mengintegrasikan antara Machine Learning dengan sistem yang berjalan
- Bagaimana hasil dari perhitungan scikit-learn ini (diambil lagi oleh) / (dikirim ke) Sistem Informasi
- Activity Diagram Proses Klasterisasi pakai Flowchart
    - Gambarkan mana proses Elbow
    - mana proses Silhoutte
    - mana proses Davies-Boudlin Index
- Perhitungan Rumus
- Perancangan Software - Tahapan Machine Learning
- Sistem Informasi -> Sudah Siap Pakai
- Bagaimana cara mengimplementasikan Machine Learning dalam aplikasi
- Tahapan Perhitungan harus sudah terlihat
- Untuk gambar jangan ada warna background/PNG transparent, pastikan terlihat dengan jelas

## 1 Juli 2026
- dalam k-means kenapa centroid awal dipilih secara acak? dan kriteria nya apa saja?
- standard scaler -> Pembanding 2
- pengelompokan 2 min-max & standard scaler
- non-akademik optional
    - dibatasi, contoh: ikut turnamen nasional, wilayah, dst
- selesaikan rumusnya untuk menentukan model
- buat tahapan model untuk smart system
- jangan gunakan waterfall, gunakan crisp-dm saja untuk metode pengembangan
- menyelesaikan bahwa sistem informasi sudah ada, menggunakan data
    - KKN, akademik, nilai, prestasi
    - ada perancangan sistem informasi
    - tahapan di machine learning, apa saja tahapannya
        - machine learning untuk hasil akhirnya
- bangunan besar -> sistem informasi kemahasiswaan
    - depannya dibuat untuk mempercepat proses, menghasilkan hasil akurat, siapa yang diberikan beasiswa, siapa yang nilainya mendekati standar untuk dapat penghargaan
- arsitektur aplikasi

Tinjauan Pustaka

BAB III
- Kasih contoh perhitungan
- Model arsitektur -> tahap-tahap dalam proses mulai dari input data -> masuk machine learning -> sampai output
- Tahapan Machine Learning-nya diperjelas
- sesuaikan perhitungan dengan kasus yang sedang diteliti
- sampai perancangan sistem informasi (use case, perancangan basis data: mengelola database & database yang menghasilkan di machine learning, dll)
- ERD: SI & Hasil Output Machine Learning -> referensi hasil machine learning dasar data dari mana
- gambarkan model pakai flowchart
- rancang juga sistem informasinya, basis data nya & antarmuka nya (yang penting dan relevan saja)
- visual paradigm: BNPM, Use Case, Activity, Sequence, Class, ERD, Interface

- mahasiswa bisa mencapai 1500++. per semester pasti ada beberapa ribu record IPK, dll. apakah semua disimpan atau sebagian besar yang nilainya 0 tidak disimpan di table machine learning
- masukan terkait bagaimana strategi yang akan diolah apakah yang memiliki aktivitas atau semua?
- di konsep machine learning akan membaca seluruh data, tapi nanti akan ada cleansing, bagaimana hasil cleansingnya? di cleansing tentukan apakah yang aktif saja atau bagaimana? penentuan data yang dimasukkan ke machine learning apa saja/yang bagaimana?
- cek lagi model konsep machine learning terkait cleansing

Presentasi:
- perancangan tidak perlu dimasukkan ke power point, dapat disimpan di visual paradigm -> dan dipresentasikan dari visual paradigm

hasil perancangan ada software yang sudah jadi, seperti navicat

BESOK Jam 9 & Jam 3 - Report

## 30 Juni 2026

BESOK Jam 9
- Ke kampus untuk menjelaskan diagram algoritma Euclidean -> Bagaimana tahapan, ada anomali atau tidak, bagaimana pembuatan rumusnya
- Rumus -> contoh perhitungannya dijelaskan agar lebih mudah dipahami

tahapan algoritma -> Euclidean -> jangan hanya sebut klasterisasi, tapi jelaskan apa yang dimaksud dengan klasterisasi
- Elbow Method
- Silhoutte

- jangan hanya ceritakan model, tapi ceritakan juga tujuannya apa?
    - kerangka model
        - davies bouldin index
        - shilloutte, dll
- baru ada metode pengembangan
    - model sistemnya harus tergambarkan seperti apa modelnya?

IPK hanya menjadi salah satu bagian yang menjadikan nilai, berikut detailnya:
1. akademik
    - IPK
    - kegiatan pendukung/turnamen
2. terkait di luar akademik (non-akademik) - nilai akademik
    - kegiatan apa saja yang diikuti
    - keterlibatan prestasi
    - hibah
    - pengabdian
    - cerdas cermat
    - module:
        - Kegiatan
        - Prestasi

kelompokkan setiap bidang penilaian/prestasi non-akademik
tentukan model scoring penilaian keseluruhan
- model scoring belum ditentukan, coba cari referensi bagaimana agar scoring nya baik

yang akses data bukan hanya
ROLE:
- Administrator
    - Manajemen Sistem terutama user
- Dekan III
- Staff Dekan III
- Kaprodi
    - Monitoring Nilai IPK Mahasiswa dan Nilai IPK Penerima Beasiswa
- Staff Prodi
    - Update Prestasi Akademik
    - Non-Akademik
        - Sertifikat Juara

Cek Sumber Referensi dan penulisan sitasi
- kalau bisa, download file referensi nya agar dapat dibuktikan kebenarannya

Detailkan model "Unsupervised" Machine Learning
Datanya seharusnya sudah supervised
- cek apakah perlu data unsupervised atau cukup dengan supervised?
- data seperti apa yang akan digunakan sebagai basis unsupervised, penggunaannya untuk apa?

dataset
minta data IPK sejak tahun 2019 ke pak Lalan

Gambar Flowchart sesuaikan dengan standar

Penulisan BAB I, BAB II, dan BAB III, harus disimpan di Bawah:
```
BAB III
ANALISIS DAN PERANCANGAN
```

Sesuaikan Diagram sesuai dengan aturan
- Use Case Diagram
- Activity Diagram
- Class Diagram

Diagramnya mengikuti Pengerjaan sistem informasi standar, tapi lebih condong ke tahapan pengelolaan sistem cerdasnya
Klasifikasi Data nya apa saja, belum jelas
