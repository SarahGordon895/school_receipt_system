<?php

namespace App\Services;

use App\Data\BankReceiptData;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class BankReceiptParser
{
    public function parseFromPdf(string $absolutePath): BankReceiptData
    {
        $text = '';

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($absolutePath);
            $text = trim(preg_replace('/\s+/u', ' ', $pdf->getText()) ?? '');
        } catch (\Throwable) {
            $text = '';
        }

        if ($text === '') {
            $raw = file_get_contents($absolutePath) ?: '';
            $text = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
        }

        return $this->parseFromText($text);
    }

    public function parseFromText(string $text): BankReceiptData
    {
        $normalized = $this->normalizeText($text);

        return new BankReceiptData(
            bank: $this->detectBank($normalized),
            amount: $this->extractAmount($normalized),
            reference: $this->extractReference($normalized),
            accountNumber: $this->extractAccountNumber($normalized),
            paymentDate: $this->extractPaymentDate($normalized),
            rawText: $text,
        );
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return Str::upper(trim($text));
    }

    private function detectBank(string $text): ?string
    {
        if (preg_match('/\bCRDB(?:\s+BANK)?\b/u', $text)) {
            return 'crdb';
        }

        if (preg_match('/\bNMB(?:\s+BANK)?\b/u', $text) || str_contains($text, 'NATIONAL MICROFINANCE BANK')) {
            return 'nmb';
        }

        return null;
    }

    private function extractAmount(string $text): ?int
    {
        $patterns = [
            '/(?:AMOUNT|TRANSACTION AMOUNT|TOTAL AMOUNT|PAID AMOUNT|CREDIT AMOUNT)\s*(?:TZS|TSH|TZ)?\s*([0-9][0-9,]*(?:\.[0-9]{2})?)/u',
            '/(?:TZS|TSH)\s*([0-9][0-9,]*(?:\.[0-9]{2})?)/u',
            '/AMOUNT\s+([0-9][0-9,]*(?:\.[0-9]{2})?)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->toIntegerAmount($matches[1]);
            }
        }

        return null;
    }

    private function extractReference(string $text): ?string
    {
        $patterns = [
            '/(?:TRANSACTION REF(?:ERENCE)?|REFERENCE(?: NO| NUMBER)?|REF(?:ERENCE)? NO|RECEIPT NO|TXN REF)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\-\/]{5,30})/u',
            '/\b((?:NMB|CRDB)[A-Z0-9]{6,24})\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return strtoupper(trim($matches[1]));
            }
        }

        return null;
    }

    private function extractAccountNumber(string $text): ?string
    {
        $patterns = [
            '/(?:BENEFICIARY|CREDIT|DESTINATION|TO)\s+ACCOUNT(?:\s+NUMBER|\s+NO)?\s*[:#-]?\s*([0-9]{8,16})/u',
            '/ACCOUNT\s+(?:NUMBER|NO)\s*[:#-]?\s*([0-9]{8,16})/u',
            '/\b([0-9]{10,16})\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->normalizeAccount($matches[1]);
            }
        }

        return null;
    }

    private function extractPaymentDate(string $text): ?Carbon
    {
        if (preg_match('/(?:PAYMENT DATE|TRANSACTION DATE|DATE|VALUE DATE)\s*[:#-]?\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4})/u', $text, $matches)) {
            foreach (['d/m/Y', 'd-m-Y', 'j/n/Y'] as $format) {
                $parsed = Carbon::createFromFormat($format, $matches[1]);
                if ($parsed !== false) {
                    return $parsed->startOfDay();
                }
            }
        }

        if (preg_match('/(?:PAYMENT DATE|TRANSACTION DATE|DATE|VALUE DATE)\s*[:#-]?\s*([0-9]{1,2}-[A-Z]{3}-[0-9]{4})/u', $text, $matches)) {
            $parsed = Carbon::createFromFormat('d-M-Y', $matches[1]);

            return $parsed !== false ? $parsed->startOfDay() : null;
        }

        return null;
    }

    private function toIntegerAmount(string $value): int
    {
        $clean = str_replace(',', '', trim($value));

        if (str_contains($clean, '.')) {
            $clean = explode('.', $clean)[0];
        }

        return (int) (preg_replace('/\D+/', '', $clean) ?: 0);
    }

    public function normalizeAccount(?string $account): ?string
    {
        if (! filled($account)) {
            return null;
        }

        return preg_replace('/\D+/', '', $account) ?: null;
    }

    public function accountsMatch(?string $extracted, ?string $configured): bool
    {
        $a = $this->normalizeAccount($extracted);
        $b = $this->normalizeAccount($configured);

        if (! $a || ! $b) {
            return false;
        }

        return $a === $b || str_ends_with($a, $b) || str_ends_with($b, $a);
    }
}
