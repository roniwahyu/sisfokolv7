# G30. Maintenance Plan

---

## Jadwal Maintenance

| Jenis Maintenance | Frekuensi | Waktu | Kegiatan | PIC | Dampak Operasional |
| --- | --- | --- | --- | --- | --- |
| Preventive (periksa log & backup) | Harian | Pagi | Cek log error, status backup, ruang disk | Tim IT | Minimal |
| Patch update (framework & OS) | Mingguan | Sabtu/Minggu malam | Update security patch, restart server | Tim IT | Maintenance window |
| Database optimization | Bulanan | Akhir bulan | Analisis query lambat, index, cleanup | Tim IT | Maintenance window |
| Review user & RBAC | Bulanan | Awal bulan | Audit user aktif, reset password lama | TU + Tim IT | Minimal |
| Full system health check | 3 bulanan | Awal kuartal | Performance test, security scan | Tim IT | Maintenance window |
| Major release | Sesuai roadmap | Di luar jam aktif | Deploy fitur baru (Fase 2) | Tim IT + BA | Maintenance window |

## SLA (Service Level Agreement)

| Parameter | Target |
| --- | --- |
| Uptime | 98% per bulan |
| Response time halaman dashboard | < 3 detik |
| Response time laporan | < 10 detik |
| Waktu respon tiket bug High | < 4 jam |
| Waktu respon tiket bug Medium | < 1 hari kerja |
| Waktu respon tiket bug Low | < 3 hari kerja |
| Backup failure response | < 1 jam |

## Prosedur Maintenance

1. Buat pengumuman maintenance kepada pengguna minimal 2 hari sebelumnya.
2. Aktifkan maintenance mode.
3. Backup database dan file aplikasi sebelum perubahan.
4. Jalankan update/patch.
5. Lakukan smoke test setelah update.
6. Matikan maintenance mode dan informasikan pengguna.

## Eskalasi Masalah

| Level | Kondisi | Tindakan |
| --- | --- | --- |
| 1 | Bug minor | Ditangani developer dalam SLA Medium/Low |
| 2 | Bug High / fitur tidak berfungsi | Tim IT segera perbaiki, informasi kepala sekolah |
| 3 | Critical / data hilang / sistem down | Aktifkan BCP, restore backup, laporan ke kepala sekolah |
