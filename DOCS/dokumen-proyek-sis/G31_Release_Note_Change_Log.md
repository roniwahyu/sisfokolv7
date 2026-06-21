# G31. Release Note & Change Log

---

## Riwayat Versi

| Versi | Tanggal | Penulis | Fitur / Perubahan | Bug Fix | Status |
| --- | --- | --- | --- | --- | --- |
| v0.1.0 | 15 Jul 2026 | Tim IT | Project setup, struktur folder, autentikasi awal | - | Internal |
| v0.2.0 | 05 Agu 2026 | Tim IT | Modul data master (siswa, guru, kelas, mapel) | - | Alpha |
| v0.3.0 | 25 Agu 2026 | Tim IT | Jadwal, absensi, tahun ajaran | Fix validasi bentrok jadwal | Alpha |
| v0.4.0 | 15 Sep 2026 | Tim IT | Input nilai, perhitungan akhir | - | Beta |
| v0.5.0 | 05 Okt 2026 | Tim IT | Cetak rapor, dashboard | Fix margin PDF | Beta |
| v0.6.0 | 25 Okt 2026 | Tim IT | Pembayaran SPP, kwitansi, laporan keuangan | Fix status tagihan | Beta |
| v0.7.0 | 15 Nov 2026 | Tim IT | Portal siswa & orang tua | Fix hak akses ortu | Beta |
| v0.8.0 | 05 Des 2026 | Tim IT | Audit log, notifikasi, RBAC lengkap | Fix notifikasi malam | RC |
| v1.0.0 | 01 Jan 2027 | Tim IT | **Go-Live Fase 1** — semua modul High | Semua bug High closed | Production |
| v1.1.0 | 15 Feb 2027 | Tim IT | Peningkatan UI dashboard, export laporan otomatis | Minor CSS Safari | Production |
| v1.2.0 | 01 Apr 2027 | Tim IT | Modul notifikasi WhatsApp, dark mode | - | Production |
| v2.0.0 | 01 Jul 2027 | Tim IT | **Fase 2**: E-PPDB, E-Learning, integrasi Dapodik | - | Planned |

## Format Changelog

Setiap entri changelog mencakup:
- **Added**: Fitur baru.
- **Changed**: Perubahan pada fitur existing.
- **Fixed**: Perbaikan bug.
- **Deprecated**: Fitur yang akan dihapus.
- **Removed**: Fitur yang dihapus.
- **Security**: Perbaikan terkait keamanan.

## Contoh Release Note v1.0.0

### What's New
- Semua modul Fase 1 telah aktif.
- Dashboard real-time untuk kepala sekolah.
- Multi-role portal untuk siswa dan orang tua.
- Audit log dan backup otomatis.

### Known Issues
- Notifikasi WhatsApp masih terbatas pada operator tertentu.
- Dark mode belum tersedia di Fase 1.

### Breaking Changes
- Tidak ada (Fase 1 adalah baseline).

## Prosedur Release

1. Persiapkan branch `release/x.x.x`.
2. Jalankan regression test.
3. Update changelog dan release note.
4. Merge ke `main`.
5. Deploy ke production sesuai deployment plan.
6. Umumkan release kepada pengguna.
