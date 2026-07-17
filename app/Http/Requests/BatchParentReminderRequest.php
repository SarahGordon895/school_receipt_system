<?php

namespace App\Http\Requests;

use App\Services\NotificationTemplateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BatchParentReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $min = (int) config('notifications.min_batch_parents', 1);
        $max = (int) config('notifications.max_batch_parents', 5);
        $isManualSend = $this->routeIs('notification-logs.send.store');

        $allowedTypes = array_merge(['auto'], NotificationTemplateService::manualSendEventTypes());

        $messageTypeRule = $isManualSend
            ? ['required', Rule::in($allowedTypes)]
            : ['nullable', Rule::in($allowedTypes)];

        return [
            'student_ids' => ['required', 'array', 'min:'.$min, 'max:'.$max],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
            'message_type' => $messageTypeRule,
            'send_sms' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('send_sms') && ! $this->boolean('send_email')) {
                $validator->errors()->add('send_sms', 'Select at least one channel (SMS or email).');
            }
        });
    }

    public function sendSms(): bool
    {
        return $this->boolean('send_sms', $this->routeIs('reports.unpaid.send-reminders'));
    }

    public function sendEmail(): bool
    {
        return $this->boolean('send_email', $this->routeIs('reports.unpaid.send-reminders'));
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $min = (int) config('notifications.min_batch_parents', 1);
        $max = (int) config('notifications.max_batch_parents', 5);

        return [
            'student_ids.min' => "Select at least {$min} student(s).",
            'student_ids.max' => "Select at most {$max} student(s) per send.",
            'student_ids.required' => 'Select between '.$min.' and '.$max.' students.',
        ];
    }
}
