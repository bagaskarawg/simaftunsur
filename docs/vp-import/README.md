# Diagram SIMAFTUNSUR (PlantUML) — tanpa Visual Paradigm

Seluruh diagram TA disediakan sebagai **sumber teks PlantUML** yang dapat dirender **gratis**
tanpa Visual Paradigm. Notasi mengikuti standar baku (referensi tercantum di tiap berkas & di
bawah), agar tidak dipertanyakan asal-usulnya saat sidang.

## Cara render (gratis)

| Cara | Langkah |
|---|---|
| **planttext.com** (termudah) | Buka situs → tempel isi berkas `.puml` → *Refresh* → *Download* PNG/SVG. |
| **plantuml.com/plantuml** | Server resmi PlantUML. |
| **VS Code** | Pasang ekstensi *PlantUML* → buka `.puml` → `Alt+D` pratinjau → klik kanan *Export*. |
| **draw.io / diagrams.net** | GUI penuh pengganti VP; dapat meng-*import* `proses-berjalan.bpmn` atau menggambar ulang. |

> Ekspor **latar putih** (bukan transparan) sesuai catatan pembimbing. Berkas dengan beberapa blok
> `@startuml … @enduml` menghasilkan beberapa gambar — render per blok.

## Daftar berkas

| Berkas | Diagram | Catatan |
|---|---|---|
| `simaftunsur-usecase.puml` | Use Case | **6 aktor** (termasuk Wakil Rektor III); Penyaringan Kandidat memakai hasil klaster. |
| `simaftunsur-activity.puml` | Activity | Alur klasterisasi (Elbow/Silhouette/DBI) + flowchart algoritma; swimlane. |
| `simaftunsur-class.puml` | Class | Kelas fokus penelitian + modul pendukung. |
| `simaftunsur-erd-lengkap.puml` | **ERD (utama)** | Satu gambar utuh (A4 landscape); termasuk atribut ekonomi & tabel RBAC. |
| `simaftunsur-erd.puml` | ERD (lampiran) | Terpecah per modul, dengan tipe data. |
| `simaftunsur-sequence.puml` | Sequence | Integrasi aplikasi ↔ service Python (REST). |
| `proses-berjalan.puml` | Activity (proses berjalan) | Sistem manual yang sedang berjalan (BAB III). |
| `proses-berjalan.bpmn` | BPMN 2.0 | Alternatif untuk draw.io. |

## Referensi notasi (untuk sitasi di naskah)

- **Diagram UML** (Use Case, Activity, Class, Sequence): Object Management Group. (2017).
  *Unified Modeling Language (OMG UML), Version 2.5.1.* — konsisten dengan Pressman & Maxim (2020),
  *Software Engineering: A Practitioner's Approach* (9th ed.).
- **ERD** (kaki-gagak / crow's foot / Information Engineering): Elmasri & Navathe (2016),
  *Fundamentals of Database Systems*; Connolly & Begg (2015), *Database Systems*.
- **Aktor di luar system boundary**: sesuai OMG UML 2.5.1 — aktor adalah peran yang *external to the
  subject*, sehingga digambar di luar kotak batas sistem; use case di dalamnya.
