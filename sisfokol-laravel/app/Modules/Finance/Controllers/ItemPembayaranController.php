<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Models\TahunAjaran;
use App\Modules\Finance\Models\ItemPembayaran;
use App\Modules\Finance\Requests\StoreItemPembayaranRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItemPembayaranController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ItemPembayaran::class);

        $search = $request->input('search');
        $query = ItemPembayaran::query();

        if ($search) {
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('jenis', 'like', "%{$search}%");
        }

        $items = $query->with('tahunAjaran')->latest()->paginate(15)->withQueryString();

        return view('finance.item-pembayaran.index', compact('items', 'search'));
    }

    public function create()
    {
        Gate::authorize('create', ItemPembayaran::class);
        $tahunAjaran = TahunAjaran::where('aktif', true)->get();
        return view('finance.item-pembayaran.form', [
            'item' => new ItemPembayaran(),
            'tahunAjaran' => $tahunAjaran,
            'isEdit' => false,
        ]);
    }

    public function store(StoreItemPembayaranRequest $request)
    {
        Gate::authorize('create', ItemPembayaran::class);

        $item = ItemPembayaran::create(array_merge($request->validated(), [
            'aktif' => $request->has('aktif') ? true : false,
        ]));

        return redirect()
            ->route('finance.item-pembayaran.index')
            ->with('success', "Item pembayaran {$item->nama} berhasil ditambahkan.");
    }

    public function edit(ItemPembayaran $item_pembayaran)
    {
        Gate::authorize('update', $item_pembayaran);
        $tahunAjaran = TahunAjaran::all();
        return view('finance.item-pembayaran.form', [
            'item' => $item_pembayaran,
            'tahunAjaran' => $tahunAjaran,
            'isEdit' => true,
        ]);
    }

    public function update(StoreItemPembayaranRequest $request, ItemPembayaran $item_pembayaran)
    {
        Gate::authorize('update', $item_pembayaran);

        $item_pembayaran->update(array_merge($request->validated(), [
            'aktif' => $request->has('aktif') ? true : false,
        ]));

        return redirect()
            ->route('finance.item-pembayaran.index')
            ->with('success', "Item pembayaran {$item_pembayaran->nama} berhasil diperbarui.");
    }

    public function destroy(ItemPembayaran $item_pembayaran)
    {
        Gate::authorize('delete', $item_pembayaran);

        $item_pembayaran->delete();

        return redirect()
            ->route('finance.item-pembayaran.index')
            ->with('success', "Item pembayaran {$item_pembayaran->nama} berhasil dihapus.");
    }
}
