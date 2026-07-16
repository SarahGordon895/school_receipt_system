# Demo bank receipts (presentation)

Use these PDFs during the **parent bank payment** demo (upload → parse → verify → receipt).

## Quick prepare (recommended before each live demo)

```bash
php artisan ftrs:prepare-bank-demo
```

This will:

1. Ensure Gordon’s child (`Sarah George Gordon`, `MBN-2024-002`) has enough outstanding balance
2. Generate a fresh unique PDF at `Gordon_NMB_Fee_Payment.pdf` (so the bank reference is never “already used”)

Optional:

```bash
php artisan ftrs:prepare-bank-demo --amount=200000
php artisan ftrs:prepare-bank-demo --bank=crdb
```

## Live demo steps

1. Login as **Gordon Guardian**
   - Phone: `+255755666899`
   - Password: `Gordon@2025`
2. Open **Bank Payments**
3. Select student **Sarah George Gordon**
4. Upload `Gordon_NMB_Fee_Payment.pdf`
5. Expect status **verified** and a school receipt created automatically
6. (Optional) Login as school admin / bursar and show the receipt / bank proofs report

## Why Gordon needed a balance

If the student is fully paid, upload is rejected with “no outstanding balance”. Fresh seeds now leave Gordon on `partial_good`. For an existing DB that was already fully paid, the prepare command removes newest receipts until balance covers the demo amount.
