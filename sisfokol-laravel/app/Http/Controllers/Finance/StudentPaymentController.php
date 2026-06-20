<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentPaymentRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentPayment;
use App\Models\StudentPaymentDetail;
use App\Models\Treasurer;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPaymentController extends Controller
{
    public function __construct(
        private FinanceService $financeService
    ) {}

    public function index()
    {
        $payments = StudentPayment::with(['student', 'treasurer'])->latest()->paginate(20);

        return view('finance.student-payments.index', compact('payments'));
    }

    public function create()
    {
        $academicYears = AcademicYear::all();
        $students = Student::all();
        $treasurers = Treasurer::with('employee')->get();

        return view('finance.student-payments.create', compact('academicYears', 'students', 'treasurers'));
    }

    public function store(StoreStudentPaymentRequest $request)
    {
        $data = $request->validated();
        $data['invoice_number'] = $this->financeService->generateInvoiceNumber();
        $data['treasurer_id'] = Auth::user()->userable?->treasurer?->id;

        $payment = StudentPayment::create($data);

        foreach ($data['bills'] as $billId => $amount) {
            if ($amount > 0) {
                StudentPaymentDetail::create([
                    'student_payment_id' => $payment->id,
                    'student_bill_id' => $billId,
                    'amount' => $amount,
                ]);

                $bill = StudentBill::find($billId);
                $bill->paid += $amount;
                $bill->updateStatus();
                $bill->save();
            }
        }

        return redirect()->route('finance.student-payments.index')->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function show(StudentPayment $studentPayment)
    {
        $studentPayment->load(['student', 'details.studentBill.paymentItem', 'treasurer.employee']);

        return view('finance.student-payments.show', compact('studentPayment'));
    }
}
