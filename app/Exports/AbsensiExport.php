<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AbsensiExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $headers;

    public function __construct($data)
    {
        $this->data = collect($data);

        // Extract headers from first record
        if ($this->data->isNotEmpty()) {
            $this->headers = array_keys($this->data->first());
        } else {
            $this->headers = [];
        }
    }

    /**
     * Return the collection of data
     */
    public function collection()
    {
        return $this->data->map(function ($item) {
            return collect($item)->values();
        });
    }

    /**
     * Return the headings for the Excel file
     */
    public function headings(): array
    {
        // Format headers: replace underscores with spaces and capitalize
        return array_map(function ($header) {
            return ucwords(str_replace('_', ' ', $header));
        }, $this->headers);
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headers));
        $lastRow = $this->data->count() + 1; // +1 for header row

        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0EA5E9'], // Blue color matching the design
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
