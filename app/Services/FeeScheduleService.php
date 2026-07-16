<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeeScheduleService
{
    /** @var list<int> */
    public const DEFAULT_MONTHS = [1, 6, 9];

    public const DEFAULT_DAY = 15;

    /**
     * Day of month when each installment is due (e.g. 15 = mid-month).
     */
    public function installmentDay(?Setting $setting = null): int
    {
        $day = (int) (($setting ?? Setting::current())?->fee_installment_day ?: self::DEFAULT_DAY);

        return max(1, min(28, $day));
    }

    /**
     * Calendar months when fees are due (1=Jan … 12=Dec). Default: Jan, Jun, Sep.
     *
     * @return list<int>
     */
    public function installmentMonths(?Setting $setting = null): array
    {
        $raw = ($setting ?? Setting::current())?->fee_installment_months;

        $months = is_array($raw)
            ? array_map('intval', $raw)
            : self::DEFAULT_MONTHS;

        $months = array_values(array_unique(array_filter(
            $months,
            fn (int $month) => $month >= 1 && $month <= 12
        )));

        sort($months);

        return $months !== [] ? $months : self::DEFAULT_MONTHS;
    }

    /**
     * Installment due dates for a calendar year.
     *
     * @return Collection<int, Carbon>
     */
    public function datesForYear(int $year, ?Setting $setting = null): Collection
    {
        $day = $this->installmentDay($setting);

        return collect($this->installmentMonths($setting))
            ->map(fn (int $month) => Carbon::create($year, $month, $day)->startOfDay())
            ->values();
    }

    /**
     * Next installment due date on or after $on (looks ahead one year).
     */
    public function nextDueDate(?Carbon $on = null, ?Setting $setting = null): Carbon
    {
        $on = ($on ?? now())->copy()->startOfDay();

        foreach ([$on->year, $on->year + 1] as $year) {
            foreach ($this->datesForYear($year, $setting) as $date) {
                if ($date->gte($on)) {
                    return $date;
                }
            }
        }

        return $this->datesForYear($on->year + 1, $setting)->first();
    }

    /**
     * Most recent installment due date strictly before $on, if any.
     */
    public function previousDueDate(?Carbon $on = null, ?Setting $setting = null): ?Carbon
    {
        $on = ($on ?? now())->copy()->startOfDay();

        for ($year = $on->year; $year >= $on->year - 1; $year--) {
            $dates = $this->datesForYear($year, $setting)->reverse();
            foreach ($dates as $date) {
                if ($date->lt($on)) {
                    return $date;
                }
            }
        }

        return null;
    }

    /**
     * Due date used for reminders and display: next upcoming installment.
     */
    public function activeDueDate(?Carbon $on = null, ?Setting $setting = null): Carbon
    {
        return $this->nextDueDate($on, $setting);
    }

    /**
     * Fraction of annual fees that should already be paid by $on (0, 1/n, 2/n, …, 1).
     */
    public function requiredPaidFraction(?Carbon $on = null, ?Setting $setting = null): float
    {
        $on = ($on ?? now())->copy()->startOfDay();
        $months = $this->installmentMonths($setting);
        $count = count($months);

        if ($count === 0) {
            return 0.0;
        }

        $passed = $this->datesForYear($on->year, $setting)
            ->filter(fn (Carbon $date) => $date->lte($on))
            ->count();

        // Before the year's first installment, prior year's final installment still applies.
        if ($passed === 0) {
            $previous = $this->previousDueDate($on, $setting);

            return $previous ? 1.0 : 0.0;
        }

        return min(1.0, $passed / $count);
    }

    /**
     * Cumulative amount that should be paid by $on for this student.
     */
    public function cumulativeAmountDue(Student $student, ?Carbon $on = null, ?Setting $setting = null): int
    {
        $expected = $student->expected_amount;

        if ($expected <= 0) {
            return 0;
        }

        return (int) round($expected * $this->requiredPaidFraction($on, $setting));
    }

    /**
     * Amount that should be covered by the installment due on $dueDate (inclusive).
     */
    public function cumulativeAmountDueOn(Student $student, Carbon $dueDate, ?Setting $setting = null): int
    {
        return $this->cumulativeAmountDue($student, $dueDate->copy()->startOfDay(), $setting);
    }

    public function isOverdue(Student $student, ?Carbon $on = null, ?Setting $setting = null): bool
    {
        if ($student->expected_amount <= 0 || $student->balance <= 0) {
            return false;
        }

        return $student->paid_amount < $this->cumulativeAmountDue($student, $on, $setting);
    }

    /**
     * Human-readable schedule summary for forms/settings.
     */
    public function summary(?Setting $setting = null): string
    {
        $day = $this->installmentDay($setting);
        $labels = collect($this->installmentMonths($setting))
            ->map(fn (int $month) => Carbon::create(now()->year, $month, 1)->format('M').' '.$day)
            ->all();

        return implode(', ', $labels);
    }
}
