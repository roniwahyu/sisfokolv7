# ADR-008: DEV_DOCS sebagai Memory & Handoff Antar Agent

- **Tanggal:** 2026-06-20 07:13
- **Status:** Diterima (Accepted)

## Konteks

Proyek `sisfokol-laravel` dikerjakan lintas sesi dan kemungkinan lintas agent (multi-agent workflow, handoff session ke session). Tanpa memori tertulis, agent berikutnya harus membaca ulang semua dokumen (mahal) atau kehilangan konteks keputusan yang sudah final. ADR mencatat *keputusan*, tetapi *proses diskusi & alasan* perlu media terpisah.

## Keputusan

Dua kanal dokumentasi yang wajib diisi setiap sesi:

### 1. `ADR/` — Architecture Decision Records
- **Isi:** keputusan final yang mengikat (apa yang diputuskan + konsekuensi)
- **Format:** `0xx_nama_keputusan_timestamp.md` (lihat ADR-001)
- **Sifat:** immutable setelah Accepted; bila berubah → status `Superseded` + link ADR pengganti
- **Kapan ditulis:** setiap kali ada keputusan desain yang mengikat

### 2. `DEV_DOCS/` — Dev Memory & Handoff Notes
- **Isi:** rangkuman diskusi, rincian desain per bagian (Bagian 1–6), progres, alasan, pertanyaan terbuka, next step
- **Format:** `00x_namafile_timestamp.md` (3 digit urut)
- **Sifat:** append-friendly; tiap sesi bisa tambah file baru dengan nomor urut lanjut
- **Kapan ditulis:** setiap kali saya (agent) menyampaikan rincian desain besar atau menyelesaikan tahap
- **Tujuan ganda:**
  - **Memory** — agent yang sama di sesi berikutnya bisa melanjutkan tanpa re-derive
  - **Handoff** — agent lain (subagent, sesi baru) memahami konteks & progress

## Konvensi penamaan DEV_DOCS

```
DEV_DOCS/00x_bagian_topik_timestamp.md
          │  │      │
          │  │      └─ ringkas topik
          │  └─ Bagian ke berapa (sesuai alur desain) atau tahap
          └─ nomor urut 3 digit
```

## Aturan komunikasi antar agent

- Agent berikutnya **wajib** membaca `ADR/*` + `DEV_DOCS/*` terbaru sebelum bertindak.
- Bila ada keputusan yang tampak kontradiksi, cek ADR-nya; bila ADR `Superseded`, ikuti penggantinya.
- Progress tracking memakai `TodoWrite` internal sesi + ringkasan di DEV_DOCS terakhir.

## Konsekuensi

- ✅ Konteks tidak hilang antar sesi/agent
- ✅ Handoff jadi eksplisit & dapat diaudit
- ✅ ADR + DEV_DOCS saling melengkapi: *keputusan* vs *alasan/proses*
- ⚠️ Overhead menulis — dimitigasi dengan menjaga ringkas & fokus
- 🔄 Bila format berubah, update ADR ini (status Superseded)
