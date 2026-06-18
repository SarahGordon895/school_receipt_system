<?php

namespace App\Services;

readonly class SmsSendResult
{
    public function __construct(
        public string $status,
        public string $detail,
        public ?string $recipient = null,
        public ?string $gatewayUid = null,
        public ?string $deliveryStatus = null,
    ) {
    }

    public static function skipped(string $detail, ?string $recipient = null): self
    {
        return new self('skipped', $detail, $recipient);
    }

    public static function failed(string $detail, ?string $recipient = null, ?string $gatewayUid = null): self
    {
        return new self('failed', $detail, $recipient, $gatewayUid);
    }

    public static function sent(
        string $detail = 'SMS accepted by provider.',
        ?string $recipient = null,
        ?string $gatewayUid = null,
        ?string $deliveryStatus = null
    ): self {
        return new self('sent', $detail, $recipient, $gatewayUid, $deliveryStatus);
    }

    public function delivered(): bool
    {
        if ($this->status !== 'sent') {
            return false;
        }

        $delivery = strtolower((string) $this->deliveryStatus);

        return in_array($delivery, ['delivered', 'success', 'sent', 'submitted'], true);
    }

    public function succeeded(): bool
    {
        return $this->status === 'sent';
    }
}
