<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FeeStructure;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $students = Student::when($q !== '', fn($qb) => $qb
                ->where('name', 'like', "%{$q}%")
                ->orWhere('admission_no', 'like', "%{$q}%")
                ->orWhere('class_name', 'like', "%{$q}%"))
            ->withSum('receipts', 'amount')
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('students.index', compact('students', 'q'));
    }

    public function create()
    {
        $feeStructures = FeeStructure::where('is_active', true)->orderBy('name')->get(['id', 'name', 'amount']);
        return view('students.create', compact('feeStructures'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'admission_no' => ['nullable', 'string', 'max:100', 'unique:students,admission_no'],
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'fee_due_date' => ['nullable', 'date'],
            'expected_total_fee' => ['nullable', 'integer', 'min:0'],
            'fee_structure_ids' => ['nullable', 'array'],
            'fee_structure_ids.*' => ['exists:fee_structures,id'],
        ]);

        $data['expected_total_fee'] = (int) ($data['expected_total_fee'] ?? 0);
        $student = Student::create($data);
        $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);
        return redirect()->route('students.index')->with('status', 'Student added.');
    }

    public function edit(Student $student)
    {
        $feeStructures = FeeStructure::where('is_active', true)->orderBy('name')->get(['id', 'name', 'amount']);
        $student->load('feeStructures:id');
        return view('students.edit', compact('student', 'feeStructures'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'admission_no' => ['nullable', 'string', 'max:100', 'unique:students,admission_no,' . $student->id],
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'fee_due_date' => ['nullable', 'date'],
            'expected_total_fee' => ['nullable', 'integer', 'min:0'],
            'fee_structure_ids' => ['nullable', 'array'],
            'fee_structure_ids.*' => ['exists:fee_structures,id'],
        ]);
        $data['expected_total_fee'] = (int) ($data['expected_total_fee'] ?? 0);
        $student->update($data);
        $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);
        return redirect()->route('students.index')->with('status', 'Student updated.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return back()->with('status', 'Student deleted.');
    }

    // AJAX search (for big lists)
    public function search(Request $request)
    {
        $term = trim((string) $request->get('s', ''));
        return Student::when($term !== '', fn($qb) => $qb
            ->where('name', 'like', "%{$term}%")
            ->orWhere('admission_no', 'like', "%{$term}%"))
            ->orderBy('name')->limit(20)->get(['id', 'name']);
    }

    public function importForm()
    {
        return view('students.import');
    }

    public function importStore(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt']
        ]);

        // OPTION A: Laravel Excel (recommended)
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport, $data['file']);
        } else {
            // OPTION B: simple CSV fallback (first column = name)
            $path = $data['file']->getRealPath();
            $h = fopen($path, 'r');
            while (($row = fgetcsv($h)) !== false) {
                $name = trim((string) ($row[0] ?? ''));
                if ($name !== '') {
                    Student::firstOrCreate(['name' => $name]);
                }
            }
            fclose($h);
        }

        return redirect()->route('students.index')->with('status', 'Students imported.');
    }
}
