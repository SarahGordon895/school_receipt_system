<?php

namespace App\Http\Controllers;

use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Services\ParentPaymentNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $className = trim((string) $request->get('class_name', ''));
        $from = $request->get('from');
        $to = $request->get('to');
        $categoryId = $request->get('payment_category_id'); // NEW

        $receipts = Receipt::with(['user', 'paymentCategories'])
            ->when(
                $q !== '',
                fn($qb) =>
                $qb->where(
                    fn($qq) => $qq
                        ->where('student_name', 'like', "%{$q}%")
                        ->orWhere('receipt_no', 'like', "%{$q}%")
                )
            )
            ->when($className !== '', fn($qb) => $qb->where('class_name', 'like', "%{$className}%"))
            ->when($categoryId, fn($qb) => $qb->whereHas('paymentCategories', fn($pc) => $pc->where('payment_categories.id', $categoryId))) // NEW filter
            ->when($from, fn($qb) => $qb->whereDate('created_at', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = \App\Models\PaymentCategory::orderBy('name')->get(['id', 'name']); // NEW

        return view('receipts.index', compact(
            'receipts',
            'categories',
            'q',
            'className',
            'from',
            'to',
            'categoryId'
        ));
    }



    public function create()
    {
        $categories = PaymentCategory::orderBy('name')->get();
        return view('receipts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'exists:students,id'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'payment_categories' => ['required', 'array', 'min:1'],
            'payment_categories.*.category_id' => ['required', 'exists:payment_categories,id'],
            'payment_categories.*.amount' => ['required', 'integer', 'min:1'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', Rule::in(['Cash', 'Bank', 'Mobile Money', 'Other'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
            $data['student_name'] = $student?->name;
        }

        // Calculate total amount from all payment categories
        $totalAmount = array_sum(array_column($data['payment_categories'], 'amount'));
        $data['amount'] = $totalAmount;

        $data['user_id'] = $request->user()->id;

        $receipt = Receipt::create($data);

        // Sync payment categories with their amounts
        $categoriesWithAmounts = [];
        foreach ($data['payment_categories'] as $category) {
            $categoriesWithAmounts[$category['category_id']] = $category['amount'];
        }
        $receipt->syncPaymentCategories($categoriesWithAmounts);

        $receipt->load('paymentCategories');
        app(ParentPaymentNotifier::class)->notify($receipt);

        return redirect()
            ->to(route('receipts.show', $receipt) . '?print=1')
            ->with('status', 'Receipt generated!')
            ->withFragment('thermal-root');
    }

    public function edit(Receipt $receipt)
    {
        $categories = PaymentCategory::orderBy('name')->get();
        $receipt->load(['paymentCategories']);
        
        return view('receipts.edit', compact('receipt', 'categories'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'exists:students,id'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'payment_categories' => ['required', 'array', 'min:1'],
            'payment_categories.*.category_id' => ['required', 'exists:payment_categories,id'],
            'payment_categories.*.amount' => ['required', 'integer', 'min:1'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', Rule::in(['Cash', 'Bank', 'Mobile Money', 'Other'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
            $data['student_name'] = $student?->name;
        }

        // Calculate total amount from all payment categories
        $totalAmount = array_sum(array_column($data['payment_categories'], 'amount'));
        $data['amount'] = $totalAmount;

        $receipt->update($data);

        // Sync payment categories with their amounts
        $categoriesWithAmounts = [];
        foreach ($data['payment_categories'] as $category) {
            $categoriesWithAmounts[$category['category_id']] = $category['amount'];
        }
        $receipt->syncPaymentCategories($categoriesWithAmounts);

        return redirect()->route('receipts.show', $receipt)->with('status', 'Receipt updated successfully!');
    }

    public function partial(Request $request)
    {
        $request->merge(['page' => 1]); // reset to first page for instant search
        $this->index($request); // to reuse vars would require refactor; we’ll just repeat quickly:

        $q = trim((string) $request->get('q', ''));
        $className = trim((string) $request->get('class_name', ''));
        $from = $request->get('from');
        $to = $request->get('to');

        $receipts = Receipt::with(['user', 'paymentCategories'])
            ->when(
                $q !== '',
                fn($qb) =>
                $qb->where(
                    fn($qq) => $qq
                        ->where('student_name', 'like', "%{$q}%")
                        ->orWhere('receipt_no', 'like', "%{$q}%")
                )
            )
            ->when($className !== '', fn($qb) => $qb->where('class_name', 'like', "%{$className}%"))
            ->when($from, fn($qb) => $qb->whereDate('created_at', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('created_at', '<=', $to))
            ->latest()->paginate(15)->withQueryString();

        return view('receipts.partials.table', compact('receipts'))->render();
    }

    public function pdf(Receipt $receipt)
    {
        $receipt->load(['paymentCategories']);
        $pdf = Pdf::loadView('receipts.pdf', ['receipt' => $receipt]);
        $filename = $receipt->receipt_no . '.pdf';
        return $pdf->download($filename);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['paymentCategories']);

        return view('receipts.show', compact('receipt'));
    }

    public function destroy(Receipt $receipt)
    {
        $receipt->delete();
        return back()->with('status', 'Receipt deleted.');
    }
}
