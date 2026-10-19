<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class BatchVehicleTransactionsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $query;
    protected $plates;
    protected $includeTanggalUpdate;

    public function __construct($query, array $plates = [], bool $includeTanggalUpdate = false)
    {
        $this->query = $query;
        $this->plates = $plates;
        $this->includeTanggalUpdate = $includeTanggalUpdate;
    }

    private function safeParseDate($value, string $format = 'd-m-Y', string $default = '-'): string
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return $default;
        }
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return $default;
        }
    }

    public function headings(): array
    {
        $headings = [
            'Nomor Polisi',
            'Nomor Chassis',
            'Model',
            'Nomor Job',
            'Tanggal Job',
            'Posisi KM',
            'Tipe',
            'Workshop Harent',
            'Maintenance/Service',
            'Product',
            'Deskripsi',
        ];

        if ($this->includeTanggalUpdate) {
            $headings[] = 'Tanggal Update Spareparts';
        }

        return array_merge($headings, [
            'Jumlah',
            'Harga Satuan (Rp)',
            'Harga Total (Rp)',
            'Harga Pajak (Rp)',
            'Keterangan',
            'Tanggal Close'
        ]);
    }

    public function collection()
    {
        $results = $this->query->get();

        $grandTotal = 0;
        $hargaTotal = 0;
        $hargaPajak = 0;

        $exportData = [];

        foreach ($results as $transaction) {
            $grandTotal += ($transaction->harga_total ?? 0) + ($transaction->harga_pajak ?? 0);
            $hargaTotal += $transaction->harga_total ?? 0;
            $hargaPajak += $transaction->harga_pajak ?? 0;

            $tipe = $transaction->is_internal ? 'Internal' : 'External';
            $details = $transaction->dtransaksi;

            $nomorJobFull = $transaction->nomor_job . ' - ' .
                ($transaction->supplier->kode_supplier ?? '-') . ' - ' .
                ($transaction->supplier->nama_supplier ?? '-');

            $kodeSup = $transaction->kode_sup ?? '';
            $workshopHarent = (strpos($kodeSup, '*INTERNAL') !== false) ? 'Workshop Harent' : '';
            
            $nomorSv = $transaction->nomor_sv ?? '';
            $maintenanceService = (trim($nomorSv) === 'M') ? 'Maintenance' : $nomorSv;

            $nopol = $transaction->mobil->nomor_polisi ?? '-';
            $chassis = $transaction->mobil->nomor_chassis ?? '-';
            $model = $transaction->mobil->model ?? '-';

            if ($details && $details->count() > 0) {
                // First detail row
                $first = $details->first();
                $row = [
                    $nopol,
                    $chassis,
                    $model,
                    $nomorJobFull,
                    $this->safeParseDate($transaction->tanggal_job),
                    $transaction->posisi_km ?? '-',
                    $tipe,
                    $workshopHarent,
                    $maintenanceService,
                    $first->product ?? '',
                    $first->deskripsi ?? '-',
                ];

                if ($this->includeTanggalUpdate) {
                    $row[] = $this->safeParseDate($first->tanggal_part_keluar, 'd-m-Y H:i:s', '');
                }

                $row = array_merge($row, [
                    $first->jumlah ?? '-',
                    $first->harga ?? 0,
                    $transaction->harga_total ?? 0,
                    $transaction->harga_pajak ?? 0,
                    $first->keterangan ?? ($transaction->keterangan ?? '-'),
                    $this->safeParseDate($transaction->tanggal_close)
                ]);

                $exportData[] = $row;

                // Subsequent detail rows
                foreach ($details->skip(1) as $detail) {
                    $isTaxLine = ($detail->note === 'tax');
                    $subRow = [
                        $nopol,
                        $chassis,
                        $model,
                        '', // nomor_job empty to show it belongs to above job
                        '', // tanggal_job
                        '', // posisi_km
                        '', // tipe
                        '', // workshop_harent
                        '', // maintenance_service
                        $detail->product ?? '',
                        $detail->deskripsi ?? '-',
                    ];

                    if ($this->includeTanggalUpdate) {
                        $subRow[] = $this->safeParseDate($detail->tanggal_part_keluar, 'd-m-Y H:i:s', '');
                    }

                    $subRow = array_merge($subRow, [
                        $isTaxLine ? '' : ($detail->jumlah ?? '-'),
                        $isTaxLine ? '' : ($detail->harga ?? 0),
                        '', // harga_total
                        $isTaxLine ? ($detail->value ?? 0) : '',
                        '', // keterangan
                        $this->safeParseDate($transaction->tanggal_close)
                    ]);

                    $exportData[] = $subRow;
                }
            } else {
                $row = [
                    $nopol,
                    $chassis,
                    $model,
                    $nomorJobFull,
                    $this->safeParseDate($transaction->tanggal_job),
                    $transaction->posisi_km ?? '-',
                    $tipe,
                    $workshopHarent,
                    $maintenanceService,
                    '',
                    '-',
                ];

                if ($this->includeTanggalUpdate) {
                    $row[] = '';
                }

                $row = array_merge($row, [
                    '-',
                    0,
                    $transaction->harga_total ?? 0,
                    $transaction->harga_pajak ?? 0,
                    $transaction->keterangan ?? '-',
                    $this->safeParseDate($transaction->tanggal_close)
                ]);

                $exportData[] = $row;
            }
        }

        // Summary row at the bottom
        $colCount = $this->includeTanggalUpdate ? 13 : 12;
        $summaryRow = array_fill(0, $colCount, '');
        $summaryRow[0] = 'TOTAL KESELURUHAN';
        $summaryRow[] = (float) $hargaTotal;
        $summaryRow[] = (float) $hargaPajak;
        $summaryRow[] = 'GRAND TOTAL: Rp ' . number_format($grandTotal, 0, ',', '.');
        $summaryRow[] = '';

        $exportData[] = $summaryRow;

        return collect($exportData);
    }
}
