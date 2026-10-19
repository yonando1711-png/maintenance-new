<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mobil;
use App\Models\Htransaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BatchExportController extends Controller
{
    /**
     * Safely parse a date string, returning formatted date or default value.
     */
    private function safeParseDate($value, string $format = 'd-m-Y', string $default = ''): string
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

    /**
     * Parse plate numbers input from either array or delimiter-separated string.
     */
    private function parsePlates($rawPlates): array
    {
        if (is_array($rawPlates)) {
            $plates = $rawPlates;
        } elseif (is_string($rawPlates)) {
            $plates = preg_split('/[\r\n,;]+/', $rawPlates);
        } else {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', $plates))));
    }

    /**
     * Display the Batch Export UI.
     */
    public function index()
    {
        return view('batch-export');
    }

    /**
     * Return preview metrics (vehicle specs, transaction counts, totals) for selected vehicles.
     */
    public function preview(Request $request)
    {
        $plates = $this->parsePlates($request->input('nomor_polisi'));
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if (empty($plates)) {
            return response()->json([
                'success' => true,
                'vehicles' => [],
                'totalVehicles' => 0,
                'totalTransactions' => 0,
                'grandTotal' => 0,
                'hargaPajak' => 0
            ]);
        }

        // Fetch vehicle details
        $vehicles = Mobil::whereIn('nomor_polisi', $plates)->get()->keyBy('nomor_polisi');

        // Build transaction query
        $query = Htransaksi::with(['mobil', 'supplier'])
            ->whereHas('mobil', function ($q) use ($plates) {
                $q->whereIn('nomor_polisi', $plates);
            });

        if ($start && $end) {
            $query->whereBetween('tanggal_job', [$start, $end]);
        } elseif ($start) {
            $query->where('tanggal_job', '>=', $start);
        } elseif ($end) {
            $query->where('tanggal_job', '<=', $end);
        }

        $transactions = $query->get();

        $vehicleStats = [];
        $totalCost = 0;
        $totalTax = 0;

        foreach ($plates as $nopol) {
            $v = $vehicles->get($nopol);
            $vTx = $transactions->filter(function ($t) use ($nopol) {
                return ($t->mobil->nomor_polisi ?? '') === $nopol;
            });

            $vTotal = $vTx->sum(function ($t) {
                return ($t->harga_total ?? 0) + ($t->harga_pajak ?? 0);
            });
            $vTax = $vTx->sum('harga_pajak');

            $vehicleStats[] = [
                'nomor_polisi' => $nopol,
                'exists' => $v !== null,
                'nomor_chassis' => $v->nomor_chassis ?? '-',
                'model' => $v->model ?? '-',
                'tahun_pembuatan' => $v->tahun_pembuatan ?? '-',
                'warna' => $v->warna ?? '-',
                'kode_sup' => $v->kode_sup ?? '-',
                'transaction_count' => $vTx->count(),
                'total_cost' => (float) $vTotal,
                'total_cost_formatted' => 'Rp ' . number_format($vTotal, 0, ',', '.'),
            ];

            $totalCost += $vTotal;
            $totalTax += $vTax;
        }

        return response()->json([
            'success' => true,
            'vehicles' => $vehicleStats,
            'totalVehicles' => count($plates),
            'totalTransactions' => $transactions->count(),
            'grandTotal' => (float) $totalCost,
            'grandTotalFormatted' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'totalTax' => (float) $totalTax,
            'totalTaxFormatted' => 'Rp ' . number_format($totalTax, 0, ',', '.')
        ]);
    }

    /**
     * Download multi-vehicle export (Excel or PDF payload).
     */
    public function download(Request $request)
    {
        $plates = $this->parsePlates($request->input('nomor_polisi'));
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $format = $request->input('format', 'xlsx');
        $withTanggalUpdate = $request->boolean('with_tanggal_update', false);

        $type = $request->input('type', 'detail');

        if ($type === 'summary') {
            if ($format === 'xlsx' || $format === 'csv') {
                try {
                    $exportClass = new \App\Exports\BatchVehicleSummaryExport($plates, $start, $end);
                    $ext = $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;
                    $filename = 'Batch_Summary_Kendaraan_' . date('Ymd_His') . '.' . $format;
                    return \Maatwebsite\Excel\Facades\Excel::download($exportClass, $filename, $ext);
                } catch (\Exception $e) {
                    Log::error("Batch Summary Excel Export Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
                }
            }

            if ($format === 'json') {
                try {
                    $vehicles = Mobil::whereIn('nomor_polisi', $plates)->get()->keyBy('nomor_polisi');

                    $query = Htransaksi::with(['mobil'])
                        ->whereHas('mobil', function ($q) use ($plates) {
                            $q->whereIn('nomor_polisi', $plates);
                        });

                    if ($start && $end) {
                        $query->whereBetween('tanggal_job', [$start, $end]);
                    } elseif ($start) {
                        $query->where('tanggal_job', '>=', $start);
                    } elseif ($end) {
                        $query->where('tanggal_job', '<=', $end);
                    }

                    $transactions = $query->get();

                    $rows = [];
                    $totalJobs = 0;
                    $overallCost = 0;
                    $overallTax = 0;
                    $overallGrand = 0;

                    foreach ($plates as $nopol) {
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
                            'nomor_polisi' => $nopol,
                            'nomor_chassis' => $v->nomor_chassis ?? '-',
                            'model' => $v->model ?? '-',
                            'tahun_pembuatan' => $v->tahun_pembuatan ?? '-',
                            'warna' => $v->warna ?? '-',
                            'kode_sup' => $v->kode_sup ?? '-',
                            'job_count' => $vJobCount,
                            'cost' => $vCost,
                            'cost_formatted' => number_format($vCost, 0, ',', '.'),
                            'tax' => $vTax,
                            'tax_formatted' => number_format($vTax, 0, ',', '.'),
                            'grand_total' => $vGrand,
                            'grand_total_formatted' => number_format($vGrand, 0, ',', '.'),
                        ];
                    }

                    return response()->json([
                        'success' => true,
                        'summaryRows' => $rows,
                        'totalVehicles' => count($plates),
                        'totalJobs' => $totalJobs,
                        'overallCost' => $overallCost,
                        'overallCostFormatted' => 'Rp ' . number_format($overallCost, 0, ',', '.'),
                        'overallTax' => $overallTax,
                        'overallTaxFormatted' => 'Rp ' . number_format($overallTax, 0, ',', '.'),
                        'overallGrand' => $overallGrand,
                        'overallGrandFormatted' => 'Rp ' . number_format($overallGrand, 0, ',', '.'),
                        'startDate' => $start ? Carbon::parse($start)->format('d-m-Y') : null,
                        'endDate' => $end ? Carbon::parse($end)->format('d-m-Y') : null,
                        'generatedAt' => date('d-m-Y H:i:s'),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Batch Summary JSON Export Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
                }
            }
        }

        $query = Htransaksi::with(['mobil', 'supplier', 'dtransaksi'])
            ->whereHas('mobil', function ($q) use ($plates) {
                $q->whereIn('nomor_polisi', $plates);
            });

        if ($start && $end) {
            $query->whereBetween('tanggal_job', [$start, $end]);
        } elseif ($start) {
            $query->where('tanggal_job', '>=', $start);
        } elseif ($end) {
            $query->where('tanggal_job', '<=', $end);
        }

        // Sort by vehicle plate, then by tanggal_job
        $query->join('mobil', 'htransaksi.nomor_chassis', '=', 'mobil.nomor_chassis')
            ->select('htransaksi.*')
            ->orderBy('mobil.nomor_polisi')
            ->orderBy('htransaksi.tanggal_job', 'asc');

        if ($format === 'xlsx' || $format === 'csv') {
            try {
                $exportClass = new \App\Exports\BatchVehicleTransactionsExport($query, $plates, $withTanggalUpdate);
                $ext = $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;
                $filename = 'Batch_Maintenance_Export_' . date('Ymd_His') . '.' . $format;
                return \Maatwebsite\Excel\Facades\Excel::download($exportClass, $filename, $ext);
            } catch (\Exception $e) {
                Log::error("Batch Excel Export Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
            }
        }

        // Format for PDF data generation (returns grouped data per vehicle for client-side pdfmake)
        $vehicles = Mobil::whereIn('nomor_polisi', $plates)->get()->keyBy('nomor_polisi');
        $transactions = $query->get();

        $groupedData = [];
        $overallGrandTotal = 0;
        $overallTax = 0;

        foreach ($plates as $nopol) {
            $v = $vehicles->get($nopol);
            $vTransactions = $transactions->filter(function ($t) use ($nopol) {
                return ($t->mobil->nomor_polisi ?? '') === $nopol;
            });

            $txRows = [];
            $vTotalCost = 0;
            $vTaxCost = 0;

            foreach ($vTransactions as $t) {
                $vTotalCost += ($t->harga_total ?? 0) + ($t->harga_pajak ?? 0);
                $vTaxCost += ($t->harga_pajak ?? 0);

                $details = $t->dtransaksi;
                $nomorJobFull = $t->nomor_job . ' - ' .
                    ($t->supplier->kode_supplier ?? '-') . ' - ' .
                    ($t->supplier->nama_supplier ?? '-');

                $kodeSup = $t->kode_sup ?? '';
                $workshopHarent = (strpos($kodeSup, '*INTERNAL') !== false) ? 'Workshop Harent' : '';
                $nomorSv = $t->nomor_sv ?? '';
                $maintenanceService = (trim($nomorSv) === 'M') ? 'Maintenance' : $nomorSv;

                if ($details->count() > 0) {
                    $first = $details->first();
                    $txRows[] = [
                        'nomor_job' => $nomorJobFull,
                        'tanggal_job' => $this->safeParseDate($t->tanggal_job),
                        'posisi_km' => $t->posisi_km ?? '-',
                        'tipe' => $t->is_internal ? 'Internal' : 'External',
                        'workshop_harent' => $workshopHarent,
                        'maintenance_service' => $maintenanceService,
                        'product' => $first->product ?? '',
                        'deskripsi' => $first->deskripsi ?? '-',
                        'tanggal_part_keluar' => $this->safeParseDate($first->tanggal_part_keluar, 'd-m-Y H:i:s'),
                        'jumlah' => $first->jumlah ?? '-',
                        'harga' => $first->harga ?? 0,
                        'harga_total' => $t->harga_total ?? 0,
                        'harga_pajak' => $t->harga_pajak ?? 0,
                        'keterangan' => $first->keterangan ?? ($t->keterangan ?? '-'),
                        'tanggal_close' => $this->safeParseDate($t->tanggal_close),
                        'is_detail' => false,
                    ];

                    foreach ($details->skip(1) as $d) {
                        $isTaxLine = ($d->note === 'tax');
                        $txRows[] = [
                            'nomor_job' => '',
                            'tanggal_job' => '',
                            'posisi_km' => '',
                            'tipe' => '',
                            'workshop_harent' => '',
                            'maintenance_service' => '',
                            'product' => $d->product ?? '',
                            'deskripsi' => $d->deskripsi ?? '-',
                            'tanggal_part_keluar' => $this->safeParseDate($d->tanggal_part_keluar, 'd-m-Y H:i:s'),
                            'jumlah' => $isTaxLine ? '' : ($d->jumlah ?? '-'),
                            'harga' => $isTaxLine ? '' : ($d->harga ?? 0),
                            'harga_total' => '',
                            'harga_pajak' => $isTaxLine ? ($d->value ?? 0) : '',
                            'keterangan' => '',
                            'tanggal_close' => $this->safeParseDate($t->tanggal_close),
                            'is_detail' => true,
                        ];
                    }
                } else {
                    $txRows[] = [
                        'nomor_job' => $nomorJobFull,
                        'tanggal_job' => $this->safeParseDate($t->tanggal_job),
                        'posisi_km' => $t->posisi_km ?? '-',
                        'tipe' => $t->is_internal ? 'Internal' : 'External',
                        'workshop_harent' => $workshopHarent,
                        'maintenance_service' => $maintenanceService,
                        'product' => '',
                        'deskripsi' => '-',
                        'tanggal_part_keluar' => '',
                        'jumlah' => '-',
                        'harga' => 0,
                        'harga_total' => $t->harga_total ?? 0,
                        'harga_pajak' => $t->harga_pajak ?? 0,
                        'keterangan' => $t->keterangan ?? '-',
                        'tanggal_close' => $this->safeParseDate($t->tanggal_close),
                        'is_detail' => false,
                    ];
                }
            }

            $overallGrandTotal += $vTotalCost;
            $overallTax += $vTaxCost;

            $groupedData[] = [
                'vehicle' => [
                    'nomor_polisi' => $nopol,
                    'nomor_chassis' => $v->nomor_chassis ?? '-',
                    'nomor_mesin' => $v->nomor_mesin ?? '-',
                    'model' => $v->model ?? '-',
                    'warna' => $v->warna ?? '-',
                    'tahun_pembuatan' => $v->tahun_pembuatan ?? '-',
                    'tanggal_pembelian' => $v->tanggal_pembelian ?? '-',
                    'kode_sup' => $v->kode_sup ?? '-',
                ],
                'transactions' => $txRows,
                'totalCost' => $vTotalCost,
                'totalTax' => $vTaxCost,
            ];
        }

        return response()->json([
            'success' => true,
            'groupedData' => $groupedData,
            'overallGrandTotal' => $overallGrandTotal,
            'overallTax' => $overallTax,
            'generatedAt' => date('d-m-Y H:i:s'),
        ]);
    }
}
