# E23. Hasil Pengujian & Bug List

---

## Ringkasan Hasil Pengujian (Contoh)

| Metrik | Nilai |
| --- | --- |
| Total Test Case | 32 |
| Pass | 28 |
| Fail | 3 |
| Blocked | 1 |
| Bug Critical | 0 |
| Bug High | 1 |
| Bug Medium | 2 |
| Bug Low | 1 |
| Coverage Unit Test | 64% |

## Bug Tracker

| ID | Bug | Modul | Severity | Status | PIC | Solusi |
| --- | --- | --- | --- | --- | --- | --- |
| BUG-001 | Nilai akhir tidak terhitung jika PTS kosong | Nilai | High | Open | Backend | Tambahkan default 0 dengan warning |
| BUG-002 | Cetak rapor terpotong pada footer | Rapor | Medium | In Progress | Frontend | Atur margin PDF |
| BUG-003 | Notifikasi tagihan tidak terkirim pada malam hari | Notifikasi | Medium | Open | DevOps | Periksa cron job queue |
| BUG-004 | Tombol "Simpan" absensi masih aktif saat proses | Absensi | Low | Resolved | Frontend | Disable saat AJAX |
| BUG-005 | Layout dashboard berantakan di Safari | UI | Medium | Open | Frontend | Cek CSS flexbox |

## Prosedur Pelaporan Bug

1. Tester menemukan bug saat eksekusi test case.
2. Tester mengisi form bug dengan evidence/screenshot.
3. Developer menganalisis dan menetapkan severity.
4. Developer memperbaiki dan mengupdate status.
5. Tester melakukan re-test untuk verifikasi.
6. Bug ditutup jika re-test pass.

## Severity Level

| Severity | Kriteria |
| --- | --- |
| Critical | Sistem down, data hilang, keamanan fatal |
| High | Fitur utama tidak berfungsi |
| Medium | Fitur berfungsi dengan workaround atau tampilan tidak sesuai |
| Low | Cosmetic, typo, minor issue |
