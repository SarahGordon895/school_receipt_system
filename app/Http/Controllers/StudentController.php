<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $students = Student::when($q !== '', fn($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('students.index', compact('students', 'q'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        Student::create($data);
        return redirect()->route('students.index')->with('status', 'Student added.');
    }

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $student->update($data);
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
        return Student::when($term !== '', fn($qb) => $qb->where('name', 'like', "%{$term}%"))
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
