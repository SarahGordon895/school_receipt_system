<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Used only to read spreadsheet rows into a plain array for StudentImportService.
 */
class StudentsImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
