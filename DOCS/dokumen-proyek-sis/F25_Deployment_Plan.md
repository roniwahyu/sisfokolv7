# F25. Deployment Plan

---

## Timeline / Gantt Chart Implementasi

```mermaid
gantt
    title Timeline Implementasi Sistem Informasi Sekolah SMP IT
    dateFormat  YYYY-MM-DD
    section Perencanaan
    Inisiasi & Charter       :done, plan1, 2026-07-01, 2w
    Analisis Kebutuhan      :done, plan2, after plan1, 2w
    section Desain
    Desain Database & Arsitektur :des1, after plan2, 3w
    UI/UX Design             :des2, after plan2, 2w
    UML & Dokumentasi Detail :des3, after des1, 2w
    section Pengembangan
    Sprint 1: Master & Auth  :dev1, after des3, 2w
    Sprint 2: Akademik       :dev2, after dev1, 3w
    Sprint 3: Keuangan       :dev3, after dev2, 2w
    Sprint 4: Portal & Laporan :dev4, after dev3, 3w
    Sprint 5: Integrasi & Polish :dev5, after dev4, 2w
    section Testing
    Unit & Integration Test  :test1, after dev2, 4w
    Functional & Security Test :test2, after dev5, 2w
    UAT                      :test3, after test2, 1w
    section Deployment
    Data Migration           :dep1, after test3, 1w
    Training                 :dep2, after dep1, 1w
    Go-Live                  :milestone, dep3, after dep2, 0d
    Stabilization            :dep4, after dep3, 2w
```

## Milestone Utama

| Milestone | Target Tanggal | Deliverable |
| --- | --- | --- |
| Project Kick-off | 1 Juli 2026 | Project Charter, Tim Terbentuk |
| Desain Selesai | 11 September 2026 | SRS, ERD, UI/UX, UML |
| Pengembangan Selesai | 11 Desember 2026 | Semua modul High priority |
| QA Selesai | 18 Desember 2026 | Test report, bug closed |
| UAT Approved | 26 Desember 2026 | Tanda tangan UAT |
| Go-Live | 1 Januari 2027 | Sistem live untuk semester genap |

## Rencana Deployment

1. **Persiapan Infrastruktur**: Siapkan server production, database, SSL, dan domain.
2. **Deployment Aplikasi**: Clone/pull code dari repository production, jalankan migration, seed data minimal.
3. **Data Migration**: Import data siswa, guru, kelas dari Excel/manual.
4. **Konfigurasi**: Atur environment, cron job, backup, dan monitoring.
5. **Training**: Pelatihan pengguna per role.
6. **Soft Launch**: Akses terbatas selama 1 minggu untuk stabilisasi.
7. **Full Go-Live**: Semua pengguna aktif.

## Rollback Plan

Jika terjadi kegagalan kritis pada go-live:
- Aktifkan maintenance mode.
- Pulihkan database dari backup terakhir.
- Kembalikan ke versi stabil sebelumnya.
- Komunikasikan ke seluruh pengguna via pengumuman resmi.
