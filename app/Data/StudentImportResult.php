<?php

namespace App\Data;

class StudentImportResult
{
    /** @param list<StudentImportRowResult> $rows */
    public function __construct(
        public string $filename,
        public array $rows,
    ) {
    }

    public function totalRows(): int
    {
        return count($this->rows);
    }

    public function createdCount(): int
    {
        return count(array_filter($this->rows, fn (StudentImportRowResult $r) => $r->status === 'created'));
    }

    public function updatedCount(): int
    {
        return count(array_filter($this->rows, fn (StudentImportRowResult $r) => $r->status === 'updated'));
    }

    public function skippedCount(): int
    {
        return count(array_filter($this->rows, fn (StudentImportRowResult $r) => $r->status === 'skipped'));
    }

    public function importedCount(): int
    {
        return $this->createdCount() + $this->updatedCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'rows' => array_map(fn (StudentImportRowResult $r) => $r->toArray(), $this->rows),
        ];
    }

    public static function fromSessionArray(array $data): self
    {
        $rows = array_map(
            fn (array $row) => StudentImportRowResult::fromArray($row),
            $data['rows'] ?? []
        );

        return new self(
            filename: (string) ($data['filename'] ?? 'import'),
            rows: $rows,
        );
    }
}
