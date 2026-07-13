<?php

namespace App\Services;

use App\Data\StudentImportResult;
use App\Data\StudentImportRowResult;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class StudentImportService
{
    /** @return list<array<int, string|null>> */
    public function readRowsFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'], true) && class_exists(Excel::class)) {
            $sheet = Excel::toArray(new \App\Imports\StudentsImport, $file);

            return $sheet[0] ?? [];
        }

        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    public function import(UploadedFile $file, User $importedBy): StudentImportResult
    {
        $rawRows = $this->readRowsFromFile($file);
        $parsedRows = $this->normalizeRows($rawRows);
        $results = [];

        foreach ($parsedRows as $index => $row) {
            $rowNumber = $index + 1;
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $admissionNo = filled($row['admission_no'] ?? null) ? trim((string) $row['admission_no']) : null;
            $className = filled($row['class_name'] ?? null) ? trim((string) $row['class_name']) : null;
            $parentName = filled($row['parent_name'] ?? null) ? trim((string) $row['parent_name']) : null;
            $parentPhone = filled($row['parent_phone'] ?? null) ? User::normalizePhone((string) $row['parent_phone']) : null;
            $parentEmail = filled($row['parent_email'] ?? null) ? strtolower(trim((string) $row['parent_email'])) : null;

            $existing = null;
            if ($admissionNo) {
                $existing = Student::query()->where('admission_no', $admissionNo)->first();
            }
            if (! $existing) {
                $existing = Student::query()->where('name', $name)->when($className, fn ($q) => $q->where('class_name', $className))->first();
            }

            if ($existing && ! $this->rowHasChanges($existing, $row)) {
                $results[] = new StudentImportRowResult(
                    rowNumber: $rowNumber,
                    name: $name,
                    admissionNo: $admissionNo,
                    className: $className,
                    parentName: $parentName,
                    parentPhone: $parentPhone,
                    parentEmail: $parentEmail,
                    status: 'skipped',
                    message: 'Already registered with the same details.',
                    studentId: $existing->id,
                );

                continue;
            }

            $payload = [
                'name' => $name,
                'class_name' => $className,
                'parent_name' => $parentName,
                'parent_phone' => $parentPhone,
                'parent_email' => $parentEmail,
                'registered_by_user_id' => $importedBy->id,
            ];

            if ($admissionNo) {
                $payload['admission_no'] = $admissionNo;
            }

            if ($existing) {
                $existing->fill($payload);
                if (! $existing->admitted_at) {
                    $existing->admitted_at = now();
                }
                $existing->save();
                $student = $existing;
                $status = 'updated';
                $message = 'Existing student record updated from import file.';
            } else {
                $student = Student::create(array_merge($payload, [
                    'admitted_at' => now(),
                    'expected_total_fee' => 0,
                ]));
                $status = 'created';
                $message = 'New student registered from import file.';
            }

            $results[] = new StudentImportRowResult(
                rowNumber: $rowNumber,
                name: $student->name,
                admissionNo: $student->admission_no,
                className: $student->class_name,
                parentName: $student->parent_name,
                parentPhone: $student->parent_phone,
                parentEmail: $student->parent_email,
                status: $status,
                message: $message,
                studentId: $student->id,
            );
        }

        return new StudentImportResult(
            filename: $file->getClientOriginalName(),
            rows: $results,
        );
    }

    /**
     * @param list<array<int, string|null>> $rawRows
     * @return list<array<string, string|null>>
     */
    private function normalizeRows(array $rawRows): array
    {
        if ($rawRows === []) {
            return [];
        }

        $first = $rawRows[0];
        $headerMap = $this->detectHeaderMap($first);

        if ($headerMap !== null) {
            $parsed = [];
            foreach (array_slice($rawRows, 1) as $row) {
                $parsed[] = $this->mapRowWithHeader($row, $headerMap);
            }

            return $parsed;
        }

        return array_map(fn (array $row) => $this->mapPositionalRow($row), $rawRows);
    }

    /** @param array<int, string|null> $headerRow @return array<string, int>|null */
    private function detectHeaderMap(array $headerRow): ?array
    {
        $map = [];
        foreach ($headerRow as $index => $cell) {
            $key = $this->normalizeHeaderKey((string) $cell);
            if ($key !== null) {
                $map[$key] = $index;
            }
        }

        return isset($map['name']) ? $map : null;
    }

    private function normalizeHeaderKey(string $header): ?string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return match (true) {
            in_array($header, ['name', 'student_name', 'student', 'full_name', 'learner_name'], true) => 'name',
            in_array($header, ['admission_no', 'admission_number', 'admission', 'reg_no', 'registration_no'], true) => 'admission_no',
            in_array($header, ['class', 'class_name', 'form', 'grade', 'level'], true) => 'class_name',
            in_array($header, ['parent_name', 'guardian_name', 'guardian', 'parent'], true) => 'parent_name',
            in_array($header, ['parent_phone', 'phone', 'guardian_phone', 'mobile', 'contact_phone'], true) => 'parent_phone',
            in_array($header, ['parent_email', 'email', 'guardian_email', 'contact_email'], true) => 'parent_email',
            default => null,
        };
    }

    /** @param array<int, string|null> $row @param array<string, int> $headerMap @return array<string, string|null> */
    private function mapRowWithHeader(array $row, array $headerMap): array
    {
        $get = fn (string $key) => isset($headerMap[$key]) ? ($row[$headerMap[$key]] ?? null) : null;

        return [
            'name' => $get('name'),
            'admission_no' => $get('admission_no'),
            'class_name' => $get('class_name'),
            'parent_name' => $get('parent_name'),
            'parent_phone' => $get('parent_phone'),
            'parent_email' => $get('parent_email'),
        ];
    }

    /** @param array<int, string|null> $row @return array<string, string|null> */
    private function mapPositionalRow(array $row): array
    {
        return [
            'name' => $row[0] ?? null,
            'admission_no' => $row[1] ?? null,
            'class_name' => $row[2] ?? null,
            'parent_name' => $row[3] ?? null,
            'parent_phone' => $row[4] ?? null,
            'parent_email' => $row[5] ?? null,
        ];
    }

    /** @param array<string, string|null> $row */
    private function rowHasChanges(Student $student, array $row): bool
    {
        $fields = ['name', 'class_name', 'parent_name', 'parent_phone', 'parent_email', 'admission_no'];

        foreach ($fields as $field) {
            $incoming = $row[$field] ?? null;
            if (! filled($incoming)) {
                continue;
            }

            if ($field === 'parent_phone') {
                $incoming = User::normalizePhone((string) $incoming);
            }

            if ((string) ($student->{$field} ?? '') !== (string) $incoming) {
                return true;
            }
        }

        return false;
    }
}
