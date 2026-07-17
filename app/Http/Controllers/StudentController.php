<?php

namespace App\Http\Controllers;

use App\Data\StudentImportResult;
use App\Mail\ParentWelcomeMailable;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentReminderService;
use App\Services\StudentImportService;
use App\Support\ParentStudentAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

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
        $parentAccounts = User::query()->where('role', 'parent')->orderBy('name')->get(['id', 'name', 'email', 'phone']);

        return view('students.create', compact('feeStructures', 'parentAccounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedStudentData($request);

        $result = DB::transaction(function () use ($request, $data) {
            $parent = $this->resolveParentUser($data);

            $student = new Student(collect($data)->except($this->nonStudentFields())->all());
            $student->admitted_at = now();
            $student->registered_by_user_id = $request->user()->id;
            $student->save();

            if (($data['parent_mode'] ?? 'existing') === 'existing' && filled($data['portal_login_email'] ?? null)) {
                $parent = ParentStudentAdmission::updateParentPortalEmail($parent->id, $data['portal_login_email']);
            }

            ParentStudentAdmission::linkGuardian(
                $student,
                $parent->id,
                $data['parent_relationship'],
                true,
                $data['parent_phone'],
                $request->user()->id,
            );

            $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);

            return ['student' => $student, 'parent' => $parent];
        });

        $student = $result['student'];
        $parent = $result['parent'];
        $welcomeEmailSent = null;

        if ($data['parent_mode'] === 'new') {
            $welcomeEmailSent = $this->sendParentWelcomeEmail(
                $parent,
                $student,
                $data['parent_password'],
            );
        }

        $student->loadSum('receipts', 'amount');
        app(ParentReminderService::class)->notifyAdmission($student->fresh());

        $response = redirect()->route('students.index')->with(
            'status',
            $welcomeEmailSent === true
                ? 'Student admitted and parent account created. Login details were emailed to the parent.'
                : 'Student admitted and linked to parent portal account. Fee reminder sent to parent if applicable.'
        );

        if ($welcomeEmailSent === false) {
            $response->with('warning', 'The parent account was created, but the welcome email could not be sent. Verify the mail settings and provide the login details securely.');
        }

        return $response;
    }

    public function edit(Student $student)
    {
        $feeStructures = FeeStructure::where('is_active', true)->orderBy('name')->get(['id', 'name', 'amount', 'class_name']);
        $parentAccounts = User::query()->where('role', 'parent')->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $student->load(['feeStructures:id', 'primaryParentLink.linkedBy', 'registeredBy', 'parentUser']);

        return view('students.edit', compact('student', 'feeStructures', 'parentAccounts'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validatedStudentData($request, $student);

        $parent = DB::transaction(function () use ($request, $student, $data) {
            $parent = $this->resolveParentUser($data);

            $student->fill(collect($data)->except($this->nonStudentFields())->all());
            $student->save();

            if (($data['parent_mode'] ?? 'existing') === 'existing' && filled($data['portal_login_email'] ?? null)) {
                $parent = ParentStudentAdmission::updateParentPortalEmail($parent->id, $data['portal_login_email']);
            }

            ParentStudentAdmission::linkGuardian(
                $student,
                $parent->id,
                $data['parent_relationship'],
                true,
                $data['parent_phone'],
                $request->user()->id,
            );

            $student->feeStructures()->sync($data['fee_structure_ids'] ?? []);

            return $parent;
        });

        $welcomeEmailSent = null;
        if ($data['parent_mode'] === 'new') {
            $welcomeEmailSent = $this->sendParentWelcomeEmail(
                $parent,
                $student,
                $data['parent_password'],
            );
        }

        $response = redirect()
            ->route('students.edit', $student)
            ->with(
                'status',
                $welcomeEmailSent === true
                    ? 'Student updated and new parent login details were emailed.'
                    : 'Student and parent contact details updated.'
            );

        if ($welcomeEmailSent === false) {
            $response->with('warning', 'The parent account was created, but the welcome email could not be sent.');
        }

        return $response;
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

    public function importStore(Request $request, StudentImportService $importService)
    {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'extensions:xlsx,xls,csv,txt,pdf',
                'mimetypes:application/pdf,application/x-pdf,text/plain,text/csv,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream',
            ],
        ]);

        $result = $importService->import($data['file'], $request->user());

        if ($result->totalRows() === 0) {
            return back()->with('error', 'No student rows were found in the uploaded file. Check the format and try again.');
        }

        // Keep the parsed student list in session (not the PDF/file itself).
        $request->session()->put('import_result', $result->toArray());

        return redirect()
            ->route('students.import.result')
            ->with('success', 'Students were imported as a list. The uploaded file is not shown.');
    }

    public function importResult(Request $request)
    {
        $payload = $request->session()->get('import_result');
        abort_unless(is_array($payload), 404);

        $result = StudentImportResult::fromSessionArray($payload);

        return view('students.import-result', compact('result'));
    }

    private function validatedStudentData(Request $request, ?Student $student = null): array
    {
        $parentMode = $request->input('parent_mode');
        if (! in_array($parentMode, ['existing', 'new'], true)) {
            $parentMode = filled($request->input('parent_user_id')) ? 'existing' : 'new';
        }

        $request->merge(['parent_mode' => $parentMode]);

        $portalEmailRules = [
            'nullable',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore(
                $parentMode === 'existing' ? $request->input('parent_user_id') : null
            ),
        ];

        if ($parentMode === 'new') {
            $portalEmailRules = ['required', 'email', 'max:255', Rule::unique('users', 'email')];
        }

        $data = $request->validate([
            'admission_no' => ['nullable', 'string', 'max:100', Rule::unique('students', 'admission_no')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'parent_mode' => ['required', 'in:existing,new'],
            'parent_user_id' => [
                Rule::requiredIf($parentMode === 'existing'),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'parent'),
            ],
            'parent_relationship' => ['required', 'string', Rule::in(\App\Models\StudentParentLink::RELATIONSHIPS)],
            'parent_name' => [
                Rule::requiredIf($parentMode === 'new'),
                'nullable',
                'string',
                'max:255',
            ],
            'parent_phone' => ['required', 'string', 'max:50'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'portal_login_email' => $portalEmailRules,
            'parent_password' => [
                Rule::requiredIf($parentMode === 'new'),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
            'expected_total_fee' => ['nullable', 'integer', 'min:0'],
            'fee_structure_ids' => ['nullable', 'array'],
            'fee_structure_ids.*' => ['exists:fee_structures,id'],
        ]);

        $data['expected_total_fee'] = (int) ($data['expected_total_fee'] ?? 0);

        if ($parentMode === 'new' && blank($data['parent_email'] ?? null)) {
            $data['parent_email'] = $data['portal_login_email'];
        }

        return $data;
    }

    private function resolveParentUser(array $data): User
    {
        if (($data['parent_mode'] ?? 'existing') === 'existing') {
            return User::query()
                ->where('role', 'parent')
                ->findOrFail((int) $data['parent_user_id']);
        }

        return ParentStudentAdmission::createParentAccount(
            $data['parent_name'],
            $data['portal_login_email'],
            $data['parent_phone'],
            $data['parent_password'],
        );
    }

    private function sendParentWelcomeEmail(User $parent, Student $student, string $temporaryPassword): bool
    {
        try {
            Mail::to($parent->email)->send(
                new ParentWelcomeMailable($parent, $student, $temporaryPassword)
            );

            return true;
        } catch (Throwable $exception) {
            Log::error('Parent welcome email failed.', [
                'parent_user_id' => $parent->id,
                'student_id' => $student->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /** @return list<string> */
    private function nonStudentFields(): array
    {
        return [
            'parent_user_id',
            'parent_relationship',
            'fee_structure_ids',
            'portal_login_email',
            'parent_mode',
            'parent_password',
            'parent_password_confirmation',
        ];
    }
}
