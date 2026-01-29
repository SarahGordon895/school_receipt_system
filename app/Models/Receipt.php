<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Receipt extends Model
{
    protected $fillable = [
        'receipt_no',
        'student_id',
        'student_name',
        'class_id',
        'stream_id',
        'amount',
        'payment_date',
        'payment_mode',
        'reference',
        'note',
        'user_id'
    ];

    protected static function booted(): void
    {
        static::creating(function (Receipt $r) {
            if (!$r->receipt_no) {
                $r->receipt_no = static::generateScopedNo($r->payment_date ?: now()->toDateString());
            }
        });
    }

    public static function generateScopedNo($paymentDate): string
    {
        $date = Carbon::parse($paymentDate);
        $year = (int) $date->year;
        $term = match (true) {
            $date->month <= 4 => 'T1',
            $date->month <= 8 => 'T2',
            default => 'T3',
        };

        return DB::transaction(function () use ($year, $term) {
            $counter = ReceiptCounter::where('year', $year)->where('term', $term)->lockForUpdate()->first();
            if (!$counter) {
                $counter = ReceiptCounter::create(['year' => $year, 'term' => $term, 'current' => 0]);
                $counter->refresh();
                $counter->lockForUpdate();
            }
            $counter->current++;
            $counter->save();

            $seq = str_pad((string) $counter->current, 4, '0', STR_PAD_LEFT);
            return "RCPT-{$year}-{$term}-{$seq}";
        }, 3);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function paymentCategories()
    {
        return $this->belongsToMany(PaymentCategory::class, 'receipt_payment_category')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount); // "1,234,567"
    }

    public function syncPaymentCategories($categoriesWithAmounts)
    {
        $syncData = [];
        foreach ($categoriesWithAmounts as $categoryId => $amount) {
            if ($amount > 0) {
                $syncData[$categoryId] = ['amount' => $amount];
            }
        }
        return $this->paymentCategories()->sync($syncData);
    }

    public static function generateReceiptNo(): string
    {
        // Daily sequence: RCPT-YYYYMMDD-#### (simple, collision-safe loop)
        return DB::transaction(function () {
            $date = now()->format('Ymd');
            for ($i = 1; $i <= 5; $i++) {
                $seq = str_pad((string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
                $no = "RCPT-{$date}-{$seq}";
                if (!static::where('receipt_no', $no)->exists()) {
                    return $no;
                }
            }
            // Fallback (rare)
            return 'RCPT-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        }, 3);
    }
}
