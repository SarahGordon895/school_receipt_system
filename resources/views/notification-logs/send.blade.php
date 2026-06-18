@extends('layouts.app')
@section('title', 'Send Reminder to Parent')

@section('actions')
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-arrow-left" label="Back to logs" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
<div class="card">
    <div class="card-header fw-semibold">Send fee reminder to parent</div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            School Admin and Super Admin can send SMS and/or email reminders directly to a student's parent.
            SMS uses <strong>Parent Phone</strong> on the student record. Email uses <strong>Notification Email</strong>.
            Each send is recorded in reminder logs with the exact result.
        </p>

        <form method="POST" action="{{ route('notification-logs.send.store') }}" id="send-reminder-form">
            @csrf

            <div class="row g-3">
                <div class="col-12">
                    <label for="student_id" class="form-label">Student</label>
                    <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                data-phone="{{ $student->resolveParentPhone() }}"
                                data-email="{{ $student->resolveParentEmail() }}"
                                data-balance="{{ $student->balance }}"
                                data-due="{{ $student->fee_due_date?->format('Y-m-d') }}"
                                @selected(old('student_id', $selectedStudentId) == $student->id)>
                                {{ $student->name }}{{ $student->admission_no ? ' ('.$student->admission_no.')' : '' }}
                                — Balance Tsh {{ number_format($student->balance) }}
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="parent-contact-preview" class="form-text mt-2"></div>
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input @error('send_sms') is-invalid @enderror" type="checkbox"
                            name="send_sms" id="send_sms" value="1" @checked(old('send_sms', true))>
                        <label class="form-check-label" for="send_sms">Send SMS to parent phone</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_email" id="send_email" value="1"
                            @checked(old('send_email', true))>
                        <label class="form-check-label" for="send_email">Send email to parent</label>
                    </div>
                    @error('send_sms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="custom_sms_message" class="form-label">Custom SMS message (optional)</label>
                    <textarea name="custom_sms_message" id="custom_sms_message" rows="3"
                        class="form-control @error('custom_sms_message') is-invalid @enderror"
                        placeholder="Leave blank to use the standard fee reminder text.">{{ old('custom_sms_message') }}</textarea>
                    @error('custom_sms_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="default-sms-preview"></div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-form-actions :cancelUrl="route('notification-logs.index')" submitLabel="Send reminder now" submitIcon="bi-send" />
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const studentSelect = document.getElementById('student_id');
    const contactPreview = document.getElementById('parent-contact-preview');
    const defaultPreview = document.getElementById('default-sms-preview');
    const form = document.getElementById('send-reminder-form');

    function updatePreview() {
        const option = studentSelect.selectedOptions[0];
        if (!option || !option.value) {
            contactPreview.textContent = '';
            defaultPreview.textContent = '';
            return;
        }

        const phone = option.dataset.phone || 'No phone on file';
        const email = option.dataset.email || 'No email on file';
        const balance = option.dataset.balance || '0';
        const due = option.dataset.due || 'N/A';

        contactPreview.textContent = 'Parent contact: ' + phone + ' • ' + email;
        defaultPreview.textContent = 'Default SMS: Reminder: [student] has outstanding fee balance of Tsh '
            + Number(balance).toLocaleString() + '. Due date: ' + due + '.';
    }

    studentSelect.addEventListener('change', updatePreview);
    updatePreview();

    form.addEventListener('submit', function (event) {
        if (!confirm('Send this reminder to the selected parent now?')) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
