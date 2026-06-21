# E21. Test Plan

---

## 1. Tujuan Pengujian

Memastikan Sistem Informasi Sekolah SMP Islam Terpadu berfungsi sesuai SRS, aman, dan siap digunakan oleh seluruh stakeholder.

## 2. Lingkup Pengujian

- Modul autentikasi dan RBAC.
- Modul data master (siswa, guru, kelas, mapel).
- Modul akademik (jadwal, absensi, nilai, rapor).
- Modul keuangan (SPP, infaq, laporan).
- Portal siswa/orang tua.
- Performa, keamanan, dan kompatibilitas.

## 3. Jenis Pengujian & Strategi

| Jenis Pengujian | Fokus | Strategi | Tools | Tahap |
| --- | --- | --- | --- | --- |
| Unit Testing | Fungsi individual | White-box | PHPUnit | Development |
| Integration Testing | Integrasi antar modul | Black-box | PHPUnit + Postman | Development |
| Functional Testing | Kebutuhan fungsional SRS | Black-box | Manual + TestCase | QA |
| UI/UX Testing | Antarmuka & usability | Heuristic | Browser + Mobile | QA |
| Performance Testing | Beban & kecepatan | Load test | Apache JMeter / k6 | Staging |
| Security Testing | Kerentanaman | OWASP Top 10 | Burp Suite / OWASP ZAP | Staging |
| Compatibility Testing | Browser & perangkat | Cross-browser | Chrome, Firefox, Edge, Safari | QA |
| Regression Testing | Fitur lama tetap berfungsi | Re-test setelah perubahan | Manual + Otomatis | Pre-release |
| User Acceptance Test (UAT) | Persetujuan pengguna | Skenario bisnis | Form UAT | Pre-go-live |

## 4. Kriteria Kelulusan

- 100% test case High priority lulus.
- Tidak ada bug Critical atau High yang terbuka.
- Coverage unit test minimal 60%.
- UAT disetujui oleh minimal 80% peserta.
- Performance: load halaman dashboard < 3 detik untuk 100 user simultan.

## 5. Jadwal Pengujian

| Fase | Durasi | Kegiatan |
| --- | --- | --- |
| Unit & Integration | 2 minggu | Development sprint |
| Functional & UI | 2 minggu | QA testing |
| Performance & Security | 1 minggu | Staging environment |
| UAT | 1 minggu | Pengguna akhir |

## 6. Tim Pengujian

| Peran | Tanggung Jawab |
| --- | --- |
| QA Lead | Menyusun test plan, test case, dan laporan |
| Developer | Unit test & perbaikan bug |
| Business Analyst | Memastikan skenario bisnis tercakup |
| End User | Melakukan UAT |
