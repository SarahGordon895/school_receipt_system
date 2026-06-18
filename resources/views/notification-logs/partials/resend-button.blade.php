@props(['log', 'size' => 'sm'])

@if($log->isResolvableFailure())
    <form action="{{ route('notification-logs.resend', $log) }}" method="POST" class="d-inline form-with-loading"
        data-loading-label="Sending…">
        @csrf
        <x-icon-btn type="submit"
            icon="bi-arrow-repeat"
            :label="$log->channel === 'sms' ? 'Resend SMS' : 'Resend email'"
            variant="outline-warning"
            :size="$size" />
    </form>
@endif

@if($log->channel === 'sms' && $log->gateway_uid && in_array($log->status, ['failed', 'skipped'], true))
    <form action="{{ route('notification-logs.refresh-status', $log) }}" method="POST" class="d-inline form-with-loading"
        data-loading-label="Checking…">
        @csrf
        <x-icon-btn type="submit" icon="bi-cloud-check" label="Refresh status" variant="outline-info" :size="$size" />
    </form>
@endif

@if(in_array($log->status, ['failed', 'skipped'], true) && in_array($log->channel, ['sms', 'email'], true) && $log->student_id)
    <form action="{{ route('notification-logs.mark-delivered', $log) }}" method="POST" class="d-inline form-with-loading"
        data-loading-label="Saving…"
        onsubmit="return confirm('Mark this reminder as delivered? Use this when the parent confirms they received the message.')">
        @csrf
        <x-icon-btn type="submit" icon="bi-check2-circle" label="Mark delivered" variant="outline-success" :size="$size" />
    </form>
@endif
