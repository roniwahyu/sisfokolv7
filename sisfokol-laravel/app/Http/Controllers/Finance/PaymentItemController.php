<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentItemRequest;
use App\Models\AcademicYear;
use App\Models\PaymentItem;
use Illuminate\Http\Request;

class PaymentItemController extends Controller
{
    public function index()
    {
        $paymentItems = PaymentItem::with('academicYear')->latest()->paginate(20);

        return view('finance.payment-items.index', compact('paymentItems'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();

        return view('finance.payment-items.create', compact('academicYears'));
    }

    public function store(StorePaymentItemRequest $request)
    {
        PaymentItem::create($request->validated());

        return redirect()->route('finance.payment-items.index')->with('success', 'Item pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentItem $paymentItem)
    {
        $academicYears = AcademicYear::all();

        return view('finance.payment-items.edit', compact('paymentItem', 'academicYears'));
    }

    public function update(StorePaymentItemRequest $request, PaymentItem $paymentItem)
    {
        $paymentItem->update($request->validated());

        return redirect()->route('finance.payment-items.index')->with('success', 'Item pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentItem $paymentItem)
    {
        $paymentItem->delete();

        return redirect()->route('finance.payment-items.index')->with('success', 'Item pembayaran berhasil dihapus.');
    }
}
