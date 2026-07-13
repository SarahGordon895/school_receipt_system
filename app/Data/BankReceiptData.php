<?php

namespace App\Data;

use Carbon\Carbon;

class BankReceiptData
{
    public function __construct(
        public ?string $bank = null,
        public ?int $amount = null,
        public ?string $reference = null,
        public ?string $accountNumber = null,
        public ?Carbon $paymentDate = null,
        public string $rawText = '',
    ) {
    }

    public function hasMinimumFields(): bool
    {
        return $this->bank !== null
            && $this->amount !== null
            && $this->amount > 0
            && filled($this->reference)
            && filled($this->accountNumber)
            && $this->paymentDate !== null;
    }
}
