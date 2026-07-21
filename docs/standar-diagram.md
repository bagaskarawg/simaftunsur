# Standar Notasi & Alat Pembuatan Diagram — SIMAFTUNSUR

> Rujukan singkat untuk menjawab pertanyaan penguji/pembimbing:
> "diagram ini dibuat pakai apa dan standar/versi berapa?"
> Terakhir diperbarui: 2026-07-02.

## Ringkasan (untuk dikutip langsung)

| Diagram | Standar notasi | Versi standar | Alat pembuatan | Versi alat |
|---|---|---|---|---|
| Use Case Diagram | UML — *Unified Modeling Language* (OMG) | **UML 2.5.1** | Visual Paradigm (model) + PlantUML (gambar) | PlantUML 1.2026.6 |
| Activity Diagram (AD) | UML *Activity Diagram* (OMG) | **UML 2.5.1** | Visual Paradigm + PlantUML | PlantUML 1.2026.6 |
| Class Diagram (CD) | UML *Class Diagram* (OMG) | **UML 2.5.1** | Visual Paradigm + PlantUML | PlantUML 1.2026.6 |
| ERD (basis data) | *Entity-Relationship*, notasi **crow's foot** (*Information Engineering*/Martin) | Konsep ER (Chen, 1976); model fisik **MySQL 8.x** | Visual Paradigm (*Reverse DDL*) + PlantUML | PlantUML 1.2026.6 |
| Flowchart (FC) | Bagan alir / *flowchart* | **ISO 5807:1985** (setara simbol ANSI) | PlantUML | PlantUML 1.2026.6 |
| Wireframe antarmuka | *Low-fidelity UI wireframe* (konvensi desain UI/HCI; bukan notasi ber-standar OMG) | — | PlantUML *Salt* | PlantUML 1.2026.6 |

## Rincian

### 1. UML (Use Case, Activity, Class)
- **Standar:** UML (*Unified Modeling Language*) — standar internasional dari **OMG (Object Management Group)**, juga diadopsi sebagai **ISO/IEC 19505**. Versi mutakhir spesifikasi: **UML 2.5.1** (OMG, Desember 2017).
- **Elemen yang dipakai sesuai UML 2.5.1:**
  - *Use Case:* aktor, use case, asosiasi, «include», «extend».
  - *Activity:* *initial node* (lingkaran penuh), *action* (kotak sudut membulat), *decision/merge* (belah ketupat) dengan *guard* `[Ya]`/`[Tidak]`, *activity final node*, dan *swimlane* (*activity partition*).
  - *Class:* kelas dengan atribut & operasi (visibility +/−/#), asosiasi bermultiplicity, dependensi «use».
- **Catatan teknis interchange:** berkas model untuk Visual Paradigm memakai **XMI 2.1** (*XML Metadata Interchange*, OMG) — format resmi impor UML ke VP.

### 2. ERD (Entity-Relationship Diagram)
- **Konsep:** model Entity-Relationship (Peter Chen, 1976).
- **Notasi gambar:** **crow's foot** (kaki gagak) gaya *Information Engineering* (Martin) — kardinalitas `1`, `0..1`, `0..*`, `1..*`.
- **Tingkat:** **model data fisik** (tipe kolom, PK/FK/unik) yang menargetkan **MySQL 8.x**.
- **Alat:** di Visual Paradigm dibentuk lewat **Tools → DB → Reverse DDL** dari `simaftunsur-erd.sql`; versi gambar cepat memakai PlantUML.

### 3. Flowchart (bagan alir proses klasterisasi)
- **Standar:** **ISO 5807:1985** — *Information processing — Documentation symbols and conventions for data, program and system flowcharts* (setara simbol ANSI/klasik).
- **Simbol:** *terminator* (oval mulai/selesai), *process* (persegi), *decision* (belah ketupat), *input/output* (jajar genjang), konektor/arah alur.

### 4. Wireframe antarmuka
- **Jenis:** *low-fidelity wireframe* — kerangka tata letak (kotak, label, *placeholder*), bukan tangkapan layar dan bukan notasi ber-standar OMG. Merupakan konvensi umum perancangan antarmuka (UI/HCI).
- **Alat:** **PlantUML Salt** (modul *wireframe* bawaan PlantUML).

## Alat & lingkungan (versi persis)
- **PlantUML `1.2026.6`** (build 2026-06-08) — menghasilkan seluruh gambar `.png` dari sumber teks `.puml`.
- **Java: OpenJDK `21.0.8` LTS** (Eclipse Temurin 21.0.8+9) — *runtime* PlantUML.
- **Visual Paradigm** — alat pemodelan UML/ERD untuk mengedit & mempresentasikan model. *(Isi nomor versi VP yang Anda pasang, mis. "Visual Paradigm 17.x", sebelum diserahkan.)*
- Impor ke Visual Paradigm: **XMI 2.1** (UML) dan **Reverse DDL / MySQL 8.x** (ERD).

## Cara menjawab lisan (ringkas)
> "Diagram UML (use case, activity, class) mengikuti standar **UML 2.5.1** dari OMG.
> ERD memakai notasi **crow's foot** (*Information Engineering*) untuk basis data
> **MySQL 8.x**. Flowchart mengikuti simbol **ISO 5807**. Pemodelan dilakukan di
> **Visual Paradigm**, dan gambar final dihasilkan dari **PlantUML 1.2026.6**
> (berjalan di Java 21). Wireframe antarmuka dibuat dengan **PlantUML Salt**."
