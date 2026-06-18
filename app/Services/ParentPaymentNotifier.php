<?php

namespace App\Services;

use App\Models\Receipt;

/**
 * @deprecated Use ParentReminderService directly.
 */
class ParentPaymentNotifier
{
    public function __construct(private ParentReminderService $parentReminderService)
    {
    }

    public function notify(Receipt $receipt): void
    {
        $this->parentReminderService->notifyPayment($receipt);
    }
}
