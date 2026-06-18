@props(['log', 'students'])

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="student_id" class="form-label">Student</label>
        <select name="student_id" id="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
            <option value="">Select student</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected(old('student_id', $log->student_id) == $student->id)>
                    {{ $student->name }}{{ $student->admission_no ? ' ('.$student->admission_no.')' : '' }}
                </option>
            @endforeach
        </select>
        @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="sent_on" class="form-label">Date sent</label>
        <input type="date" name="sent_on" id="sent_on"
            class="form-control @error('sent_on') is-invalid @enderror"
            value="{{ old('sent_on', $log->sent_on?->format('Y-m-d') ?? now()->toDateString()) }}" required>
        @error('sent_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="channel" class="form-label">Channel</label>
        <select name="channel" id="channel" class="form-select @error('channel') is-invalid @enderror" required>
            <option value="email" @selected(old('channel', $log->channel) === 'email')>Email</option>
            <option value="sms" @selected(old('channel', $log->channel) === 'sms')>SMS</option>
        </select>
        @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="sent" @selected(old('status', $log->status) === 'sent')>Sent</option>
            <option value="failed" @selected(old('status', $log->status) === 'failed')>Failed</option>
            <option value="skipped" @selected(old('status', $log->status) === 'skipped')>Skipped</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="message" class="form-label">Message</label>
        <textarea name="message" id="message" rows="4"
            class="form-control @error('message') is-invalid @enderror"
            placeholder="e.g. Fee reminder SMS sent.">{{ old('message', $log->message) }}</textarea>
        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Use the same wording as automated reminders when recording manual follow-ups.</div>
    </div>
</div>
