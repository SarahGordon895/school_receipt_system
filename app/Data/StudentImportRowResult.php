<?php

namespace App\Data;

class StudentImportRowResult
{
    public function __construct(
        public int $rowNumber,
        public string $name,
        public ?string $admissionNo = null,
        public ?string $className = null,
        public ?string $parentName = null,
        public ?string $parentPhone = null,
        public ?string $parentEmail = null,
        public string $status = 'created',
        public ?string $message = null,
        public ?int $studentId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'name' => $this->name,
            'admission_no' => $this->admissionNo,
            'class_name' => $this->className,
            'parent_name' => $this->parentName,
            'parent_phone' => $this->parentPhone,
            'parent_email' => $this->parentEmail,
            'status' => $this->status,
            'message' => $this->message,
            'student_id' => $this->studentId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rowNumber: (int) ($data['row_number'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            admissionNo: $data['admission_no'] ?? null,
            className: $data['class_name'] ?? null,
            parentName: $data['parent_name'] ?? null,
            parentPhone: $data['parent_phone'] ?? null,
            parentEmail: $data['parent_email'] ?? null,
            status: (string) ($data['status'] ?? 'created'),
            message: $data['message'] ?? null,
            studentId: isset($data['student_id']) ? (int) $data['student_id'] : null,
        );
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'updated' => 'Updated',
            'skipped' => 'Skipped',
            default => 'Registered',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'updated' => 'info',
            'skipped' => 'secondary',
            default => 'success',
        };
    }
}
