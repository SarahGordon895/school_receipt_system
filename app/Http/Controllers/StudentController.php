<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\User;
use App\Support\ParentStudentAdmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $students = Student::with(['parentUser', 'primaryParentLink.parentUser'])
            ->when($q !== '', fn ($qb) => $qb
                ->where('name', 'like', "%{$q}%")
                ->orWhere('admission_no', 'like', "%{$q}%")
                ->orWhere('class_name', 'like', "%{$q}%"))
            ->withSum('receipts', 'amount')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact('students', 'q'));
    }

    public function create()
    {
        $feeStructures = FeeStructure::where('is_active', true)->orderBy('name')->get(['id', 'name', 'amount', 'class_name']);
        $parentAccounts = User::query()->where('role', 'parent')->orderBy('name')->get(['id', 'name', 'email']);

        return view('students.create', compact('feeStructures', 'parentAccounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedStudentData($request);

        $student = new Student($data);
        $student->admitted_at = now();
        $student->registered_by_user_id = $request->user()->id;
        $student->save();

        ParentStudentAdmission::linkGuardian(
            $student,
            (int) $data['parent_user_id'],
            $data['parent_relationship'],
            true,
            $data['parent_phone'] ?? null,
            $request->user()->id,
        );

        $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);

        return redirect()->route('students.index')->with('status', 'Student admitted and linked to parent portal account.');
    }

    public function edit(Student $student)
    {
        $feeStructures = FeeStructure::where('is_active', true)->orderBy('name')->get(['id', 'name', 'amount', 'class_name']);
        $parentAccounts = User::query()->where('role', 'parent')->orderBy('name')->get(['id', 'name', 'email']);
        $student->load(['feeStructures:id', 'primaryParentLink.linkedBy', 'registeredBy']);

        return view('students.edit', compact('student', 'feeStructures', 'parentAccounts'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validatedStudentData($request, $student);

        $student->fill($data);
        $student->save();

        ParentStudentAdmission::linkGuardian(
            $student,
            (int) $data['parent_user_id'],
            $data['parent_relationship'],
            true,
            $data['parent_phone'] ?? null,
            $request->user()->id,
        );

        $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);

        return redirect()->route('students.index')->with('status', 'Student and parent admission link updated.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('status', 'Student deleted.');
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->get('s', ''));

        return Student::when($term !== '', fn ($qb) => $qb
            ->where('name', 'like', "%{$term}%")
            ->orWhere('admission_no', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'class_name', 'parent_email']);
    }

    public function importForm()
    {
        return view('students.import');
    }

    public function importStore(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport, $data['file']);
        } else {
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

    private function validatedStudentData(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'admission_no' => ['nullable', 'string', 'max:100', Rule::unique('students', 'admission_no')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'parent_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'parent')],
            'parent_relationship' => ['required', 'string', Rule::in(\App\Models\StudentParentLink::RELATIONSHIPS)],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:50'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'fee_due_date' => ['nullable', 'date'],
            'expected_total_fee' => ['nullable', 'integer', 'min:0'],
            'fee_structure_ids' => ['nullable', 'array'],
            'fee_structure_ids.*' => ['exists:fee_structures,id'],
        ]);

        $data['expected_total_fee'] = (int) ($data['expected_total_fee'] ?? 0);

        return $data;
    }
}
