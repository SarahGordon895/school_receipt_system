<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $feeStructures = FeeStructure::query()
            ->when($q !== '', fn($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('fee-structures.index', compact('feeStructures', 'q'));
    }

    public function create()
    {
        return view('fee-structures.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        FeeStructure::create($data);

        return redirect()->route('fee-structures.index')->with('status', 'Fee structure created.');
    }

    public function edit(FeeStructure $feeStructure)
    {
        return view('fee-structures.edit', compact('feeStructure'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $feeStructure->update($data);

        return redirect()->route('fee-structures.index')->with('status', 'Fee structure updated.');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();
        return back()->with('status', 'Fee structure deleted.');
    }
}
