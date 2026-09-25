<?php

namespace App\Services;

use App\Models\OdooSetting;
use App\Models\ImportHistory;
use App\Models\Htransaksi;
use App\Models\Dtransaksi;
use App\Models\Mobil;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OdooSyncService
{
    protected $setting;
    protected $url;
    protected $db;
    protected $user;
    protected $apiKey;
    protected $uid = null;

    public function __construct()
    {
        $this->setting = OdooSetting::first();
        if ($this->setting) {
            $this->url = $this->setting->odoo_url;
            $this->db = $this->setting->database;
            $this->user = $this->setting->user_email;
            $this->apiKey = $this->setting->api_key;
        }
    }

    public function sync($sourceType = 'Manual', $targetJo = null, $isFullSync = false, $phase = 'all', $offset = 0, $limit = 500)
    {
        if (!$this->setting) {
            return ['success' => false, 'message' => 'Odoo settings not configured.'];
        }

        try {
            // 1. Authenticate with Odoo
            $authData = $this->odooCall('common', 'authenticate', [$this->db, $this->user, $this->apiKey, (object)[]]);
            
            if (!$authData['success'] || empty($authData['result'])) {
                $errorMsg = $authData['error'] ?? 'Authentication failed';
                $this->logHistory($sourceType, 'Failed', 0, "Auth Error: " . $errorMsg);
                return ['success' => false, 'message' => "Odoo Auth Failed: $errorMsg"];
            }

            $this->uid = $authData['result'];
            $lastSyncUTC = $this->setting->last_sync ? Carbon::parse($this->setting->last_sync)->setTimezone('UTC')->format('Y-m-d H:i:s') : null;

            $ros = [];
            $moves = [];
            $hasMore = false;
            $nextOffset = $offset;

            // 2. Fetch Repair Orders (Job Orders)
            if ($phase === 'all' || $phase === 'ro') {
                $roDomain = [];
                if ($targetJo) {
                    if (is_array($targetJo)) {
                        $roDomain[] = ['name', 'in', $targetJo];
                    } else {
                        $roDomain[] = ['name', '=', $targetJo];
                    }
                } elseif ($lastSyncUTC) {
                    $roDomain[] = ['write_date', '>=', $lastSyncUTC];
                } else {
                    $roDomain[] = ['create_date', '>=', '2025-11-30 00:00:00'];
                }

                $rosData = $this->odooCall('object', 'execute_kw', [
                    $this->db, $this->uid, $this->apiKey,
                    'repair.order', 'search_read',
                    [$roDomain],
                    [
                        'fields' => ['name', 'lot_id', 'lot_vehicle_ref', 'service_type', 'km_pickup', 'km_return', 'compute_job_card_repair_notes', 'product_model_type_combined', 'partner_id', 'vendor_id', 'order_line_ids', 'repair_service_ids', 'state', 'create_date', 'move_id', 'vendor_bill_ids', 'schedule_date', 'repair_end_datetime', 'is_internal_repair'],
                        'limit' => $limit,
                        'offset' => $offset,
                        'order' => 'create_date asc'
                    ]
                ]);
                $ros = $rosData['result'] ?? [];
                
                if (count($ros) === $limit) {
                    $hasMore = true;
                    $nextOffset = $offset + $limit;
                }
            }

            // 3. Fetch Linked Vendor Bills (only if RO phase is done or phase is 'move')
            if (($phase === 'all' && !$hasMore) || $phase === 'move') {
                $moveOffset = ($phase === 'move') ? $offset : 0;
                $billDomain = [['repair_id', '!=', false], ['state', '!=', 'cancel'], ['name', 'like', 'BILLS/']];
                if ($targetJo) {
                    $targetJoNames = is_array($targetJo) ? $targetJo : [$targetJo];
                    $billDomain[] = ['repair_id.name', 'in', $targetJoNames];
                } elseif ($lastSyncUTC) {
                    $billDomain[] = ['write_date', '>=', $lastSyncUTC];
                } else {
                    $billDomain[] = ['create_date', '>=', '2025-11-30 00:00:00'];
                }

                $movesData = $this->odooCall('object', 'execute_kw', [
                    $this->db, $this->uid, $this->apiKey,
                    'account.move', 'search_read',
                    [$billDomain],
                    [
                        'fields' => ['name', 'partner_id', 'invoice_date', 'ref', 'line_ids', 'state', 'repair_id', 'create_date', 'amount_tax', 'amount_untaxed'], 
                        'limit' => $limit,
                        'offset' => $moveOffset,
                        'order' => 'create_date asc'
                    ]
                ]);
                
                $moves = $movesData['result'] ?? [];
                
                if (count($moves) === $limit) {
                    $hasMore = true;
                    $nextOffset = $moveOffset + $limit;
                    $phase = 'move';
                } else {
                    $hasMore = false;
                    $nextOffset = 0;
                }
            }
            
            if (empty($ros) && empty($moves)) {
                if (!$hasMore && $phase !== 'ro') {
                    $this->setting->update(['last_sync' => now()]);
                }
                return [
                    'success' => true, 
                    'message' => 'No more records.', 
                    'items' => 0, 
                    'hasMore' => false, 
                    'nextOffset' => 0,
                    'phase' => $phase
                ];
            }

            // --- Pre-fetch Related Data in Batch ---
            $allLotIds = [];
            $allLineIds = []; // account.move.line
            $allRoLineIds = []; // repair.order.line
            $allRoServiceIds = []; // repair.service
            $allRoMoveIds = []; // account.move (Sales Invoices)
            $allVendorBillIds = []; // vendor bills from ROs
            
            foreach ($ros as $ro) {
                if (!empty($ro['lot_id'])) $allLotIds[] = is_array($ro['lot_id']) ? $ro['lot_id'][0] : $ro['lot_id'];
                if (!empty($ro['order_line_ids'])) $allRoLineIds = array_merge($allRoLineIds, $ro['order_line_ids']);
                if (!empty($ro['repair_service_ids'])) $allRoServiceIds = array_merge($allRoServiceIds, $ro['repair_service_ids']);
                if (!empty($ro['move_id'])) $allRoMoveIds[] = is_array($ro['move_id']) ? $ro['move_id'][0] : $ro['move_id'];
                if (!empty($ro['vendor_bill_ids'])) $allVendorBillIds = array_merge($allVendorBillIds, $ro['vendor_bill_ids']);
            }
            foreach ($moves as $move) {
                if (!empty($move['line_ids'])) $allLineIds = array_merge($allLineIds, $move['line_ids']);
            }

            $allLotIds = array_values(array_unique($allLotIds));
            $allLineIds = array_values(array_unique($allLineIds));
            $allRoLineIds = array_values(array_unique($allRoLineIds));
            $allRoServiceIds = array_values(array_unique($allRoServiceIds));
            $allVendorBillIds = array_values(array_unique($allVendorBillIds));

            // Fetch Lots (Vehicles)
            $lotsMap = [];
            if (!empty($allLotIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'stock.lot', 'read', [$allLotIds], ['fields' => ['name', 'ref', 'color_id', 'purchase_date', 'vehicle_year', 'engine_number', 'product_id']]]);
                if ($res['success']) foreach ($res['result'] as $l) $lotsMap[$l['id']] = $l;
            }

            // Fetch RO Lines
            $roLinesMap = [];
            if (!empty($allRoLineIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'repair.order.line', 'read', [$allRoLineIds], ['fields' => ['name', 'quantity', 'price_unit', 'price_subtotal', 'product_template_id', 'stock_move_id']]]);
                if ($res['success']) foreach ($res['result'] as $rl) $roLinesMap[$rl['id']] = $rl;
            }

            // Fetch Stock Moves linked to RO Lines
            $stockMovesMap = [];
            $allStockMoveIds = [];
            foreach ($roLinesMap as $rl) {
                if (!empty($rl['stock_move_id']) && is_array($rl['stock_move_id'])) {
                    $allStockMoveIds[] = $rl['stock_move_id'][0];
                }
            }
            $allStockMoveIds = array_values(array_unique($allStockMoveIds));
            if (!empty($allStockMoveIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'stock.move', 'read', [$allStockMoveIds], ['fields' => ['date']]]);
                if ($res['success']) foreach ($res['result'] as $sm) $stockMovesMap[$sm['id']] = $sm;
            }

            // Fetch Service Lines
            $roServicesMap = [];
            if (!empty($allRoServiceIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'repair.service', 'read', [$allRoServiceIds], ['fields' => ['name', 'quantity', 'price_subtotal', 'product_id']]]);
                if (!$res['success']) \Log::error("REPAIR SERVICE ERROR: ".json_encode($res)); if ($res['success']) foreach ($res['result'] as $rs) $roServicesMap[$rs['id']] = $rs;
            }

            // Fetch Bill Lines
            $billLinesMap = [];
            if (!empty($allLineIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'account.move.line', 'read', [$allLineIds], ['fields' => ['move_id', 'name', 'quantity', 'price_unit', 'price_subtotal', 'display_type', 'debit', 'credit', 'balance', 'product_id']]]);
                if ($res['success']) {
                    foreach ($res['result'] as $bl) {
                        $mId = is_array($bl['move_id']) ? $bl['move_id'][0] : $bl['move_id'];
                        $billLinesMap[$mId][] = $bl;
                    }
                }
            }
            
            // Fetch RO Linked Moves (Sales Invoices for Revenue Tax)
            $roMovesMap = [];
            if (!empty($allRoMoveIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'account.move', 'read', [$allRoMoveIds], ['fields' => ['amount_tax']]]);
                if ($res['success']) foreach ($res['result'] as $rm) $roMovesMap[$rm['id']] = $rm;
            }

            // Fetch Vendor Bills linked to ROs (for tax amounts)
            // Map: repair_order_id => bill data
            $vendorBillsByRo = [];
            if (!empty($allVendorBillIds)) {
                $res = $this->odooCall('object', 'execute_kw', [$this->db, $this->uid, $this->apiKey, 'account.move', 'read', [$allVendorBillIds], ['fields' => ['repair_id', 'amount_tax', 'amount_untaxed', 'amount_total', 'state']]]);
                // Keep the bill with highest amount_total if multiple
                if ($res['success']) {
                    foreach ($res['result'] as $bill) {
                        $roId = is_array($bill['repair_id']) ? $bill['repair_id'][0] : $bill['repair_id'];
                        if ($roId) {
                            if (!isset($vendorBillsByRo[$roId]) || $bill['amount_total'] > $vendorBillsByRo[$roId]['amount_total']) {
                                $vendorBillsByRo[$roId] = $bill;
                            }
                        }
                    }
                }
            }
            // --- End Batch Pre-fetch ---

            DB::beginTransaction();
            $itemsSynced = 0;

            // Step A: Process Job Orders (Repair Orders)
            foreach ($ros as $ro) {
                try {
                $jobNo = $ro['name'];
                $chassisNo = $ro['lot_vehicle_ref'];
                $orderLotId = is_array($ro['lot_id']) ? $ro['lot_id'][0] : null;
                $plateNo = is_array($ro['lot_id']) ? $ro['lot_id'][1] : null;

                $purchaseDate = null;
                $vehicleYear = null;
                $engineNumber = null;
                $warna = '';

                if ($orderLotId && isset($lotsMap[$orderLotId])) {
                    $lot = $lotsMap[$orderLotId];
                    if (!$chassisNo) $chassisNo = $lot['name'];
                    if (!$plateNo || preg_match('/^\d+$/', $plateNo)) $plateNo = $lot['ref'];
                    if (!empty($lot['purchase_date'])) $purchaseDate = $lot['purchase_date'];
                    if (!empty($lot['vehicle_year'])) $vehicleYear = $lot['vehicle_year'];
                    if (!empty($lot['engine_number'])) $engineNumber = $lot['engine_number'];
                    if (!empty($lot['color_id']) && is_array($lot['color_id'])) $warna = $lot['color_id'][1];
                }

                // Use create_date for the date filter instead of schedule_date, because
                // schedule_date can be backdated (e.g. a JO created in Jan 2026 may have
                // schedule_date in Nov 2025), which would incorrectly skip legitimate orders.
                $jobDate = $ro['create_date'] ? Carbon::parse($ro['create_date'])->format('Y-m-d') : null;
                if ($jobDate && $jobDate < '2025-11-30') {
                    Log::info('Skipping JO due to create_date < 2025-11-30: ' . ($ro['name'] ?? 'unknown') . ' (' . $jobDate . ')');
                    continue;
                }

                if (!$chassisNo) continue;

                // Sync Vehicle
                $mobil = Mobil::where('nomor_chassis', $chassisNo)->first();

                // Extract vehicle model from product_id if available (Odoo stock.lot -> product.product)
                // product_id is a many2one [id, display_name] e.g. [9025, "[TYT-ALPG25-AT-B] TOYOTA ALPHARD 2.5 G A/T BENSIN"]
                $vehicleModel = '';
                if (!empty($lot['product_id']) && is_array($lot['product_id'])) {
                    $vehicleModel = mb_substr($lot['product_id'][1], 0, 100, 'UTF-8');
                }

                if ($mobil) {
                    $updateData = ['nomor_polisi' => $plateNo ?: $mobil->nomor_polisi];
                    
                    // Update vehicle details if we got them from Odoo
                    if ($purchaseDate) {
                        $updateData['tanggal_pembelian'] = $purchaseDate;
                    }
                    if ($vehicleYear) {
                        $updateData['tahun_pembuatan'] = $vehicleYear;
                    }
                    if ($engineNumber) {
                        $updateData['nomor_mesin'] = $engineNumber;
                    }
                    if ($warna) {
                        $updateData['warna'] = $warna;
                    }
                    if ($vehicleModel) {
                        $updateData['model'] = $vehicleModel;
                    }
                    
                    $mobil->update($updateData);
                } else {
                    $mobil = Mobil::create([
                        'nomor_chassis' => $chassisNo,
                        'nomor_polisi' => mb_substr($plateNo ?? '', 0, 25, 'UTF-8'),
                        'nopol' => mb_substr($plateNo ?? '', 0, 25, 'UTF-8'),
                        'warna' => $warna ?: '',
                        'tanggal_pembelian' => $purchaseDate ?: now()->format('Y-m-d'),
                        'nomor_kk' => '',
                        'model' => $vehicleModel,
                        'tahun_pembuatan' => $vehicleYear ?: now()->format('Y'),
                        'nomor_mesin' => $engineNumber ?: '',
                        'kode_sup' => '',
                    ]);
                }

                // Sync Vendor/Supplier from repair.order's native vendor_id field.
                // This is the actual Vendor field in Odoo, distinct from partner_id (customer).
                $supplierId = '0';
                if (!empty($ro['vendor_id']) && is_array($ro['vendor_id'])) {
                    $supplierId = 'O-' . $ro['vendor_id'][0];
                    \App\Models\Supplier::updateOrCreate(
                        ['kode_supplier' => $supplierId],
                        ['nama_supplier' => mb_substr($ro['vendor_id'][1], 0, 100, 'UTF-8')]
                    );
                }

                // Sync Customer from repair.order's partner_id field.
                $customerId = 0;
                if (!empty($ro['partner_id']) && is_array($ro['partner_id'])) {
                    $partnerNameRaw = $ro['partner_id'][1];
                    $kodeCustomer = '';
                    $namaCustomer = trim($partnerNameRaw);

                    if (preg_match('/^\[(.*?)\]\s*(.*)$/', $partnerNameRaw, $matches)) {
                        $kodeCustomer = trim($matches[1]);
                        $namaCustomer = trim($matches[2]);
                    }

                    $customer = null;
                    if ($kodeCustomer) {
                        $customer = \App\Models\Customer::where('kode_customer', $kodeCustomer)->first();
                    }
                    if (!$customer && $namaCustomer) {
                        $customer = \App\Models\Customer::where('nama_customer', $namaCustomer)->first();
                    }

                    if (!$customer) {
                        $customer = \App\Models\Customer::create([
                            'kode_customer' => $kodeCustomer ?: 'O-' . $ro['partner_id'][0],
                            'nama_customer' => mb_substr($namaCustomer, 0, 100, 'UTF-8')
                        ]);
                    }
                    $customerId = $customer->id;
                }

                // Sync Head
                $headerData = [
                    'nomor_job' => $jobNo,
                    'nomor_invoice' => $jobNo, // Default to jobNo if no bill yet
                    'tanggal_job' => $ro['schedule_date'] ? Carbon::parse($ro['schedule_date'])->format('Y-m-d') : null,
                    'tanggal_invoice' => $ro['schedule_date'] ? Carbon::parse($ro['schedule_date'])->format('Y-m-d') : null,
                    'tanggal_close' => $ro['repair_end_datetime'] ? Carbon::parse($ro['repair_end_datetime'])->format('Y-m-d') : null,
                    'nomor_chassis' => mb_substr($chassisNo, 0, 50, 'UTF-8'),
                    'posisi_km' => $ro['km_return'] ?: ($ro['km_pickup'] ?? 0),
                    'mtrs' => $ro['km_return'] ?: ($ro['km_pickup'] ?? 0),
                    'keterangan' => mb_substr($this->sanitizeText(strip_tags($ro['compute_job_card_repair_notes'] ?? '')), 0, 255, 'UTF-8'),
                    'nomor_sv' => mb_substr($ro['service_type'] ?? '', 0, 50, 'UTF-8'),
                    'id_customer' => $customerId,
                    'sup_invoice' => 0,
                    'pajak' => '0',
                    'kode_sup' => $supplierId,
                    'kode_servis' => 0,
                    'nomor_req' => '',
                    'harga_part' => 0,
                    'harga_oli' => 0,
                    'harga_lbr' => 0,
                    'harga_oth' => 0,
                    'harga_total' => 0,
                    'harga_pajak' => 0,
                    'harga_jual' => 0,
                    'harga_pajak_jual' => 0,
                    'state' => $ro['state'] ?? null,
                    'is_internal' => !empty($ro['is_internal_repair']) ? 1 : 0,
                ];

                $htransaksi = Htransaksi::updateOrCreate(['nomor_job' => $jobNo], $headerData);

                // Override state to cancel if:
                // 1. Vendor is OTHERS (O-10404) - workaround for cancelled jobs, OR
                // 2. Repair notes contain cancellation keywords (batal, cancel, etc.)
                $repairNotes = strip_tags($ro['compute_job_card_repair_notes'] ?? '');
                $hasCancelKeyword = preg_match('/\b(batal|cancel|dibatalkan|cancelled|void|pembatalan)\b/i', $repairNotes);
                if ($supplierId === 'O-10404' || $hasCancelKeyword) {
                    $htransaksi->update(['state' => 'cancel']);
                }

                // Sync Detail Lines (Draft from JO)
                Dtransaksi::where('nomor_invoice', $htransaksi->nomor_invoice)->delete();
                $totalJO = 0;
                $insertedLineNames = [];
                
                // Process Services (repair_service_ids) first as they have accurate tax subtotals
                $roServices = is_array($ro['repair_service_ids']) ? $ro['repair_service_ids'] : [];
                foreach ($roServices as $lid) {
                    $ld = $roServicesMap[$lid] ?? null;
                    if (!$ld) {
                        \Log::error("RO Service Missing in Map ID: {$lid}");
                        continue;
                    }

                    $qty = $ld['quantity'] ?? 1;
                    $subtotal = $ld['price_subtotal'];
                    $price_unit = $qty > 0 ? ($subtotal / $qty) : $subtotal;

                    $cleanName = mb_substr($this->sanitizeText($ld['name']), 0, 255, 'UTF-8');
                    $insertedLineNames[] = $cleanName;
                    \Log::error("Inserting RO Service: {$cleanName} (subtotal: {$subtotal})");

                    $totalJO += $subtotal;
                    Dtransaksi::create([
                        'nomor_invoice' => $htransaksi->nomor_invoice,
                        'deskripsi' => $cleanName,
                        'tanggal_part_keluar' => null,
                        'jumlah' => $qty,
                        'harga' => $price_unit,
                        'value' => $subtotal,
                        'discount' => 0,
                        'mnt_grp' => '',
                        'product' => is_array($ld['product_id'] ?? null) ? ($ld['product_id'][1] ?? '') : '',
                        'lbr_grp' => '',
                        'note' => ''
                    ]);
                }

                // Process Parts (order_line_ids) from roLinesMap
                $roLines = is_array($ro['order_line_ids']) ? $ro['order_line_ids'] : [];
                foreach ($roLines as $lid) {
                    $ld = $roLinesMap[$lid] ?? null;
                    if (!$ld) continue;

                    $cleanName = mb_substr($this->sanitizeText($ld['name']), 0, 255, 'UTF-8');
                    if (!empty($ro['is_internal_repair']) && !empty($ld['product_template_id']) && is_array($ld['product_template_id'])) {
                        $templateName = $ld['product_template_id'][1] ?? '';
                        if (preg_match('/^(\[[^\]]+\])/', $templateName, $matches)) {
                            $partCode = $matches[1];
                            if (strpos($cleanName, $partCode) !== 0) {
                                $cleanName = mb_substr($partCode . ' ' . $cleanName, 0, 255, 'UTF-8');
                            }
                        }
                    }
                    if (in_array($cleanName, $insertedLineNames)) continue;

                    $subtotal = $ld['price_subtotal'];
                    if ($subtotal == 0 && isset($ld['price_unit']) && $ld['price_unit'] > 0) {
                        $subtotal = $ld['price_unit'] * $ld['quantity'];
                    }

                    $tanggalKeluar = null;
                    if (!empty($ld['stock_move_id']) && is_array($ld['stock_move_id'])) {
                        $smId = $ld['stock_move_id'][0];
                        if (isset($stockMovesMap[$smId]) && !empty($stockMovesMap[$smId]['date'])) {
                            $tanggalKeluar = \Carbon\Carbon::parse($stockMovesMap[$smId]['date'], 'UTC')
                                ->setTimezone('Asia/Jakarta')
                                ->format('Y-m-d H:i:s');
                        }
                    }

                    $totalJO += $subtotal;
                    Dtransaksi::create([
                        'nomor_invoice' => $htransaksi->nomor_invoice,
                        'deskripsi' => $cleanName,
                        'tanggal_part_keluar' => $tanggalKeluar,
                        'jumlah' => $ld['quantity'],
                        'harga' => $ld['price_unit'],
                        'value' => $subtotal,
                        'discount' => 0,
                        'mnt_grp' => '',
                        'product' => is_array($ld['product_template_id'] ?? null) ? ($ld['product_template_id'][1] ?? '') : '',
                        'lbr_grp' => '',
                        'note' => ''
                    ]);
                }
                $roMoveId = is_array($ro['move_id']) ? $ro['move_id'][0] : $ro['move_id'];
                $roId = $ro['id'];
                $roTax = 0;
                $roHargaPart = $totalJO;
                $roHargaTotal = $totalJO;
                
                // Prefer vendor bill (most accurate, includes tax)
                // harga_total stores the UNTAXED amount; harga_pajak stores tax separately
                // so that grandTotal (harga_total + harga_pajak) = full total without double-counting
                if (isset($vendorBillsByRo[$roId])) {
                    $roTax = $vendorBillsByRo[$roId]['amount_tax'];
                    $roHargaPart = $vendorBillsByRo[$roId]['amount_untaxed'] ?: $totalJO;
                    $roHargaTotal = $roHargaPart; // untaxed only; tax is in harga_pajak
                } elseif ($roMoveId && isset($roMovesMap[$roMoveId])) {
                    $roTax = $roMovesMap[$roMoveId]['amount_tax'];
                    // roHargaTotal stays as $totalJO (already untaxed from JO lines)
                }

                $htransaksi->update([
                    'harga_part' => $roHargaPart,
                    'harga_total' => $roHargaTotal,
                    'harga_jual' => $roHargaTotal,
                    'harga_pajak' => $roTax,
                    'harga_pajak_jual' => $roTax,
                ]);
                $itemsSynced++;
                } catch (\Exception $roEx) {
                    Log::warning('Skipping RO due to error: ' . ($ro['name'] ?? 'unknown') . ' - ' . $roEx->getMessage());
                }
            }

            // Step B: Overwrite with Bills (More accurate financials)
            foreach ($moves as $move) {
                $rId = is_array($move['repair_id']) ? $move['repair_id'][0] : null;
                $jobNo = is_array($move['repair_id']) ? $move['repair_id'][1] : null;
                $invoiceNo = $move['name'];

                if (!$jobNo) continue;

                $htransaksi = Htransaksi::where('nomor_job', $jobNo)->first();
                if (!$htransaksi) continue;

                $calculatedTotal = $move['amount_untaxed'] ?? 0;
                $calculatedTax = $move['amount_tax'] ?? 0;

                // Sync Vendor/Supplier
                $supplierId = '0';
                if (!empty($move['partner_id']) && is_array($move['partner_id'])) {
                    $supplierId = 'O-' . $move['partner_id'][0];
                    \App\Models\Supplier::updateOrCreate(
                        ['kode_supplier' => $supplierId],
                        ['nama_supplier' => mb_substr($move['partner_id'][1], 0, 100, 'UTF-8')]
                    );
                }

                // Delete existing lines for both the old invoice number (job number) and the new bill number
                Dtransaksi::whereIn('nomor_invoice', [$htransaksi->nomor_invoice, $invoiceNo])->delete();
                
                if (isset($billLinesMap[$move['id']])) {
                    foreach ($billLinesMap[$move['id']] as $line) {
                        if (in_array($line['display_type'], ['payment_term', 'tax'])) continue;

                        $amt = $line['price_subtotal'];
                        $cleanName = mb_substr($this->sanitizeText($line['name']), 0, 255, 'UTF-8');
                        if ($htransaksi->is_internal && !empty($line['product_id']) && is_array($line['product_id'])) {
                            $productName = $line['product_id'][1] ?? '';
                            if (preg_match('/^(\[[^\]]+\])/', $productName, $matches)) {
                                $partCode = $matches[1];
                                if (strpos($cleanName, $partCode) !== 0) {
                                    $cleanName = mb_substr($partCode . ' ' . $cleanName, 0, 255, 'UTF-8');
                                }
                            }
                        }

                        Dtransaksi::create([
                            'nomor_invoice' => $invoiceNo,
                            'deskripsi' => $cleanName,
                            'tanggal_part_keluar' => null,
                            'jumlah' => $line['quantity'],
                            'harga' => $line['price_unit'] ?: ($line['quantity'] ? abs($amt/$line['quantity']) : 0),
                            'value' => $amt,
                            'discount' => 0,
                            'mnt_grp' => '',
                            'product' => is_array($line['product_id'] ?? null) ? ($line['product_id'][1] ?? '') : '',
                            'lbr_grp' => '',
                            'note' => $line['display_type'] ?? ''
                        ]);
                    }
                }

                $htransaksi->update([
                    'nomor_invoice' => $invoiceNo,
                    'kode_sup' => $supplierId,
                    'harga_part' => $calculatedTotal,
                    'harga_pajak' => $calculatedTax,
                    'harga_total' => $calculatedTotal, // untaxed only; grandTotal = harga_total + harga_pajak
                    'tanggal_invoice' => $move['invoice_date'] ?: $htransaksi->tanggal_job
                ]);
            }

            DB::commit();
            
            // Only update last_sync when completely finished
            if (!$hasMore && ($phase === 'move' || $phase === 'all')) {
                $this->setting->update(['last_sync' => now()]);
                
                // Sync ALL vehicles (stock.lot) from Odoo to fix purchase_date for
                // vehicles without any repair orders. Run in batches until complete.
                $vehicleOffset = 0;
                $vehicleLimit = 500;
                $vehicleTotal = 0;
                do {
                    $vehicleResult = $this->syncVehicles($sourceType, $vehicleOffset, $vehicleLimit);
                    if ($vehicleResult['success']) {
                        $vehicleTotal += $vehicleResult['items'] ?? 0;
                        $vehicleHasMore = $vehicleResult['hasMore'] ?? false;
                        $vehicleOffset = $vehicleResult['nextOffset'] ?? ($vehicleOffset + $vehicleLimit);
                    } else {
                        Log::warning('Vehicle sync batch failed: ' . ($vehicleResult['message'] ?? 'Unknown error'));
                        break;
                    }
                } while ($vehicleHasMore);
                
                if ($vehicleTotal > 0) {
                    Log::info("Vehicle sync completed: {$vehicleTotal} vehicles processed.");
                }

                // Automatically sync latest rental start dates and KM for all vehicles
                $rentalResult = $this->syncRentalStarts($sourceType);
                if ($rentalResult['success']) {
                    Log::info("Rental start sync completed: " . ($rentalResult['updated_vehicles'] ?? 0) . " vehicles updated.");
                }
            }

            $this->logHistory($sourceType, 'Success', $itemsSynced, "Batch processed: $itemsSynced items.");
            return [
                'success' => true, 
                'message' => "Synced $itemsSynced items successfully.", 
                'items' => $itemsSynced,
                'hasMore' => $hasMore,
                'nextOffset' => $nextOffset,
                'phase' => $phase
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Odoo Sync Error: ' . $e->getMessage());
            $this->logHistory($sourceType, 'Failed', 0, 'Exception: ' . $e->getMessage() . " line " . $e->getLine());
            return ['success' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }

    public function getSyncStats($targetJo = null)
    {
        if (!$this->setting) return ['success' => false];

        $authData = $this->odooCall('common', 'authenticate', [$this->db, $this->user, $this->apiKey, (object)[]]);
        if (!$authData['success']) return ['success' => false];
        $uid = $authData['result'];

        $lastSyncUTC = $this->setting->last_sync ? Carbon::parse($this->setting->last_sync)->setTimezone('UTC')->format('Y-m-d H:i:s') : null;

        $roDomain = [];
        if ($targetJo) {
            $roDomain[] = ['name', '=', $targetJo];
        } elseif ($lastSyncUTC) {
            $roDomain[] = ['write_date', '>=', $lastSyncUTC];
        } else {
            $roDomain[] = ['create_date', '>=', '2025-11-30 00:00:00'];
        }

        $billDomain = [['repair_id', '!=', false], ['state', '!=', 'cancel'], ['name', 'like', 'BILLS/']];
        if ($lastSyncUTC) {
            $billDomain[] = ['write_date', '>=', $lastSyncUTC];
        } else {
            $billDomain[] = ['create_date', '>=', '2025-11-30 00:00:00'];
        }

        $roCount = $this->odooCall('object', 'execute_kw', [$this->db, $uid, $this->apiKey, 'repair.order', 'search_count', [$roDomain]]);
        $billCount = $this->odooCall('object', 'execute_kw', [$this->db, $uid, $this->apiKey, 'account.move', 'search_count', [$billDomain]]);

        return [
            'success' => true,
            'ro_count' => $roCount['result'] ?? 0,
            'bill_count' => $billCount['result'] ?? 0,
            'total' => ($roCount['result'] ?? 0) + ($billCount['result'] ?? 0)
        ];
    }

    public function backfillIsInternal($startDate = '2025-11-30 00:00:00')
    {
        if (!$this->setting) {
            return ['success' => false, 'message' => 'Odoo settings not configured.'];
        }

        try {
            // 1. Authenticate with Odoo
            $authData = $this->odooCall('common', 'authenticate', [$this->db, $this->user, $this->apiKey, (object)[]]);
            
            if (!$authData['success'] || empty($authData['result'])) {
                $errorMsg = $authData['error'] ?? 'Authentication failed';
                return ['success' => false, 'message' => "Odoo Auth Failed: $errorMsg"];
            }

            $this->uid = $authData['result'];

            $offset = 0;
            $limit = 1000;
            $updatedCount = 0;

            do {
                $roDomain = [['create_date', '>=', $startDate]];
                
                $rosData = $this->odooCall('object', 'execute_kw', [
                    $this->db, $this->uid, $this->apiKey,
                    'repair.order', 'search_read',
                    [$roDomain],
                    [
                        'fields' => ['name', 'is_internal_repair'],
                        'limit' => $limit,
                        'offset' => $offset,
                        'order' => 'create_date asc'
                    ]
                ]);

                $ros = $rosData['result'] ?? [];
                if (empty($ros)) {
                    break;
                }

                foreach ($ros as $ro) {
                    $jobNo = $ro['name'];
                    $isInternal = !empty($ro['is_internal_repair']) ? 1 : 0;

                    $affected = DB::table('htransaksi')
                        ->where('nomor_job', $jobNo)
                        ->update(['is_internal' => $isInternal]);

                    if ($affected > 0) {
                        $updatedCount++;
                    }
                }

                $offset += $limit;
            } while (count($ros) === $limit);

            return ['success' => true, 'message' => "Backfill completed. Updated $updatedCount records."];

        } catch (\Exception $e) {
            Log::error('Odoo Backfill Error: ' . $e->getMessage());
            return ['success' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }

    public function syncVehicles($sourceType = 'Manual', $offset = 0, $limit = 500)
    {
        if (!$this->setting) {
            return ['success' => false, 'message' => 'Odoo settings not configured.'];
        }

        try {
            // 1. Authenticate with Odoo
            $authData = $this->odooCall('common', 'authenticate', [$this->db, $this->user, $this->apiKey, (object)[]]);
            
            if (!$authData['success'] || empty($authData['result'])) {
                $errorMsg = $authData['error'] ?? 'Authentication failed';
                $this->logHistory($sourceType, 'Failed', 0, "Auth Error: " . $errorMsg);
                return ['success' => false, 'message' => "Odoo Auth Failed: $errorMsg"];
            }

            $this->uid = $authData['result'];

            // 2. Fetch ALL stock.lot records with ref (chassis) and purchase_date
            $domain = [['ref', '!=', false], ['purchase_date', '!=', false]];
            
            $lotsData = $this->odooCall('object', 'execute_kw', [
                $this->db, $this->uid, $this->apiKey,
                'stock.lot', 'search_read',
                [$domain],
                [
                    'fields' => ['name', 'ref', 'color_id', 'purchase_date', 'vehicle_year', 'engine_number', 'product_id'],
                    'limit' => $limit,
                    'offset' => $offset,
                    'order' => 'create_date asc'
                ]
            ]);

            $lots = $lotsData['result'] ?? [];
            $hasMore = count($lots) === $limit;
            $nextOffset = $hasMore ? $offset + $limit : 0;

            if (empty($lots)) {
                return [
                    'success' => true,
                    'message' => 'No vehicle records found.',
                    'items' => 0,
                    'hasMore' => false,
                    'nextOffset' => 0
                ];
            }

            $itemsSynced = 0;

            foreach ($lots as $lot) {
                try {
                    $chassisNo = $lot['ref'] ?? null;
                    $plateNo = $lot['name'] ?? '';
                    $purchaseDate = $lot['purchase_date'] ?? null;
                    $vehicleYear = $lot['vehicle_year'] ?? null;
                    $engineNumber = $lot['engine_number'] ?? null;
                    $warna = '';

                    if (!empty($lot['color_id']) && is_array($lot['color_id'])) {
                        $warna = $lot['color_id'][1];
                    }

                    // Extract vehicle model from product_id (many2one [id, display_name])
                    $vehicleModel = '';
                    if (!empty($lot['product_id']) && is_array($lot['product_id'])) {
                        $vehicleModel = mb_substr($lot['product_id'][1], 0, 100, 'UTF-8');
                    }

                    if (!$chassisNo) continue;

                    // Format nopol: "B -1382-HZK"
                    $nopol = '';
                    if ($plateNo) {
                        // Try to format plate number with dashes
                        if (preg_match('/^([A-Z]+)\s*(\d+)\s*([A-Z]+)$/', $plateNo, $m)) {
                            $nopol = $m[1] . ' -' . $m[2] . '-' . $m[3];
                        } else {
                            $nopol = $plateNo;
                        }
                    }

                    $existingMobil = Mobil::where('nomor_chassis', $chassisNo)->first();
                    if ($existingMobil) {
                        $updateData = [
                            'nomor_polisi' => mb_substr($plateNo, 0, 25, 'UTF-8'),
                            'nopol' => mb_substr($nopol ?: $plateNo, 0, 25, 'UTF-8'),
                            'warna' => $warna ?: '',
                            'tahun_pembuatan' => $vehicleYear ?: now()->format('Y'),
                            'nomor_mesin' => $engineNumber ?: '',
                        ];
                        // Only overwrite model if we have a value from Odoo
                        if ($vehicleModel) {
                            $updateData['model'] = $vehicleModel;
                        }
                        if ($purchaseDate) {
                            $updateData['tanggal_pembelian'] = $purchaseDate;
                        }
                        $existingMobil->update($updateData);
                    } else {
                        Mobil::create([
                            'nomor_chassis' => $chassisNo,
                            'nomor_polisi' => mb_substr($plateNo, 0, 25, 'UTF-8'),
                            'nopol' => mb_substr($nopol ?: $plateNo, 0, 25, 'UTF-8'),
                            'warna' => $warna ?: '',
                            'tanggal_pembelian' => $purchaseDate ?: now()->format('Y-m-d'),
                            'tahun_pembuatan' => $vehicleYear ?: now()->format('Y'),
                            'nomor_mesin' => $engineNumber ?: '',
                            'nomor_kk' => '',
                            'model' => $vehicleModel,
                            'kode_sup' => '',
                        ]);
                    }

                    $itemsSynced++;
                } catch (\Exception $lotEx) {
                    \Log::warning('SyncVehicles: Skipping lot ' . ($lot['id'] ?? 'unknown') . ' - ' . $lotEx->getMessage());
                }
            }

            $this->logHistory($sourceType, 'Success', $itemsSynced, "Vehicles batch processed: $itemsSynced items (offset: $offset).");
            return [
                'success' => true,
                'message' => "Synced $itemsSynced vehicles.",
                'items' => $itemsSynced,
                'hasMore' => $hasMore,
                'nextOffset' => $nextOffset
            ];

        } catch (\Exception $e) {
            \Log::error('Odoo SyncVehicles Error: ' . $e->getMessage());
            $this->logHistory($sourceType, 'Failed', 0, 'Exception: ' . $e->getMessage() . " line " . $e->getLine());
            return ['success' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }

    private function odooCall($service, $method, $args)
    {
        try {
            $response = Http::when(app()->environment('local'), function ($request) {
                return $request->withoutVerifying();
            })->post("{$this->url}/jsonrpc", [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => $service,
                    'method' => $method,
                    'args' => $args,
                ],
                'id' => uniqid(),
            ]);

            $json = $response->json();
            
            if (isset($json['error'])) {
                return ['success' => false, 'error' => $json['error']['data']['message'] ?? $json['error']['message']];
            }

            return ['success' => true, 'result' => $json['result'] ?? null];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync latest rental starts (stock.move.line with /OUT/ reference) to mobil table
     */
    public function syncRentalStarts($sourceType = 'Manual'): array
    {
        if (!$this->setting) {
            return ['success' => false, 'message' => 'Odoo settings not configured.'];
        }

        try {
            $authData = $this->odooCall('common', 'authenticate', [$this->db, $this->user, $this->apiKey, (object)[]]);
            if (!$authData['success'] || empty($authData['result'])) {
                $errorMsg = $authData['error'] ?? 'Authentication failed';
                $this->logHistory($sourceType, 'Failed', 0, "Auth Error: " . $errorMsg);
                return ['success' => false, 'message' => "Odoo Auth Failed: $errorMsg"];
            }

            $this->uid = $authData['result'];

            $offset = 0;
            $limit = 2000;
            $latestRentals = [];

            do {
                $batch = $this->odooCall('object', 'execute_kw', [
                    $this->db, $this->uid, $this->apiKey,
                    'stock.move.line', 'search_read',
                    [[
                        ['state', '=', 'done'],
                        ['reference', 'like', '/OUT/'],
                        ['lot_id', '!=', false]
                    ]],
                    [
                        'fields' => ['id', 'reference', 'lot_id', 'date', 'latest_km'],
                        'order' => 'date desc',
                        'offset' => $offset,
                        'limit' => $limit
                    ]
                ]);

                if (!$batch['success']) {
                    return ['success' => false, 'message' => 'Failed to fetch stock move lines: ' . ($batch['error'] ?? 'Unknown error')];
                }

                $records = $batch['result'] ?? [];

                foreach ($records as $r) {
                    $lotName = is_array($r['lot_id']) ? $r['lot_id'][1] : null;
                    if ($lotName && !isset($latestRentals[$lotName])) {
                        // Date is UTC in Odoo, convert to Asia/Jakarta
                        $dateJakarta = null;
                        if (!empty($r['date'])) {
                            try {
                                $dateJakarta = Carbon::createFromFormat('Y-m-d H:i:s', $r['date'], 'UTC')
                                    ->setTimezone('Asia/Jakarta')
                                    ->format('Y-m-d H:i:s');
                            } catch (\Exception $e) {
                                $dateJakarta = $r['date'];
                            }
                        }

                        $latestRentals[$lotName] = [
                            'ref' => $r['reference'] ?? null,
                            'date' => $dateJakarta,
                            'km' => $r['latest_km'] ?? null,
                        ];
                    }
                }

                if (count($records) < $limit) {
                    break;
                }

                $offset += $limit;
            } while (true);

            // Batch update Mobil records
            $updatedCount = 0;
            DB::beginTransaction();
            foreach ($latestRentals as $plate => $data) {
                $cleanPlate = preg_replace('/\s+/', '', $plate);
                $mobils = Mobil::where('nomor_polisi', $plate)
                    ->orWhere('nopol', $plate)
                    ->orWhereRaw("REPLACE(nomor_polisi, ' ', '') = ?", [$cleanPlate])
                    ->get();

                foreach ($mobils as $m) {
                    $m->update([
                        'tanggal_start_sewa' => $data['date'],
                        'km_start_sewa' => $data['km'],
                        'rental_reference' => $data['ref'],
                    ]);
                    $updatedCount++;
                }
            }
            DB::commit();

            $this->logHistory($sourceType, 'Success', $updatedCount, "Synced rental starts for {$updatedCount} vehicle records (" . count($latestRentals) . " unique plates).");

            return [
                'success' => true,
                'message' => "Successfully synced rental starts for {$updatedCount} vehicles.",
                'total_unique_plates' => count($latestRentals),
                'updated_vehicles' => $updatedCount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Rental Start Sync Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }


    private function logHistory($source, $status, $items, $details)
    {
        ImportHistory::create([
            'source' => "Odoo ($source)",
            'status' => $status,
            'items' => $items,
            'details' => $details
        ]);
    }

    private function sanitizeText(string $text): string
    {
        // Remove invalid UTF-8 sequences
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        // Remove non-printable and invalid XML/JSON characters
        $clean = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $clean ?? '');
        return $clean ?? '';
    }
}
