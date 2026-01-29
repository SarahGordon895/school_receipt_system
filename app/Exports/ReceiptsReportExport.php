<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptsReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    protected $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Receipt::with(['classRoom', 'stream', 'paymentCategories', 'user']);

        // Apply date range filter
        $dateRange = $this->request['date_range'];
        if ($dateRange === 'custom') {
            $query->whereDate('payment_date', '>=', $this->request['start_date'])
                  ->whereDate('payment_date', '<=', $this->request['end_date']);
        } else {
            $dateRange = $this->getDateRange($dateRange);
            $query->whereDate('payment_date', '>=', $dateRange[0])
                  ->whereDate('payment_date', '<=', $dateRange[1]);
        }

        // Apply other filters
        $query->when($this->request['class_id'] ?? null, fn($q) => $q->where('class_id', $this->request['class_id']))
              ->when($this->request['stream_id'] ?? null, fn($q) => $q->where('stream_id', $this->request['stream_id']))
              ->when($this->request['payment_category_id'] ?? null, fn($q) => $q->where('payment_category_id', $this->request['payment_category_id']))
              ->when($this->request['payment_mode'] ?? null, fn($q) => $q->where('payment_mode', $this->request['payment_mode']))
              ->when($this->request['min_amount'] ?? null, fn($q) => $q->where('amount', '>=', $this->request['min_amount']))
              ->when($this->request['max_amount'] ?? null, fn($q) => $q->where('amount', '<=', $this->request['max_amount']));

        return $query->orderBy('payment_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Receipt No',
            'Student Name',
            'Class',
            'Stream',
            'Payment Category',
            'Amount',
            'Payment Date',
            'Payment Mode',
            'Reference',
            'Notes',
            'Created By',
            'Created At'
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->receipt_no,
            $receipt->student_name,
            $receipt->classRoom?->name ?? 'N/A',
            $receipt->stream?->name ?? 'N/A',
            $receipt->paymentCategories->pluck('name')->implode(', ') ?? 'N/A',
            number_format($receipt->amount, 2),
            \Carbon\Carbon::parse($receipt->payment_date)->format('d/m/Y'),
            $receipt->payment_mode,
            $receipt->reference ?? 'N/A',
            $receipt->note ?? 'N/A',
            $receipt->user?->name ?? 'N/A',
            $receipt->created_at->format('d/m/Y H:i:s')
        ];
    }

    public function title(): string
    {
        return 'Receipts Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Add total row
                $totalAmount = $this->collection()->sum('amount');
                $totalRow = $highestRow + 1;
                
                // Set total row values
                $sheet->setCellValue('A' . $totalRow, 'TOTAL:');
                $sheet->setCellValue('F' . $totalRow, $totalAmount);
                
                // Style total row
                $sheet->getStyle('A' . $totalRow . ':F' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2']
                    ]
                ]);
                
                // Merge cells for total label
                $sheet->mergeCells('A' . $totalRow . ':E' . $totalRow);
                $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal('right');
            },
        ];
    }

    private function getDateRange($range)
    {
        $now = now();
        
        return match($range) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'yesterday' => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
            'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'last_year' => [$now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            default => [$now->toDateString(), $now->toDateString()],
        };
    }
}
