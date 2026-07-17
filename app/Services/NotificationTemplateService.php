<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;

class NotificationTemplateService
{
    public const PAYMENT_RECEIVED = 'payment_received';

    public const FEE_REMINDER_14 = 'fee_reminder_14';

    public const FEE_REMINDER_7 = 'fee_reminder_7';

    public const FEE_REMINDER_3 = 'fee_reminder_3';

    public const FEE_REMINDER_DUE = 'fee_reminder_due';

    /** @deprecated Use milestone-specific types */
    public const FEE_REMINDER = 'fee_reminder';

    public const OVERDUE = 'overdue';

    /** @var list<int> */
    public const REMINDER_MILESTONES = [14, 7, 3, 0];

    /** @return list<string> */
    public static function placeholders(): array
    {
        return [
            '{school_name}',
            '{student_name}',
            '{admission_no}',
            '{class_name}',
            '{parent_name}',
            '{amount}',
            '{balance}',
            '{expected_fee}',
            '{due_date}',
            '{receipt_no}',
            '{days_until_due}',
        ];
    }

    public function render(string $eventType, Student $student, ?Receipt $receipt = null): string
    {
        $template = $this->resolveTemplate($eventType);

        $dueDate = $student->resolveFeeDueDate();
        $daysUntilDue = max(0, now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false));

        $replacements = [
            '{school_name}' => $this->schoolName(),
            '{student_name}' => $student->name,
            '{admission_no}' => $student->admission_no ?? 'N/A',
            '{class_name}' => $student->class_name ?? 'N/A',
            '{parent_name}' => $student->parent_name ?? 'Parent',
            '{amount}' => format_tzs($receipt?->amount ?? 0),
            '{balance}' => format_tzs($student->balance),
            '{expected_fee}' => format_tzs($student->expected_amount),
            '{due_date}' => $dueDate->format('d/m/Y'),
            '{receipt_no}' => $receipt?->receipt_no ?? 'N/A',
            '{days_until_due}' => (string) $daysUntilDue,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function eventTypeForMilestone(int $daysBeforeDue): string
    {
        return match ($daysBeforeDue) {
            14 => self::FEE_REMINDER_14,
            7 => self::FEE_REMINDER_7,
            3 => self::FEE_REMINDER_3,
            0 => self::FEE_REMINDER_DUE,
            default => self::FEE_REMINDER,
        };
    }

    public function resolveEventTypeForStudent(Student $student): string
    {
        if ($student->balance <= 0) {
            return self::FEE_REMINDER;
        }

        if ($student->isFeeOverdue()) {
            return self::OVERDUE;
        }

        $daysUntil = now()->startOfDay()->diffInDays($student->resolveFeeDueDate()->startOfDay(), false);

        return match (true) {
            $daysUntil === 14 => self::FEE_REMINDER_14,
            $daysUntil === 7 => self::FEE_REMINDER_7,
            $daysUntil === 3 => self::FEE_REMINDER_3,
            $daysUntil === 0 => self::FEE_REMINDER_DUE,
            default => self::FEE_REMINDER,
        };
    }

    /**
     * Suggested manual-send template from fee status (range-based for bursar workflow).
     */
    public function suggestManualEventTypeForStudent(Student $student): string
    {
        if ($student->balance <= 0) {
            return self::PAYMENT_RECEIVED;
        }

        if ($student->isFeeOverdue()) {
            return self::OVERDUE;
        }

        $daysUntil = now()->startOfDay()->diffInDays($student->resolveFeeDueDate()->startOfDay(), false);

        return match (true) {
            $daysUntil <= 0 => self::FEE_REMINDER_DUE,
            $daysUntil <= 3 => self::FEE_REMINDER_3,
            $daysUntil <= 7 => self::FEE_REMINDER_7,
            $daysUntil <= 14 => self::FEE_REMINDER_14,
            default => self::FEE_REMINDER,
        };
    }

    public function statusPriority(string $eventType): int
    {
        return match ($eventType) {
            self::OVERDUE => 0,
            self::FEE_REMINDER_DUE => 1,
            self::FEE_REMINDER_3 => 2,
            self::FEE_REMINDER_7 => 3,
            self::FEE_REMINDER_14 => 4,
            self::FEE_REMINDER => 5,
            self::PAYMENT_RECEIVED => 6,
            default => 9,
        };
    }

    public function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            self::PAYMENT_RECEIVED => 'Payment confirmation',
            self::FEE_REMINDER_14 => 'Fee reminder (2 weeks before due)',
            self::FEE_REMINDER_7 => 'Fee reminder (1 week before due)',
            self::FEE_REMINDER_3 => 'Fee reminder (3 days before due)',
            self::FEE_REMINDER_DUE => 'Fee reminder (due today)',
            self::FEE_REMINDER => 'Fee reminder (general)',
            self::OVERDUE => 'Overdue notice',
            'auto' => 'Auto — match each student’s fee status',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }

    /** @return array<string, string> */
    public function defaultTemplates(): array
    {
        return [
            self::PAYMENT_RECEIVED => 'Asante! Malipo ya Tsh {amount} kwa {student_name} yamepokelewa (Risiti {receipt_no}). Salio: Tsh {balance}. — {school_name}',
            self::FEE_REMINDER_14 => 'Ukumbusho (wiki 2): {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date} (siku {days_until_due} zimebaki). Tafadhali lipa kwa wakati. — {school_name}',
            self::FEE_REMINDER_7 => 'Ukumbusho (wiki 1): {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date}. Tafadhali lipa kwa wakati. — {school_name}',
            self::FEE_REMINDER_3 => 'Ukumbusho (siku 3): {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date}. Tafadhali lipa haraka. — {school_name}',
            self::FEE_REMINDER_DUE => 'Ukumbusho: Leo ni tarehe ya mwisho ya ada ya {student_name}. Salio: Tsh {balance}. Tafadhali lipa leo. — {school_name}',
            self::FEE_REMINDER => 'Ukumbusho: {student_name} ana salio la ada Tsh {balance}. Tarehe ya mwisho: {due_date}. Tafadhali lipa kwa wakati. — {school_name}',
            self::OVERDUE => 'Taarifa: Ada ya {student_name} (Tsh {balance}) imepitisha tarehe ya mwisho ({due_date}). Tafadhali lipa haraka. — {school_name}',
        ];
    }

    /** @return list<string> */
    public static function manualSendEventTypes(): array
    {
        return [
            self::OVERDUE,
            self::FEE_REMINDER_DUE,
            self::FEE_REMINDER_3,
            self::FEE_REMINDER_7,
            self::FEE_REMINDER_14,
            self::FEE_REMINDER,
            self::PAYMENT_RECEIVED,
        ];
    }

    /**
     * Event types ordered with the suggested status first.
     *
     * @return list<string>
     */
    public function eventTypesOrderedForSuggestion(?string $suggested = null): array
    {
        $types = self::manualSendEventTypes();

        if (! $suggested || ! in_array($suggested, $types, true)) {
            return $types;
        }

        return array_values(array_unique(array_merge([$suggested], $types)));
    }

    /**
     * @return array<string, array{label: string, body: string}>
     */
    public function manualTemplateCatalog(?Setting $setting = null): array
    {
        $catalog = [];

        foreach (self::manualSendEventTypes() as $type) {
            $catalog[$type] = [
                'label' => $this->eventLabel($type),
                'body' => $this->resolveTemplate($type, $setting),
            ];
        }

        return $catalog;
    }

    public function resolveTemplate(string $eventType, ?Setting $setting = null): string
    {
        $setting ??= Setting::query()->first();
        $defaults = $this->defaultTemplates();

        $column = match ($eventType) {
            self::PAYMENT_RECEIVED => 'sms_template_payment_received',
            self::FEE_REMINDER_14 => 'sms_template_fee_reminder_14',
            self::FEE_REMINDER_7, self::FEE_REMINDER_3, self::FEE_REMINDER_DUE, self::FEE_REMINDER => 'sms_template_fee_reminder',
            self::OVERDUE => 'sms_template_overdue',
            default => null,
        };

        if ($column && $setting && filled($setting->{$column})) {
            return (string) $setting->{$column};
        }

        return $defaults[$eventType] ?? $defaults[self::FEE_REMINDER];
    }

    private function schoolName(): string
    {
        return Setting::query()->value('school_name') ?: config('app.name', 'School');
    }
}
