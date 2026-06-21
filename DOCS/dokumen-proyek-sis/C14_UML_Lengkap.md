# C14. UML Lengkap

---

## 1. Use Case Diagram

```mermaid
flowchart LR
    subgraph "Sistem Informasi Sekolah"
        UC1[Login]
        UC2[CRUD Data Master]
        UC3[Kelola Jadwal]
        UC4[Input Absensi]
        UC5[Input Nilai]
        UC6[Cetak Rapor]
        UC7[Catat Pembayaran]
        UC8[Lihat Tagihan]
        UC9[Lihat Dashboard]
        UC10[Kelola Pengguna]
        UC11[Lihat Audit Log]
    end
    KS[Kepala Sekolah] --> UC9 & UC10 & UC11 & UC1
    WK[Wakasek] --> UC3 & UC4 & UC5 & UC9 & UC1
    TU[Tata Usaha] --> UC2 & UC3 & UC10 & UC1
    GR[Guru] --> UC4 & UC5 & UC1
    WKLS[Wali Kelas] --> UC4 & UC5 & UC6 & UC1
    BN[Bendahara] --> UC7 & UC8 & UC9 & UC1
    SS[Siswa] --> UC8 & UC1
    OT[Orang Tua] --> UC8 & UC1
```

## 2. Activity Diagram (4 proses)

### 2.1 Login
```mermaid
flowchart TD
    A[Start] --> B[Masukkan Username & Password]
    B --> C{Valid?}
    C -->|Ya| D{Role?}
    D --> E[Redirect Dashboard]
    C -->|Tidak| F[Tampilkan Error]
    F --> B
    E --> G[End]
```

### 2.2 Input Nilai
```mermaid
flowchart TD
    A[Start] --> B[Guru Login]
    B --> C[Pilih Kelas & Mapel]
    C --> D[Input Nilai UH/PTS/PAS]
    D --> E{Simpan?}
    E -->|Ya| F[Hitung Nilai Akhir]
    F --> G[Catat Audit Log]
    G --> H[End]
    E -->|Tidak| I[Kembali]
    I --> D
```

### 2.3 Cetak Rapor
```mermaid
flowchart TD
    A[Start] --> B[Wali Kelas Login]
    B --> C[Pilih Semester & Kelas]
    C --> D[Generate Draft Rapor]
    D --> E{Validasi Data?}
    E -->|Lengkap| F[Kepala Sekolah Validasi]
    F --> G{Cetak?}
    G -->|Ya| H[Cetak Rapor]
    G -->|Tidak| I[Simpan Draft]
    E -->|Kurang| J[Lengkapi Data]
    J --> D
    H --> K[End]
```

### 2.4 Pembayaran SPP
```mermaid
flowchart TD
    A[Start] --> B[Orang Tua / Bendahara Input Pembayaran]
    B --> C{Cek Tagihan?}
    C -->|Ada| D[Input Jumlah & Metode]
    D --> E[Validasi Bendahara]
    E --> F{Sesuai?}
    F -->|Ya| G[Update Status Lunas]
    G --> H[Cetak Kwitansi]
    H --> I[End]
    F -->|Tidak| J[Koreksi Pembayaran]
    J --> B
    C -->|Tidak Ada| K[Tampilkan Pesan]
    K --> I
```

## 3. Sequence Diagram (4 proses)

### 3.1 Login
```mermaid
sequenceDiagram
    actor U as Pengguna
    participant W as Web Browser
    participant A as App Server
    participant DB as Database
    U->>W: Masukkan username & password
    W->>A: POST /login
    A->>DB: Cek username & hash password
    DB-->>A: Valid / Invalid
    A-->>W: Token sesi / Error
    W-->>U: Dashboard / Pesan gagal
```

### 3.2 Input Nilai
```mermaid
sequenceDiagram
    actor G as Guru
    participant W as Browser
    participant A as App Server
    participant DB as Database
    G->>W: Pilih kelas & mapel
    W->>A: GET daftar siswa
    A->>DB: Query siswa per kelas
    DB-->>A: Data siswa
    A-->>W: Tampilkan form nilai
    G->>W: Input & simpan nilai
    W->>A: POST nilai
    A->>A: Validasi & hitung akhir
    A->>DB: Simpan nilai + audit log
    DB-->>A: Sukses
    A-->>W: Notifikasi sukses
```

### 3.3 Cetak Rapor
```mermaid
sequenceDiagram
    actor W as Wali Kelas
    participant B as Browser
    participant A as App Server
    participant DB as Database
    participant R as Report Engine
    W->>B: Pilih kelas & semester
    B->>A: Request data rapor
    A->>DB: Query nilai, absensi, data siswa
    DB-->>A: Data rapor
    A->>R: Generate PDF rapor
    R-->>A: PDF rapor
    A-->>B: Tampilkan pratinjau
    W->>B: Cetak / unduh
    B->>A: Log pencetakan
    A->>DB: Simpan log
```

### 3.4 Pembayaran SPP
```mermaid
sequenceDiagram
    actor B as Bendahara
    participant W as Browser
    participant A as App Server
    participant DB as Database
    B->>W: Pilih siswa & bulan
    W->>A: GET tagihan
    A->>DB: Query tagihan aktif
    DB-->>A: Data tagihan
    A-->>W: Tampilkan detail
    B->>W: Input pembayaran
    W->>A: POST bayar
    A->>DB: Update status + simpan kwitansi
    DB-->>A: Sukses
    A-->>W: Tampilkan kwitansi
```

## 4. Class Diagram

```mermaid
classDiagram
    class User {
        +int id
        +string username
        +string password
        +string role
        +bool is_active
        +login()
        +logout()
    }
    class Siswa {
        +int id
        +string nis
        +string nama
        +int kelas_id
        +date tanggal_lahir
        +string alamat
        +lihatNilai()
        +lihatTagihan()
    }
    class Guru {
        +int id
        +string nip
        +string nama
        +inputNilai()
        +inputAbsensi()
    }
    class Kelas {
        +int id
        +string nama
        +int tingkat
        +int wali_kelas_id
        +int tahun_ajaran_id
    }
    class Nilai {
        +int id
        +int siswa_id
        +int mapel_id
        +decimal uh
        +decimal pts
        +decimal pas
        +decimal akhir
        +hitungAkhir()
    }
    class Pembayaran {
        +int id
        +int siswa_id
        +int jenis_id
        +decimal jumlah
        +date tanggal_bayar
        +string status
        +cetakKwitansi()
    }
    User <|-- Siswa : inherits data
    User <|-- Guru
    Guru "1" --> "0..*" Kelas : wali kelas
    Kelas "1" --> "0..*" Siswa : berisi
    Siswa "1" --> "0..*" Nilai : memiliki
    Siswa "1" --> "0..*" Pembayaran : melakukan
```

## Catatan

- Class diagram menunjukkan entitas utama; relasi lengkap ada di ERD.
- Method pada class disederhanakan untuk menjelaskan tanggung jawab utama.
