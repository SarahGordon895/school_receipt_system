<?php

namespace App\Exports;

use App\Models\Student;
use App\Services\FeeCollectionReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeeCollectionReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> */
    protected $rows;

    public function __construct(array $request)
    {
        $report = app(FeeCollectionReportService::class)->build(Request::create('/', 'POST', $request));
        $this->rows = $report['rows'];
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Admission No',
            'Student Name',
            'Class',
            'Parent Name',
            'Parent Phone',
            'Expected Fee (Tsh)',
            'Amount Paid in Period (Tsh)',
            'Total Paid (Tsh)',
            'Balance (Tsh)',
            'Receipts',
            'Last Payment Date',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        /** @var Student $student */
        $student = $row['student'];

        return [
            $index,
            $student->admission_no ?? 'N/A',
            $student->name,
            $student->class_name ?? 'N/A',
            $student->parent_name ?? 'N/A',
            $student->resolveParentPhone() ?? 'N/A',
            (int) $row['expected'],
            (int) $row['period_paid'],
            (int) $row['total_paid'],
            (int) $row['balance'],
            (int) $row['receipt_count'],
            $row['last_payment_date']
                ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y')
                : 'N/A',
        ];
    }

    public function title(): string
    {
        return 'Fee Collection Report';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;
                $totalCollected = $this->rows->sum('period_paid');

                $sheet->setCellValue('A'.$totalRow, 'TOTAL COLLECTED:');
                $sheet->setCellValue('H'.$totalRow, $totalCollected);
                $sheet->mergeCells('A'.$totalRow.':G'.$totalRow);
                $sheet->getStyle('A'.$totalRow.':H'.$totalRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);
                $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal('right');
            },
        ];
    }
}
