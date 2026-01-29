<?php

namespace App\Http\Controllers;

use App\Models\PaymentCategory;
use Illuminate\Http\Request;

class PaymentCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $cats = PaymentCategory::when($q !== '', fn($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('payment_categories.index', compact('cats', 'q'));
    }

    public function create()
    {
        return view('payment_categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:payment_categories,name'],
            'default_amount' => ['nullable', 'integer', 'min:0'],
        ]);
        PaymentCategory::create($data);
        return redirect()->route('payment-categories.index')->with('status', 'Category added.');
    }

    public function edit(PaymentCategory $payment_category)
    {
        return view('payment_categories.edit', ['cat' => $payment_category]);
    }

    public function update(Request $request, PaymentCategory $payment_category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:payment_categories,name,' . $payment_category->id],
            'default_amount' => ['nullable', 'integer', 'min:0'],
        ]);
        $payment_category->update($data);
        return redirect()->route('payment-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(PaymentCategory $payment_category)
    {
        $payment_category->delete();
        return back()->with('status', 'Category deleted.');
    }
}
