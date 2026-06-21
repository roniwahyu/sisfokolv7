<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $pembayaran->no_nota }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-cell {
            width: 70px;
        }
        .school-info {
            text-align: left;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .school-meta {
            font-size: 11px;
            color: #666;
            margin: 3px 0 0 0;
        }
        .kwitansi-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 15px 0;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .label {
            color: #666;
            width: 120px;
        }
        .colon {
            width: 15px;
            text-align: center;
        }
        .value {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f5f5f5;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 8px 10px;
            font-size: 12px;
            text-transform: uppercase;
            color: #444;
            text-align: left;
        }
        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 10px;
        }
        .text-right {
            text-align: right;
        }
        .total-row td {
            border-top: 2px solid #ddd;
            border-bottom: 2px solid #ddd;
            font-size: 14px;
            font-weight: bold;
            padding: 12px 10px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
        }
        .signature-space {
            height: 70px;
        }
        .footer-note {
            margin-top: 50px;
            font-size: 10px;
            color: #888;
            text-align: center;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Kop Surat (Header) -->
    <table class="header-table">
        <tr>
            <td class="school-info">
                <div class="school-name">Sisfokol Modular</div>
                <div class="school-meta">Sistem Informasi Manajemen Sekolah Terintegrasi</div>
                <div class="school-meta">Tenant NPSN: {{ auth()->user()->tenant->npsn ?? '-' }} • Tenant ID: {{ $pembayaran->tenant_id }}</div>
            </td>
            <td class="text-right" style="vertical-align: middle;">
                <div style="font-size: 14px; font-weight: bold; color: #4f46e5;">BUKTI PEMBAYARAN</div>
                <div style="font-size: 11px; color: #888; margin-top: 3px;">No: {{ $pembayaran->no_nota }}</div>
            </td>
        </tr>
    </table>

    <!-- Keterangan Transaksi -->
    <table class="meta-table">
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="colon">:</td>
            <td class="value">{{ $pembayaran->siswa->nama }}</td>
            
            <td class="label" style="padding-left: 50px;">Tanggal Nota</td>
            <td class="colon">:</td>
            <td class="value">{{ $pembayaran->created_at->setTimezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td class="colon">:</td>
            <td class="value">{{ $pembayaran->siswa->nis }}</td>

            <td class="label" style="padding-left: 50px;">Penerima (Kasir)</td>
            <td class="colon">:</td>
            <td class="value">{{ $pembayaran->bendahara->name ?? 'Administrator' }}</td>
        </tr>
    </table>

    <div class="kwitansi-title">Rincian Pos Pembayaran</div>

    <!-- Rincian Item yang Dibayar -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Pos Pembayaran</th>
                <th style="width: 25%;">Periode / Bulan</th>
                <th style="width: 25%; text-align: right;">Jumlah Dibayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembayaran->rincian as $index => $r)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $r->tagihanSiswa->itemPembayaran->nama }}</strong>
                        <div style="font-size: 10px; color: #777; margin-top: 2px;">Tahun Ajaran: {{ $r->tagihanSiswa->tahunAjaran->nama }}</div>
                    </td>
                    <td>
                        {{ $r->tagihanSiswa->itemPembayaran->periode === 'bulanan' ? carbon_month_name($r->tagihanSiswa->bulan) : 'Sekali Bayar' }}
                    </td>
                    <td class="text-right" style="font-weight: 600;">
                        Rp {{ number_format($r->jumlah, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL PENERIMAAN</td>
                <td class="text-right" style="color: #4f46e5;">Rp {{ number_format($pembayaran->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div style="color: #666; font-size: 11px;">Penyetor / Wali Murid</div>
                <div class="signature-space"></div>
                <div style="font-weight: bold; border-bottom: 1px solid #ccc; display: inline-block; width: 150px;"></div>
            </td>
            <td class="signature-cell">
                <div style="color: #666; font-size: 11px;">Bendahara Sekolah,</div>
                <div class="signature-space"></div>
                <div style="font-weight: bold; text-decoration: underline;">{{ $pembayaran->bendahara->name ?? 'Penerima Kasir' }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    <div class="footer-note">
        Terima kasih atas pembayaran Anda. Simpan bukti kwitansi ini sebagai bukti pembayaran yang sah.<br>
        Dicetak secara otomatis oleh Sistem Sisfokol Modular.
    </div>

</body>
</html>
