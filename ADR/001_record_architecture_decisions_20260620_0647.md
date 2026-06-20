# ADR-001: Record Architecture Decisions

- **Tanggal:** 2026-06-20 06:47
- **Status:** Diterima (Accepted)
- **Konteks:** Proyek konversi SISFOKOL v7 → Laravel 11 (folder `sisfokol-laravel`)

## Konteks

Proyek ini berskala luas (75 tabel legacy, 8+ domain, multi-tenant SaaS). Banyak keputusan desain saling bergantung dan tersebar di banyak sesi diskusi. Tanpa rekam jejak formal, keputusan mudah hilang atau dikontradiksi di kemudian hari.

## Keputusan

Setiap keputusan arsitektur yang signifikan dicatat sebagai **Architecture Decision Record (ADR)** dalam folder `ADR/` dengan konvensi penamaan:

```
ADR/0xx_nama_singkat_timestamp.md
       │     │            │
       │     │            └─ YYYYMMDD_HHMM (UTC+7)
       │     └─ snake_case, ringkas
       └─ nomor urut 3 digit
```

Setiap ADR mengikuti template Michael Nygard:
- **Status** (Proposed / Accepted / Superseded / Deprecated)
- **Konteks** (mengapa dipertimbangkan)
- **Keputusan** (apa yang diputuskan)
- **Konsekuensi** (positif, negatif, netral)

## Konsekuensi

- ✅ Jejak audit keputusan; onboarding tim baru lebih cepat
- ✅ Mencegah re-debat keputusan yang sudah final
- ⚠️ Overhead menulis ADR untuk tiap keputusan — dimitigasi dengan menjaga ADR ringkas dan fokus
- 🔄 Bila keputusan berubah, ADR lama diberi status **Superseded** + link ke ADR pengganti (bukan dihapus)
