<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Pembayaran;
use App\Modules\Finance\Models\PembayaranRincian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request)
    {
        // We can authorize using a generic finance report gate
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->can('finance.report.*') && !auth()->user()->can('finance.*')) {
            abort(403);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // 1. Total Pemasukan header
        $totalPemasukan = Pembayaran::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->sum('total');

        // 2. Transaksi Hari Ini
        $transaksiHariIni = Pembayaran::whereDate('created_at', now()->toDateString())->count();
        $nominalHariIni = Pembayaran::whereDate('created_at', now()->toDateString())->sum('total');

        // 3. Rincian Pemasukan per Item Pembayaran
        $pemasukanPerItem = PembayaranRincian::select(
                'item_pembayaran.nama as nama_item',
                'item_pembayaran.jenis as jenis_item',
                DB::raw('SUM(pembayaran_rincian.jumlah) as total_jumlah')
            )
            ->join('tagihan_siswa', 'pembayaran_rincian.tagihan_siswa_id', '=', 'tagihan_siswa.id')
            ->join('item_pembayaran', 'tagihan_siswa.item_pembayaran_id', '=', 'item_pembayaran.id')
            ->whereBetween(DB::raw('DATE(pembayaran_rincian.created_at)'), [$startDate, $endDate])
            ->groupBy('item_pembayaran.id', 'item_pembayaran.nama', 'item_pembayaran.jenis')
            ->get();

        // 4. Riwayat Transaksi Terbaru dalam range tanggal
        $transaksiTerbaru = Pembayaran::with(['siswa', 'bendahara'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->latest()
            ->limit(10)
            ->get();

        return view('finance.laporan.index', compact(
            'startDate',
            'endDate',
            'totalPemasukan',
            'transaksiHariIni',
            'nominalHariIni',
            'pemasukanPerItem',
            'transaksiTerbaru'
        ));
    }
}
