<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Mobil;
use App\Models\Htransaksi;

class BatchVehicleSummaryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $plates;
    protected $startDate;
    protected $endDate;

    public function __construct(array $plates = [], ?string $startDate = null, ?string $endDate = null)
    {
        $this->plates = $plates;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Polisi',
            'Nomor Chassis',
            'Model',
            'Tahun Pembuatan',
            'Warna',
            'Kode Supplier',
            'Jumlah Transaksi Job',
            'Total Biaya Maintenance (Rp)',
            'Total PPN / Pajak (Rp)',
            'Grand Total (Rp)'
        ];
    }

    public function collection()
    {
        if (empty($this->plates)) {
            return collect([]);
        }

        $vehicles = Mobil::whereIn('nomor_polisi', $this->plates)->get()->keyBy('nomor_polisi');

        $query = Htransaksi::with(['mobil'])
            ->whereHas('mobil', function ($q) {
                $q->whereIn('nomor_polisi', $this->plates);
            });

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tanggal_job', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('tanggal_job', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('tanggal_job', '<=', $this->endDate);
        }

        $transactions = $query->get();

        $rows = [];
        $totalJobs = 0;
        $overallCost = 0;
        $overallTax = 0;
        $overallGrand = 0;

        $no = 1;
        foreach ($this->plates as $nopol) {
            $v = $vehicles->get($nopol);
            $vTx = $transactions->filter(function ($t) use ($nopol) {
                return ($t->mobil->nomor_polisi ?? '') === $nopol;
            });

            $vCost = (float) $vTx->sum('harga_total');
            $vTax = (float) $vTx->sum('harga_pajak');
            $vGrand = $vCost + $vTax;
            $vJobCount = $vTx->count();

            $totalJobs += $vJobCount;
            $overallCost += $vCost;
            $overallTax += $vTax;
            $overallGrand += $vGrand;

            $rows[] = [
                $no++,
                $nopol,
                $v->nomor_chassis ?? '-',
                $v->model ?? '-',
                $v->tahun_pembuatan ?? '-',
                $v->warna ?? '-',
                $v->kode_sup ?? '-',
                $vJobCount,
                $vCost,
                $vTax,
                $vGrand,
            ];
        }

        // Summary row at the bottom
        $rows[] = [
            'TOTAL',
            count($this->plates) . ' KENDARAAN',
            '',
            '',
            '',
            '',
            '',
            $totalJobs . ' JOBS',
            $overallCost,
            $overallTax,
            $overallGrand,
        ];

        return collect($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Per Kendaraan';
    }
}
