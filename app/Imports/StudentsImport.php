<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;

class StudentsImport implements ToModel
{
    public function model(array $row)
    {
        $name = trim((string) ($row[0] ?? ''));
        return $name ? Student::firstOrCreate(['name' => $name]) : null;
    }
}
