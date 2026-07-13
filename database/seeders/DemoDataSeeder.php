<?php

namespace Database\Seeders;

use App\Models\FeeStructure;
use App\Models\NotificationLog;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Support\ParentStudentAdmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const DEFAULT_PARENT_PASSWORD = 'Parent@2025';

    /** Real parent contacts for SMS/email testing (normalized +255). */
    private const SHOWCASE_PHONES = [
        '+255655139724',
        '+255755666899',
        '+255718216434',
        '+255745700923',
        '+255715564818',
        '+255742484531',
        '+255744158980',
        '+255773255214',
        '+255744400394',
        '+255655552100',
    ];

    private const SHOWCASE_NOTIFICATION_EMAILS = [
        'sarahgordon2404@gmail.com',
        'sarahgeorge7224@gmail.com',
        'techmorahsolution@gmail.com',
        'samweleve@gmail.com',
    ];

  /** @var array<string, User> */
    private array $parents = [];

    /** @var array<string, PaymentCategory> */
    private array $categories = [];

    /** @var array<string, FeeStructure> */
    private array $feeStructures = [];

    public function run(): void
    {
        $admin = User::where('email', 'admin@mbonea.sc.tz')->first();
        if (!$admin) {
            $this->command?->warn('Skipping demo data: admin@mbonea.sc.tz not found. Run DatabaseSeeder first.');

            return;
        }

        $this->seedPaymentCategories();
        $this->seedFeeStructures();
        $this->seedShowcaseAccounts($admin);
        $this->seedSchoolPopulation($admin);
        $this->syncRealContactsThroughoutDatabase();

        $studentCount = Student::count();
        $parentCount = User::where('role', 'parent')->count();
        $fullyPaid = Student::all()->filter(fn (Student $s) => $s->isFullyPaid())->count();

        $this->command?->info("Demo school data ready: {$studentCount} students, {$parentCount} parent accounts, {$fullyPaid} fully paid.");
        $this->command?->line('Showcase parent phones (login with Parent tab):');
        foreach (array_slice(self::SHOWCASE_PARENT_KEYS, 0, 4) as $index => $key) {
            $phone = self::SHOWCASE_PHONES[$index];
            $email = self::SHOWCASE_NOTIFICATION_EMAILS[$index] ?? '—';
            $this->command?->line("  {$phone} — {$email}");
        }
        $this->command?->line('Mkumbo/Gordon/Chaula passwords: Mkumbo@2025 / Gordon@2025 / Chaula@2025. Others: '.self::DEFAULT_PARENT_PASSWORD.'.');
    }

    /** @var list<string> */
    private const SHOWCASE_PARENT_KEYS = [
        'mkumbo', 'gordon', 'chaula', 'samwele', 'parent5', 'parent6', 'parent7', 'parent8', 'parent9', 'parent10',
    ];

    private function seedPaymentCategories(): void
    {
        $rows = [
            ['name' => 'Tuition', 'default_amount' => 1_200_000],
            ['name' => 'Transport', 'default_amount' => 180_000],
            ['name' => 'Exam Fee', 'default_amount' => 80_000],
            ['name' => 'Development Levy', 'default_amount' => 70_000],
            ['name' => 'Boarding', 'default_amount' => 450_000],
        ];

        foreach ($rows as $row) {
            $this->categories[$row['name']] = PaymentCategory::updateOrCreate(
                ['name' => $row['name']],
                ['default_amount' => $row['default_amount']]
            );
        }
    }

    private function seedFeeStructures(): void
    {
        $rows = [
            ['name' => 'Form I Annual Fees 2026', 'class_name' => 'Form I', 'amount' => 1_530_000, 'due_date' => now()->addDays(14)],
            ['name' => 'Form II Annual Fees 2026', 'class_name' => 'Form II', 'amount' => 1_630_000, 'due_date' => now()->addDays(21)],
            ['name' => 'Form III Annual Fees 2026', 'class_name' => 'Form III', 'amount' => 1_730_000, 'due_date' => now()->subDays(5)],
            ['name' => 'Form IV Annual Fees 2026', 'class_name' => 'Form IV', 'amount' => 1_830_000, 'due_date' => now()->addDays(30)],
        ];

        foreach ($rows as $row) {
            $this->feeStructures[$row['class_name']] = FeeStructure::updateOrCreate(
                ['name' => $row['name']],
                [
                    'class_name' => $row['class_name'],
                    'amount' => $row['amount'],
                    'due_date' => $row['due_date'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedShowcaseAccounts($admin): void
    {
        $parentProfiles = [
            'mkumbo' => [
                'name' => 'Mkumbo Guardian',
                'email' => 'parent.mkumbo@mbonea.sc.tz',
                'password' => 'Mkumbo@2025',
            ],
            'gordon' => [
                'name' => 'Gordon Guardian',
                'email' => 'parent.gordon@mbonea.sc.tz',
                'password' => 'Gordon@2025',
            ],
            'chaula' => [
                'name' => 'Chaula Guardian',
                'email' => 'parent.chaula@mbonea.sc.tz',
                'password' => 'Chaula@2025',
            ],
            'samwele' => [
                'name' => 'Samwele Guardian',
                'email' => 'parent.samwele@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent5' => [
                'name' => 'Parent Guardian 5',
                'email' => 'parent5@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent6' => [
                'name' => 'Parent Guardian 6',
                'email' => 'parent6@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent7' => [
                'name' => 'Parent Guardian 7',
                'email' => 'parent7@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent8' => [
                'name' => 'Parent Guardian 8',
                'email' => 'parent8@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent9' => [
                'name' => 'Parent Guardian 9',
                'email' => 'parent9@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
            'parent10' => [
                'name' => 'Parent Guardian 10',
                'email' => 'parent10@mbonea.sc.tz',
                'password' => 'Parent@2025',
            ],
        ];

        $notificationEmails = [];

        foreach (self::SHOWCASE_PARENT_KEYS as $index => $key) {
            $profile = $parentProfiles[$key];
            $phone = self::SHOWCASE_PHONES[$index];
            $notificationEmails[$key] = self::SHOWCASE_NOTIFICATION_EMAILS[$index]
                ?? self::realEmailForIndex($index);

            $this->parents[$key] = User::updateOrCreate(['email' => $profile['email']], [
                'name' => $profile['name'],
                'email' => $profile['email'],
                'phone' => $phone,
                'password' => bcrypt($profile['password']),
                'role' => 'parent',
            ]);
        }

        $showcaseStudents = [
            [
                'admission_no' => 'MBN-2024-001',
                'name' => 'Innocent Richard Mkumbo',
                'class_name' => 'Form I',
                'parent_key' => 'mkumbo',
                'fee_due_date' => now()->addDays(14),
                'payment_tier' => 'partial_good',
            ],
            [
                'admission_no' => 'MBN-2024-002',
                'name' => 'Sarah George Gordon',
                'class_name' => 'Form II',
                'parent_key' => 'gordon',
                'fee_due_date' => now()->addDays(21),
                'payment_tier' => 'full',
            ],
            [
                'admission_no' => 'MBN-2024-003',
                'name' => 'Charles Dani Chaula',
                'class_name' => 'Form III',
                'parent_key' => 'chaula',
                'fee_due_date' => now()->subDays(5),
                'payment_tier' => 'partial_low',
            ],
            [
                'admission_no' => 'MBN-2024-004',
                'name' => 'Amina Juma Hassan',
                'class_name' => 'Form IV',
                'parent_key' => 'samwele',
                'fee_due_date' => now()->addDays(7),
                'payment_tier' => 'partial_low',
            ],
            [
                'admission_no' => 'MBN-2024-005',
                'name' => 'Peter Mwanga Kimaro',
                'class_name' => 'Form II',
                'parent_key' => 'parent5',
                'fee_due_date' => now()->addDays(3),
                'payment_tier' => 'none',
            ],
            [
                'admission_no' => 'MBN-2024-006',
                'name' => 'Grace Neema Mrosso',
                'class_name' => 'Form I',
                'parent_key' => 'parent6',
                'fee_due_date' => now()->toDateString(),
                'payment_tier' => 'minimal',
            ],
        ];

        foreach ($showcaseStudents as $row) {
            $parent = $this->parents[$row['parent_key']];
            $row['notification_email'] = $notificationEmails[$row['parent_key']];
            $this->admitStudent($admin, $parent, $row, $row['payment_tier']);
        }
    }

    private function seedSchoolPopulation($admin): void
    {
        $firstNames = [
            'Asha', 'Baraka', 'Christina', 'Daniel', 'Esther', 'Faraji', 'Glory', 'Hassan', 'Imani', 'Juma',
            'Khadija', 'Lilian', 'Moses', 'Neema', 'Omary', 'Pendo', 'Rashid', 'Salma', 'Tumaini', 'Upendo',
            'Victor', 'Wema', 'Yusuf', 'Zawadi', 'Abel', 'Beatrice', 'Cosmas', 'Diana', 'Emmanuel', 'Fatuma',
            'Godfrey', 'Halima', 'Isaac', 'Joyce', 'Kelvin', 'Latifa', 'Michael', 'Nuru', 'Oscar', 'Pascal',
        ];

        $surnames = [
            'Mrosso', 'Kimaro', 'Mwakyusa', 'Lyimo', 'Masanja', 'Mwakasege', 'Ngowi', 'Shirima', 'Macha', 'Mrema',
            'Swai', 'Temba', 'Mwenda', 'Kileo', 'Mushi', 'Mollel', 'Ndunguru', 'Sanga', 'Mwakalinga', 'Massawe',
            'Mbwambo', 'Mfinanga', 'Majaliwa', 'Makoye', 'Mlowe', 'Msoffe', 'Munisi', 'Ngassapa', 'Rwegasira', 'Saidi',
        ];

        $classTargets = [
            'Form I' => 40,
            'Form II' => 38,
            'Form III' => 36,
            'Form IV' => 34,
        ];

        $dueDateOffsets = [
            'Form I' => [14, 14, 7, 7, 3, 3, 0, 0, -3, -10, -20],
            'Form II' => [21, 14, 7, 3, 0, -2, -7, -14, -21],
            'Form III' => [-5, -5, -10, -15, -20, 3, 7, 14],
            'Form IV' => [30, 21, 14, 7, 3, 0, -5, -12],
        ];

        $paymentTiers = ['full', 'full', 'partial_good', 'partial_good', 'partial_good', 'partial_low', 'partial_low', 'minimal', 'none', 'none'];

        $sequence = 7;
        $siblingParents = [];

        foreach ($classTargets as $className => $count) {
            $offsets = $dueDateOffsets[$className];

            for ($i = 0; $i < $count; $i++) {
                $first = $firstNames[array_rand($firstNames)];
                $last = $surnames[array_rand($surnames)];
                $middle = $firstNames[array_rand($firstNames)];
                $studentName = "{$first} {$middle} {$last}";

                $useSibling = $i < 3 && !empty($siblingParents) && random_int(0, 1) === 1;
                if ($useSibling) {
                    $parent = $siblingParents[array_rand($siblingParents)];
                } else {
                    $parent = $this->createParentAccount("{$last} {$first}", $sequence);
                    if (count($siblingParents) < 12) {
                        $siblingParents[] = $parent;
                    }
                }

                $admissionNo = sprintf('MBN-2025-%03d', $sequence);
                $sequence++;

                $this->admitStudent($admin, $parent, [
                    'admission_no' => $admissionNo,
                    'name' => $studentName,
                    'class_name' => $className,
                    'notification_email' => $this->parentNotificationEmail($parent, $sequence),
                    'fee_due_date' => now()->addDays($offsets[$i % count($offsets)]),
                ], $paymentTiers[array_rand($paymentTiers)]);
            }
        }
    }

    private function createParentAccount(string $label, int $sequence): User
    {
        $slug = Str::slug($label).'-'.$sequence;
        $phone = self::realPhoneForIndex($sequence);

        return User::updateOrCreate(
            ['email' => "parent.{$slug}@mbonea.sc.tz"],
            [
                'name' => $label.' Guardian',
                'phone' => $phone,
                'password' => bcrypt(self::DEFAULT_PARENT_PASSWORD),
                'role' => 'parent',
            ]
        );
    }

    private function parentNotificationEmail(User $parent, int $sequence): string
    {
        return self::realEmailForIndex($sequence);
    }

    private static function realPhoneForIndex(int $index): string
    {
        $phones = self::SHOWCASE_PHONES;

        return $phones[$index % count($phones)];
    }

    private static function realEmailForIndex(int $index): string
    {
        $emails = self::SHOWCASE_NOTIFICATION_EMAILS;

        return $emails[$index % count($emails)];
    }

    /** Apply real phones/emails to every parent account and student record. */
    private function syncRealContactsThroughoutDatabase(): void
    {
        User::query()
            ->where('role', 'parent')
            ->orderBy('id')
            ->get()
            ->each(function (User $parent, int $index): void {
                $parent->update(['phone' => self::realPhoneForIndex($index)]);
            });

        Student::query()
            ->orderBy('id')
            ->get()
            ->each(function (Student $student, int $index): void {
                $phone = self::realPhoneForIndex($index);
                $email = self::realEmailForIndex($index);

                $student->update([
                    'parent_phone' => $phone,
                    'parent_email' => $email,
                ]);

                StudentParentLink::query()
                    ->where('student_id', $student->id)
                    ->update(['parent_phone' => $phone]);
            });
    }

    private function admitStudent($admin, User $parent, array $row, string $paymentTier): void
    {
        $student = Student::updateOrCreate(
            ['admission_no' => $row['admission_no']],
            [
                'name' => $row['name'],
                'class_name' => $row['class_name'],
                'parent_name' => $parent->name,
                'parent_phone' => $parent->phone,
                'parent_email' => $row['notification_email'] ?? $parent->email,
                'fee_due_date' => $row['fee_due_date'],
                'expected_total_fee' => 0,
                'admitted_at' => now()->subDays(random_int(30, 400)),
                'registered_by_user_id' => $admin->id,
            ]
        );

        ParentStudentAdmission::linkGuardian(
            $student,
            $parent->id,
            random_int(0, 1) ? 'Father' : 'Mother',
            true,
            $parent->phone,
            $admin->id,
        );

        $structure = $this->feeStructures[$row['class_name']] ?? null;
        if ($structure) {
            $student->feeStructures()->sync([$structure->id]);
        }

        $this->seedPaymentsForStudent($student, $admin, $paymentTier);

        $student->refresh();
        if ($student->balance > 0) {
            NotificationLog::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'channel' => 'email',
                    'sent_on' => now()->subDays(random_int(0, 5))->toDateString(),
                    'message' => 'Demo: fee balance reminder email queued.',
                ],
                ['status' => 'sent']
            );
        }
    }

    private function seedPaymentsForStudent(Student $student, User $admin, string $tier): void
    {
        $expected = $student->expected_amount;
        if ($expected <= 0) {
            return;
        }

        $targetPaid = match ($tier) {
            'full' => $expected,
            'partial_good' => (int) round($expected * random_int(55, 85) / 100),
            'partial_low' => (int) round($expected * random_int(25, 50) / 100),
            'minimal' => (int) round($expected * random_int(5, 15) / 100),
            default => 0,
        };

        if ($targetPaid <= 0) {
            return;
        }

        $modes = ['Cash', 'Mobile Money', 'Bank'];
        $remaining = $targetPaid;
        $installments = $tier === 'full' ? random_int(2, 4) : random_int(1, 3);

        for ($i = 0; $i < $installments && $remaining > 0; $i++) {
            $isLast = $i === $installments - 1;
            $amount = $isLast ? $remaining : (int) round($remaining / ($installments - $i));
            $amount = max(10_000, min($amount, $remaining));
            $remaining -= $amount;

            $daysAgo = random_int(3, 120) + ($i * random_int(5, 20));
            $paymentDate = Carbon::today()->subDays($daysAgo)->toDateString();
            $mode = $modes[array_rand($modes)];

            $exists = Receipt::where('student_id', $student->id)
                ->whereDate('payment_date', $paymentDate)
                ->where('amount', $amount)
                ->exists();

            if ($exists) {
                continue;
            }

            $categories = $this->buildPaymentCategories($amount);
            $receipt = Receipt::create([
                'student_id' => $student->id,
                'student_name' => $student->name,
                'class_name' => $student->class_name,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_mode' => $mode,
                'reference' => 'DEMO-'.strtoupper(substr(md5($student->admission_no.$paymentDate.$amount), 0, 8)),
                'note' => 'Demo payment record',
                'user_id' => $admin->id,
            ]);

            $sync = [];
            foreach ($categories as $catName => $catAmount) {
                if (isset($this->categories[$catName])) {
                    $sync[$this->categories[$catName]->id] = ['amount' => $catAmount];
                }
            }
            $receipt->paymentCategories()->sync($sync);
        }
    }

    /** @return array<string, int> */
    private function buildPaymentCategories(int $total): array
    {
        if ($total >= 1_200_000) {
            $tuition = min(1_200_000, $total);
            $remainder = $total - $tuition;
            $categories = ['Tuition' => $tuition];

            if ($remainder >= 180_000) {
                $categories['Transport'] = 180_000;
                $remainder -= 180_000;
            }
            if ($remainder >= 80_000) {
                $categories['Exam Fee'] = 80_000;
                $remainder -= 80_000;
            }
            if ($remainder >= 70_000) {
                $categories['Development Levy'] = 70_000;
                $remainder -= 70_000;
            }
            if ($remainder > 0) {
                $categories['Boarding'] = $remainder;
            }

            return $categories;
        }

        if ($total >= 400_000) {
            $tuition = min(400_000, $total);
            $remainder = $total - $tuition;
            $categories = ['Tuition' => $tuition];

            if ($remainder >= 80_000) {
                $categories['Transport'] = 80_000;
                $remainder -= 80_000;
            }
            if ($remainder >= 50_000) {
                $categories['Exam Fee'] = 50_000;
                $remainder -= 50_000;
            }
            if ($remainder > 0) {
                $categories['Development Levy'] = $remainder;
            }

            return $categories;
        }

        return ['Tuition' => $total];
    }
}
