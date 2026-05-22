<?php

namespace Database\Seeders;

use App\Models\FeeStructure;
use App\Models\NotificationLog;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Support\ParentStudentAdmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@school.tz')->first();
        if (!$admin) {
            $this->command?->warn('Skipping demo data: admin@school.tz not found. Run DatabaseSeeder first.');

            return;
        }

        $parents = [
            'mkumbo' => User::updateOrCreate(['email' => 'parent.mkumbo@school.tz'], [
                'name' => 'Mkumbo Guardian',
                'password' => bcrypt('password'),
                'role' => 'parent',
            ]),
            'gordon' => User::updateOrCreate(['email' => 'parent.gordon@school.tz'], [
                'name' => 'Gordon Guardian',
                'password' => bcrypt('password'),
                'role' => 'parent',
            ]),
            'chaula' => User::updateOrCreate(['email' => 'parent.chaula@school.tz'], [
                'name' => 'Chaula Guardian',
                'password' => bcrypt('password'),
                'role' => 'parent',
            ]),
            'legacy' => User::where('email', 'parent@school.tz')->first(),
        ];

        $categories = collect([
            ['name' => 'Tuition', 'default_amount' => 400_000],
            ['name' => 'Transport', 'default_amount' => 80_000],
            ['name' => 'Exam Fee', 'default_amount' => 50_000],
            ['name' => 'Development Levy', 'default_amount' => 30_000],
        ])->mapWithKeys(function (array $row) {
            $cat = PaymentCategory::updateOrCreate(
                ['name' => $row['name']],
                ['default_amount' => $row['default_amount']]
            );

            return [$row['name'] => $cat];
        });

        $feeStructures = collect([
            ['name' => 'Form I Annual Fees', 'class_name' => 'Form I', 'amount' => 560_000, 'due_date' => now()->addDays(14)],
            ['name' => 'Form II Annual Fees', 'class_name' => 'Form II', 'amount' => 610_000, 'due_date' => now()->addDays(21)],
            ['name' => 'Form III Annual Fees', 'class_name' => 'Form III', 'amount' => 650_000, 'due_date' => now()->subDays(5)],
            ['name' => 'Form IV Annual Fees', 'class_name' => 'Form IV', 'amount' => 700_000, 'due_date' => now()->addDays(30)],
        ])->mapWithKeys(function (array $row) {
            $fs = FeeStructure::updateOrCreate(
                ['name' => $row['name']],
                [
                    'class_name' => $row['class_name'],
                    'amount' => $row['amount'],
                    'due_date' => $row['due_date'],
                    'is_active' => true,
                ]
            );

            return [$row['class_name'] => $fs];
        });

        $students = [
            [
                'admission_no' => 'MBN-2024-001',
                'name' => 'Innocent Richard Mkumbo',
                'class_name' => 'Form I',
                'parent_key' => 'mkumbo',
                'fee_due_date' => now()->addDays(14),
                'structures' => ['Form I'],
                'payments' => [
                    ['days_ago' => 10, 'mode' => 'Cash', 'categories' => ['Tuition' => 200_000, 'Transport' => 80_000]],
                ],
            ],
            [
                'admission_no' => 'MBN-2024-002',
                'name' => 'Sarah George Gordon',
                'class_name' => 'Form II',
                'parent_key' => 'gordon',
                'fee_due_date' => now()->addDays(21),
                'structures' => ['Form II'],
                'payments' => [
                    ['days_ago' => 5, 'mode' => 'Mobile Money', 'categories' => ['Tuition' => 610_000]],
                ],
            ],
            [
                'admission_no' => 'MBN-2024-003',
                'name' => 'Charles Dani Chaula',
                'class_name' => 'Form III',
                'parent_key' => 'chaula',
                'fee_due_date' => now()->subDays(5),
                'structures' => ['Form III'],
                'payments' => [
                    ['days_ago' => 45, 'mode' => 'Bank', 'categories' => ['Tuition' => 300_000]],
                ],
            ],
        ];

        foreach ($students as $row) {
            $parent = $parents[$row['parent_key']] ?? null;
            if (!$parent) {
                continue;
            }

            $student = Student::updateOrCreate(
                ['admission_no' => $row['admission_no']],
                [
                    'name' => $row['name'],
                    'class_name' => $row['class_name'],
                    'parent_name' => $parent->name,
                    'parent_phone' => '+25571200000' . substr($row['admission_no'], -1),
                    'parent_email' => $parent->email,
                    'fee_due_date' => $row['fee_due_date'],
                    'expected_total_fee' => 0,
                    'admitted_at' => now(),
                    'registered_by_user_id' => $admin->id,
                ]
            );

            ParentStudentAdmission::linkGuardian(
                $student,
                $parent->id,
                'Guardian',
                true,
                $student->parent_phone,
                $admin->id,
            );

            $structureIds = collect($row['structures'])
                ->map(fn (string $class) => $feeStructures[$class]->id ?? null)
                ->filter()
                ->all();
            $student->feeStructures()->sync($structureIds);

            foreach ($row['payments'] as $payment) {
                $paymentDate = Carbon::today()->subDays($payment['days_ago'])->toDateString();
                $exists = Receipt::where('student_id', $student->id)
                    ->whereDate('payment_date', $paymentDate)
                    ->where('payment_mode', $payment['mode'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $total = array_sum($payment['categories']);
                $receipt = Receipt::create([
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'class_name' => $student->class_name,
                    'amount' => $total,
                    'payment_date' => $paymentDate,
                    'payment_mode' => $payment['mode'],
                    'reference' => 'DEMO-' . strtoupper(substr(md5($student->admission_no . $paymentDate), 0, 6)),
                    'note' => 'Demo payment record',
                    'user_id' => $admin->id,
                ]);

                $sync = [];
                foreach ($payment['categories'] as $catName => $amount) {
                    if (isset($categories[$catName])) {
                        $sync[$categories[$catName]->id] = ['amount' => $amount];
                    }
                }
                $receipt->paymentCategories()->sync($sync);
            }

            if ($student->balance > 0) {
                NotificationLog::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'channel' => 'email',
                        'sent_on' => now()->toDateString(),
                        'message' => 'Demo: fee balance reminder email queued.',
                    ],
                    ['status' => 'sent']
                );
            }
        }

        $this->command?->info('Demo data seeded: one parent account per student (see parent.mkumbo@, parent.gordon@, parent.chaula@).');
    }
}
