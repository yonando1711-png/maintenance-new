@extends('layouts.app')

@section('content')
<div x-data="batchExportPage()" x-init="init()" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden theme-transition mb-6">
    <!-- Header -->
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-2xl border border-indigo-100 dark:border-indigo-900/50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-3">
                        Batch Vehicle Export
                        <span x-show="selectedPlates.length > 0" x-cloak class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            <span x-text="selectedPlates.length"></span> Kendaraan Terpilih
                        </span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Export data riwayat maintenance untuk beberapa kendaraan acak sekaligus (Excel & PDF)</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('maintenance.vehicle.transactions') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Ke Maintenance History
            </a>
        </div>
    </div>

    <div class="p-6">
        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-6 mb-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Select2 Multi-Vehicle Search (Recommendation Bar) -->
                <div class="md:col-span-2 lg:col-span-2">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                        <label for="batch_nomor_polisi_select" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Pilih Nomor Polisi (Ketik untuk rekomendasi)
                        </label>
                        <div class="flex items-center gap-2">
                            <!-- Hidden File Input for Excel Import -->
                            <input type="file" id="batch_excel_file_input" accept=".xlsx, .xls, .csv" @change="handleExcelUpload($event)" class="hidden">
                            <button type="button" @click="$('#batch_excel_file_input').click()" 
                                    class="text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 font-semibold text-xs flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 transition-colors shadow-xs" 
                                    title="Upload Excel/CSV (otomatis mendeteksi kolom Nopol)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <span>Import Excel</span>
                            </button>
                            <button type="button" @click="showBulkInput = !showBulkInput" class="text-indigo-600 dark:text-indigo-400 hover:underline normal-case font-medium text-xs flex items-center gap-1">
                                <span x-text="showBulkInput ? 'Tutup Tempel' : '+ Tempel Nopol'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="w-full">
                        <select id="batch_nomor_polisi_select" class="w-full select2-batch-nomor-polisi" multiple="multiple" style="width: 100%;">
                        </select>
                    </div>
                    <!-- Notification Banner for Excel Import / Bulk Paste -->
                    <div x-show="uploadStatusMessage" x-cloak class="mt-2.5 p-3 text-xs rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span x-text="uploadStatusMessage"></span>
                        </div>
                        <button type="button" @click="uploadStatusMessage = ''" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-100 font-bold ml-2">&times;</button>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                        Ketik nopol (contoh: <span class="font-mono text-indigo-500">B 1382</span>, <span class="font-mono text-indigo-500">L 1022</span>), klik saran yang muncul untuk menambahkannya.
                    </p>

                    <!-- Collapsible Quick Paste Area -->
                    <div x-show="showBulkInput" x-cloak x-transition class="mt-3 p-4 bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">
                            Tempel beberapa nopol sekaligus (pisahkan dengan koma, spasi, atau baris baru):
                        </label>
                        <textarea x-model="bulkInputText" rows="2" class="w-full text-xs font-mono p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500" placeholder="B1382HZK, L1022CAK, B9145BXD"></textarea>
                        <div class="flex justify-end gap-2 mt-2">
                            <button type="button" @click="applyBulkInput()" class="px-3 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors shadow-sm">
                                Tambahkan ke Pilihan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filters -->
                <div>
                    <label for="batch_start_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Start Date (Opsional)</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="text" x-model="startDateDisplay" id="batch_start_date" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm" autocomplete="off" placeholder="DD-MM-YYYY">
                    </div>
                </div>

                <div>
                    <label for="batch_end_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">End Date (Opsional)</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="text" x-model="endDateDisplay" id="batch_end_date" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm" autocomplete="off" placeholder="DD-MM-YYYY">
                    </div>
                </div>
            </div>

            <!-- Options & Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-400">
                        <input type="checkbox" x-model="withTanggalUpdate" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        <span>Sertakan Tanggal Update Spareparts (khusus Excel)</span>
                    </label>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button type="button" @click="clearAll()" 
                            :disabled="selectedPlates.length === 0 && !startDate && !endDate"
                            class="h-[40px] px-5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-xl font-bold uppercase tracking-wider text-xs transition-colors flex items-center gap-2 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset
                    </button>
                    <button type="button" @click="fetchPreview()" 
                            :disabled="selectedPlates.length === 0 || loading"
                            class="h-[40px] px-6 bg-indigo-600 hover:bg-indigo-700 text-white border border-indigo-600 rounded-xl font-bold uppercase tracking-wider text-xs transition-all flex items-center gap-2 shadow-md hover:shadow-indigo-200 dark:hover:shadow-none disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <svg x-show="loading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="loading ? 'Memuat...' : 'Cek Data Kendaraan'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI / Metric Cards (Visible when preview loaded) -->
        <div x-show="previewData && previewData.totalVehicles > 0" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Kendaraan</div>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 flex items-baseline gap-1">
                    <span x-text="previewData?.totalVehicles || 0"></span>
                    <span class="text-xs font-normal text-slate-400">unit</span>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Transaksi Job</div>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 flex items-baseline gap-1">
                    <span x-text="previewData?.totalTransactions || 0"></span>
                    <span class="text-xs font-normal text-slate-400">record</span>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Estimasi Total Biaya</div>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400" x-text="previewData?.grandTotalFormatted || 'Rp 0'"></div>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Estimasi PPN (Pajak)</div>
                <div class="text-2xl font-black text-slate-500 dark:text-slate-400" x-text="previewData?.totalTaxFormatted || 'Rp 0'"></div>
            </div>
        </div>

        <!-- Export Action Bar -->
        <div x-show="selectedPlates.length > 0" x-cloak class="bg-gradient-to-r from-indigo-50/80 via-white to-indigo-50/50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800/80 border border-indigo-200 dark:border-slate-700 rounded-2xl p-4 md:p-5 mb-6 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 shadow-sm">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Pilihan Export Data (<span class="text-indigo-600 dark:text-indigo-400" x-text="selectedPlates.length"></span> Kendaraan Terpilih)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Pilih format <strong>Summary (1 baris per kendaraan)</strong> atau <strong>Detail (seluruh riwayat job & parts)</strong>.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                <!-- Group 1: Summary Export (Per Vehicle / 1 Baris Per Kendaraan) -->
                <div class="flex items-center gap-2 bg-indigo-50/80 dark:bg-slate-900/80 p-1.5 rounded-xl border border-indigo-200 dark:border-slate-700 shadow-xs">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 px-2 hidden sm:inline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Summary (Per Mobil):
                    </span>
                    <!-- Summary XLSX -->
                    <button type="button" @click="exportSummaryXLSX()" :disabled="exporting"
                            class="btn-batch-export btn-batch-summary-xlsx" title="Export Ringkasan: Setiap kendaraan mendapat 1 baris berisi Nopol, Chassis, Jml Job, Subtotal Biaya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Summary XLSX
                    </button>
                    <!-- Summary PDF -->
                    <button type="button" @click="exportSummaryPDF()" :disabled="exporting"
                            class="btn-batch-export btn-batch-summary-pdf" title="Export Ringkasan ke PDF: Tabel 1 baris per kendaraan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Summary PDF
                    </button>
                </div>

                <!-- Group 2: Detailed Export (All Transactions & Parts) -->
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900/80 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-2 hidden sm:inline">
                        Detail (Rinci):
                    </span>
                    <!-- Detail XLSX -->
                    <button type="button" @click="exportXLSX()" :disabled="exporting"
                            class="btn-batch-export btn-batch-xlsx" title="Download Excel Rinci (.xlsx) dengan seluruh baris transaksi dan parts">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Detail XLSX
                    </button>
                    <!-- Detail CSV -->
                    <button type="button" @click="exportCSV()" :disabled="exporting"
                            class="btn-batch-export btn-batch-csv" title="Download CSV Rinci (.csv)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Detail CSV
                    </button>
                    <!-- Detail PDF (Lengkap) -->
                    <button type="button" @click="exportPDF(true)" :disabled="exporting"
                            class="btn-batch-export btn-batch-pdf-full" title="Download PDF Rinci Lengkap dengan Harga">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Detail PDF
                    </button>
                    <!-- Detail PDF (Tanpa Harga) -->
                    <button type="button" @click="exportPDF(false)" :disabled="exporting"
                            class="btn-batch-export btn-batch-pdf-simple"
                            title="PDF tanpa kolom harga (untuk driver / workshop)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Detail PDF (No Rp)
                    </button>
                </div>
            </div>
        </div>

        <!-- Selected Vehicles Detail Table -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-4 md:px-6 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-700 flex flex-wrap justify-between items-center gap-2">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                        Daftar Kendaraan Terpilih (<span x-text="selectedPlates.length"></span>)
                    </h3>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 hidden sm:inline">- Subtotal per kendaraan</span>
                </div>
                <div class="flex items-center gap-2" x-show="selectedPlates.length > 0">
                    <button type="button" @click="exportSummaryXLSX()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1" title="Export tabel ini ke Excel (1 baris per mobil)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export Summary XLSX
                    </button>
                    <span class="text-slate-300 dark:text-slate-600">|</span>
                    <button type="button" @click="exportSummaryPDF()" class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1" title="Export tabel ini ke PDF (1 baris per mobil)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Export Summary PDF
                    </button>
                    <span class="text-slate-300 dark:text-slate-600">|</span>
                    <button type="button" @click="clearAll()" class="text-xs text-rose-500 hover:text-rose-600 hover:underline font-medium">
                        Hapus Semua
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="selectedPlates.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h4 class="text-base font-bold text-slate-700 dark:text-slate-200 mb-1">Belum Ada Kendaraan yang Dipilih</h4>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-md mx-auto">
                    Gunakan kolom pencarian di atas untuk memilih nomor polisi kendaraan secara acak, atau klik "Tempel Banyak Nopol" untuk menempel daftar dari Excel.
                </p>
            </div>

            <!-- Vehicles Table (when selected) -->
            <div x-show="selectedPlates.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                            <th class="py-3 px-4 font-bold">No</th>
                            <th class="py-3 px-4 font-bold">Nomor Polisi</th>
                            <th class="py-3 px-4 font-bold">Nomor Chassis</th>
                            <th class="py-3 px-4 font-bold">Model</th>
                            <th class="py-3 px-4 font-bold">Tahun / Warna</th>
                            <th class="py-3 px-4 font-bold">Kode Supplier</th>
                            <th class="py-3 px-4 font-bold text-center">Jumlah Job</th>
                            <th class="py-3 px-4 font-bold text-right">Subtotal Biaya</th>
                            <th class="py-3 px-4 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        <template x-for="(nopol, idx) in selectedPlates" :key="nopol">
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-slate-400" x-text="idx + 1"></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black font-mono bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800" x-text="nopol"></span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300" x-text="getVehicleInfo(nopol)?.nomor_chassis || '-'"></td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300" x-text="getVehicleInfo(nopol)?.model || '-'"></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                    <span x-text="getVehicleInfo(nopol)?.tahun_pembuatan || '-'"></span> / 
                                    <span x-text="getVehicleInfo(nopol)?.warna || '-'"></span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400" x-text="getVehicleInfo(nopol)?.kode_sup || '-'"></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full font-bold text-[11px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300"
                                          x-text="getVehicleInfo(nopol)?.transaction_count !== undefined ? getVehicleInfo(nopol).transaction_count : '-'">
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-800 dark:text-slate-200" x-text="getVehicleInfo(nopol)?.total_cost_formatted || '-'"></td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" @click="removePlate(nopol)" class="p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-500 hover:text-rose-600 transition-colors" title="Hapus dari daftar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- DateRangePicker CSS & JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- SheetJS (Excel Importer) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- pdfMake -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Batch Export Buttons */
        .btn-batch-export {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff !important;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.15);
        }
        .btn-batch-export:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .btn-batch-export:active:not(:disabled) {
            transform: translateY(0);
        }
        .btn-batch-export:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 0. Summary Export Buttons */
        .btn-batch-summary-xlsx {
            background-color: #4f46e5 !important;
            border: 1px solid #4338ca !important;
        }
        .btn-batch-summary-xlsx:hover:not(:disabled) {
            background-color: #4338ca !important;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
        }

        .btn-batch-summary-pdf {
            background-color: #7c3aed !important;
            border: 1px solid #6d28d9 !important;
        }
        .btn-batch-summary-pdf:hover:not(:disabled) {
            background-color: #6d28d9 !important;
            box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.4);
        }

        /* 1. Export XLSX (Green) */
        .btn-batch-xlsx {
            background-color: #059669 !important;
            border: 1px solid #047857 !important;
        }
        .btn-batch-xlsx:hover:not(:disabled) {
            background-color: #047857 !important;
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.4);
        }

        /* 2. Export CSV (Teal / Cyan) */
        .btn-batch-csv {
            background-color: #0d9488 !important;
            border: 1px solid #0f766e !important;
        }
        .btn-batch-csv:hover:not(:disabled) {
            background-color: #0f766e !important;
            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.4);
        }

        /* 3. Export PDF Lengkap (Rose Red) */
        .btn-batch-pdf-full {
            background-color: #e11d48 !important;
            border: 1px solid #be123c !important;
        }
        .btn-batch-pdf-full:hover:not(:disabled) {
            background-color: #be123c !important;
            box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.4);
        }

        /* 4. Export PDF Tanpa Harga (Solid Slate Gray with clear border) */
        .btn-batch-pdf-simple {
            background-color: #334155 !important;
            border: 1px solid #475569 !important;
        }
        .btn-batch-pdf-simple:hover:not(:disabled) {
            background-color: #1e293b !important;
            border-color: #64748b !important;
            box-shadow: 0 4px 6px -1px rgba(51, 65, 85, 0.4);
        }

        /* Select2 Full Width Container */
        .select2-container {
            width: 100% !important;
        }

        /* Custom Select2 Multi-Select Styling (Indigo Theme) */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            min-height: 2.5rem !important;
            border-radius: 0.75rem;
            padding: 0.2rem 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.25rem;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 1px #6366f1;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
            color: #3730a3;
            font-weight: 700;
            font-family: monospace;
            font-size: 0.75rem;
            border-radius: 0.5rem;
            padding: 0.2rem 0.5rem;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #4f46e5;
            font-weight: bold;
            margin-right: 0.25rem;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc2626;
        }
        .select2-container--default .select2-search--inline .select2-search__field {
            font-size: 0.875rem;
            margin-top: 0;
            color: #334155;
            min-width: 150px;
        }

        /* Dark Mode Overrides for Select2 */
        .dark .select2-container--default .select2-selection--multiple {
            border-color: #475569;
            background-color: #334155;
        }
        .dark .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: rgba(67, 56, 202, 0.45);
            border-color: rgba(99, 102, 241, 0.6);
            color: #c7d2fe;
        }
        .dark .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #a5b4fc;
        }
        .dark .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #f87171;
        }
        .dark .select2-container--default .select2-search--inline .select2-search__field {
            color: #e2e8f0;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-top-width: 0;
            background-color: #ffffff;
            font-size: 0.875rem;
            overflow: hidden;
            z-index: 9999;
        }
        .dark .select2-dropdown {
            border-color: #475569;
            background-color: #334155;
        }
        .select2-results__option {
            color: #334155;
            padding: 0.5rem 1rem;
        }
        .dark .select2-results__option {
            color: #cbd5e1;
        }
        .dark .select2-results__option:hover {
            background-color: rgba(49, 46, 129, 0.4);
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }
        .dark .select2-container--default .select2-results__option--selected {
            background-color: #475569;
        }

        /* DateRangePicker Dark Mode overrides */
        .dark .daterangepicker {
            background-color: #1e293b;
            border-color: #475569;
            color: #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .dark .daterangepicker .calendar-table {
            background-color: #1e293b;
            border-color: #1e293b;
        }
        .dark .daterangepicker td.off {
            background-color: #1e293b;
            color: #64748b;
        }
        .dark .daterangepicker td.available:hover {
            background-color: #334155;
        }
    </style>

    <script>
    function batchExportPage() {
        return {
            selectedPlates: [],
            startDate: '',
            endDate: '',
            startDateDisplay: '',
            endDateDisplay: '',
            withTanggalUpdate: false,
            showBulkInput: false,
            bulkInputText: '',
            uploadStatusMessage: '',
            loading: false,
            exporting: false,
            previewData: null,

            init() {
                let self = this;

                $(document).ready(function() {
                    // Initialize Select2 Multiple with AJAX suggestion
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#batch_nomor_polisi_select').select2({
                            width: '100%',
                            placeholder: 'Ketik nopol untuk cari rekomendasi (contoh: B 1382)...',
                            allowClear: true,
                            multiple: true,
                            ajax: {
                                url: "{{ route('maintenance.nomor_polisi.search') }}",
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return { q: params.term };
                                },
                                processResults: function(data) {
                                    return { results: data };
                                },
                                cache: true
                            }
                        }).on('change', function() {
                            let vals = $(this).val() || [];
                            self.selectedPlates = vals;
                            if (vals.length > 0) {
                                self.fetchPreview();
                            } else {
                                self.previewData = null;
                            }
                        });
                    }

                    // Initialize Datepickers
                    if (typeof $.fn.daterangepicker !== 'undefined') {
                        $('#batch_start_date').daterangepicker({
                            singleDatePicker: true,
                            autoUpdateInput: false,
                            showDropdowns: true,
                            locale: { cancelLabel: 'Clear', format: 'DD-MM-YYYY' }
                        }, function(start) {
                            self.startDate = start.format('YYYY-MM-DD');
                            self.startDateDisplay = start.format('DD-MM-YYYY');
                            if (self.selectedPlates.length > 0) self.fetchPreview();
                        });

                        $('#batch_end_date').daterangepicker({
                            singleDatePicker: true,
                            autoUpdateInput: false,
                            showDropdowns: true,
                            locale: { cancelLabel: 'Clear', format: 'DD-MM-YYYY' }
                        }, function(start) {
                            self.endDate = start.format('YYYY-MM-DD');
                            self.endDateDisplay = start.format('DD-MM-YYYY');
                            if (self.selectedPlates.length > 0) self.fetchPreview();
                        });

                        $('#batch_start_date, #batch_end_date').on('apply.daterangepicker', function(ev, picker) {
                            $(this).val(picker.startDate.format('DD-MM-YYYY'));
                            if (ev.target.id === 'batch_start_date') {
                                self.startDate = picker.startDate.format('YYYY-MM-DD');
                                self.startDateDisplay = picker.startDate.format('DD-MM-YYYY');
                            } else {
                                self.endDate = picker.startDate.format('YYYY-MM-DD');
                                self.endDateDisplay = picker.startDate.format('DD-MM-YYYY');
                            }
                            if (self.selectedPlates.length > 0) self.fetchPreview();
                        });

                        $('#batch_start_date, #batch_end_date').on('cancel.daterangepicker', function(ev) {
                            $(this).val('');
                            if (ev.target.id === 'batch_start_date') {
                                self.startDate = '';
                                self.startDateDisplay = '';
                            } else {
                                self.endDate = '';
                                self.endDateDisplay = '';
                            }
                            if (self.selectedPlates.length > 0) self.fetchPreview();
                        });
                    }
                });
            },

            handleExcelUpload(event) {
                let file = event.target.files[0];
                if (!file) return;

                let self = this;
                let reader = new FileReader();

                reader.onload = function(e) {
                    try {
                        let data = new Uint8Array(e.target.result);
                        let workbook = XLSX.read(data, { type: 'array' });
                        
                        if (!workbook.SheetNames || workbook.SheetNames.length === 0) {
                            alert('File Excel kosong atau tidak terbaca.');
                            return;
                        }

                        let firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        let rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });

                        if (!rows || rows.length === 0) {
                            alert('Tidak ada data yang ditemukan di sheet pertama.');
                            return;
                        }

                        // Header keywords to detect Nopol column
                        let headerKeywords = ['nopol', 'no_polisi', 'nomor_polisi', 'no polisi', 'nomor polisi', 'plat', 'plate', 'no_plat', 'no plat', 'police', 'kendaraan', 'vehicle'];
                        let ignoreKeywords = ['NOPOL', 'NO POLISI', 'NOMOR POLISI', 'NO_POLISI', 'NOMOR_POLISI', 'PLAT', 'PLATE', 'NO', 'NO.', 'KENDARAAN', 'VEHICLE'];

                        let detectedColIdx = -1;
                        let startRowIdx = 1;

                        // Scan first 5 rows for header keyword
                        for (let r = 0; r < Math.min(rows.length, 5); r++) {
                            let row = rows[r];
                            for (let c = 0; c < row.length; c++) {
                                let cellVal = String(row[c] || '').trim().toLowerCase();
                                if (headerKeywords.some(k => cellVal === k || cellVal.includes(k))) {
                                    detectedColIdx = c;
                                    startRowIdx = r + 1;
                                    break;
                                }
                            }
                            if (detectedColIdx !== -1) break;
                        }

                        // Fallback: If no header keyword was found, default to Column A (index 0)
                        if (detectedColIdx === -1) {
                            detectedColIdx = 0;
                            let firstCell = String(rows[0][0] || '').trim().toUpperCase();
                            if (ignoreKeywords.includes(firstCell)) {
                                startRowIdx = 1;
                            } else {
                                startRowIdx = 0;
                            }
                        }

                        // Extract plates
                        let extracted = [];
                        for (let r = startRowIdx; r < rows.length; r++) {
                            let rawVal = String(rows[r][detectedColIdx] || '').trim().toUpperCase();
                            let cleanVal = rawVal.replace(/\s+/g, '');
                            if (rawVal && !ignoreKeywords.includes(rawVal) && !ignoreKeywords.includes(cleanVal) && rawVal.length >= 3) {
                                extracted.push(rawVal);
                            }
                        }

                        if (extracted.length === 0) {
                            alert('Tidak ditemukan nomor polisi pada kolom yang terdeteksi.');
                            return;
                        }

                        // Merge into selectedPlates
                        let current = new Set(self.selectedPlates);
                        extracted.forEach(p => current.add(p));
                        let merged = Array.from(current);
                        self.selectedPlates = merged;

                        // Sync with Select2
                        let $select = $('#batch_nomor_polisi_select');
                        merged.forEach(val => {
                            if ($select.find("option[value='" + val + "']").length === 0) {
                                let newOption = new Option(val, val, true, true);
                                $select.append(newOption);
                            }
                        });
                        $select.val(merged).trigger('change');

                        self.uploadStatusMessage = 'Berhasil mendeteksi ' + extracted.length + ' nomor polisi dari file (' + file.name + ')!';
                        self.fetchPreview();

                        // Reset input
                        event.target.value = '';
                    } catch (err) {
                        console.error('Excel parse error:', err);
                        alert('Gagal membaca file Excel: ' + err.message);
                    }
                };

                reader.readAsArrayBuffer(file);
            },

            applyBulkInput() {
                if (!this.bulkInputText.trim()) return;
                let ignoreKeywords = ['NOPOL', 'NO POLISI', 'NOMOR POLISI', 'NO_POLISI', 'NOMOR_POLISI', 'PLAT', 'PLATE', 'NO', 'NO.', 'KENDARAAN', 'VEHICLE'];
                let items = this.bulkInputText.split(/[\r\n,;]+/)
                    .map(s => s.trim().toUpperCase())
                    .filter(s => s && !ignoreKeywords.includes(s) && !ignoreKeywords.includes(s.replace(/\s+/g, '')) && s.length >= 3);

                if (items.length === 0) {
                    alert('Tidak ada nomor polisi yang valid ditemukan.');
                    return;
                }

                let current = new Set(this.selectedPlates);
                items.forEach(item => current.add(item));

                let merged = Array.from(current);
                this.selectedPlates = merged;

                // Update Select2 options
                let $select = $('#batch_nomor_polisi_select');
                merged.forEach(val => {
                    if ($select.find("option[value='" + val + "']").length === 0) {
                        let newOption = new Option(val, val, true, true);
                        $select.append(newOption);
                    }
                });
                $select.val(merged).trigger('change');
                this.uploadStatusMessage = 'Berhasil menambahkan ' + items.length + ' nomor polisi dari teks!';
                this.bulkInputText = '';
                this.showBulkInput = false;
                this.fetchPreview();
            },

            removePlate(plate) {
                let idx = this.selectedPlates.indexOf(plate);
                if (idx > -1) {
                    this.selectedPlates.splice(idx, 1);
                    $('#batch_nomor_polisi_select').val(this.selectedPlates).trigger('change');
                }
            },

            clearAll() {
                this.selectedPlates = [];
                this.startDate = '';
                this.endDate = '';
                this.startDateDisplay = '';
                this.endDateDisplay = '';
                this.previewData = null;
                this.uploadStatusMessage = '';
                $('#batch_nomor_polisi_select').val(null).trigger('change');
                $('#batch_start_date, #batch_end_date').val('');
            },

            getVehicleInfo(nopol) {
                if (!this.previewData || !this.previewData.vehicles) return null;
                return this.previewData.vehicles.find(v => v.nomor_polisi === nopol);
            },

            fetchPreview() {
                if (this.selectedPlates.length === 0) return;
                let self = this;
                this.loading = true;

                $.ajax({
                    url: "{{ route('maintenance.batch.export.preview') }}",
                    data: {
                        nomor_polisi: self.selectedPlates.join(','),
                        start_date: self.startDate,
                        end_date: self.endDate
                    },
                    success: function(res) {
                        self.previewData = res;
                    },
                    error: function(err) {
                        console.error('Failed to fetch preview', err);
                    },
                    complete: function() {
                        self.loading = false;
                    }
                });
            },

            exportSummaryXLSX() {
                this.triggerDownload('xlsx', 'summary');
            },

            exportSummaryPDF() {
                if (this.selectedPlates.length === 0) return;
                let self = this;
                this.exporting = true;

                $.ajax({
                    url: "{{ route('maintenance.batch.export.download') }}",
                    data: {
                        nomor_polisi: self.selectedPlates.join(','),
                        start_date: self.startDate,
                        end_date: self.endDate,
                        type: 'summary',
                        format: 'json'
                    },
                    success: function(res) {
                        self.generateSummaryPDFReport(res);
                    },
                    error: function(err) {
                        alert('Gagal mengambil data untuk Ringkasan PDF. Silakan coba lagi.');
                        console.error(err);
                    },
                    complete: function() {
                        self.exporting = false;
                    }
                });
            },

            exportXLSX() {
                this.triggerDownload('xlsx', 'detail');
            },

            exportCSV() {
                this.triggerDownload('csv', 'detail');
            },

            triggerDownload(format, type = 'detail') {
                if (this.selectedPlates.length === 0) return;
                let form = document.createElement('form');
                form.method = 'GET';
                form.action = "{{ route('maintenance.batch.export.download') }}";
                form.style.display = 'none';

                let inputPlates = document.createElement('input');
                inputPlates.type = 'hidden';
                inputPlates.name = 'nomor_polisi';
                inputPlates.value = this.selectedPlates.join(',');
                form.appendChild(inputPlates);

                if (this.startDate) {
                    let inputStart = document.createElement('input');
                    inputStart.type = 'hidden';
                    inputStart.name = 'start_date';
                    inputStart.value = this.startDate;
                    form.appendChild(inputStart);
                }

                if (this.endDate) {
                    let inputEnd = document.createElement('input');
                    inputEnd.type = 'hidden';
                    inputEnd.name = 'end_date';
                    inputEnd.value = this.endDate;
                    form.appendChild(inputEnd);
                }

                let inputFormat = document.createElement('input');
                inputFormat.type = 'hidden';
                inputFormat.name = 'format';
                inputFormat.value = format;
                form.appendChild(inputFormat);

                let inputType = document.createElement('input');
                inputType.type = 'hidden';
                inputType.name = 'type';
                inputType.value = type;
                form.appendChild(inputType);

                if (this.withTanggalUpdate) {
                    let inputTgl = document.createElement('input');
                    inputTgl.type = 'hidden';
                    inputTgl.name = 'with_tanggal_update';
                    inputTgl.value = '1';
                    form.appendChild(inputTgl);
                }

                document.body.appendChild(form);
                form.submit();
                setTimeout(() => form.remove(), 1000);
            },

            exportPDF(includePrice = true) {
                if (this.selectedPlates.length === 0) return;
                let self = this;
                this.exporting = true;

                $.ajax({
                    url: "{{ route('maintenance.batch.export.download') }}",
                    data: {
                        nomor_polisi: self.selectedPlates.join(','),
                        start_date: self.startDate,
                        end_date: self.endDate,
                        format: 'json'
                    },
                    success: function(res) {
                        self.generatePDFReport(res, includePrice);
                    },
                    error: function(err) {
                        alert('Gagal mengambil data untuk PDF. Silakan coba lagi.');
                        console.error(err);
                    },
                    complete: function() {
                        self.exporting = false;
                    }
                });
            },

            generatePDFReport(data, includePrice) {
                let docContent = [];

                // Title Page / Header
                docContent.push({
                    text: 'LAPORAN BATCH MAINTENANCE HISTORY',
                    style: 'mainTitle',
                    alignment: 'center',
                    margin: [0, 0, 0, 4]
                });
                docContent.push({
                    text: 'Generated on: ' + (data.generatedAt || new Date().toLocaleString('id-ID')) + ' | Total Kendaraan: ' + data.groupedData.length,
                    alignment: 'center',
                    fontSize: 9,
                    color: '#64748b',
                    margin: [0, 0, 0, 15]
                });

                // Iterate over each vehicle
                data.groupedData.forEach(function(item, vIdx) {
                    let v = item.vehicle;
                    let txs = item.transactions;

                    // Vehicle Specs Box
                    docContent.push({
                        table: {
                            widths: ['*', '*', '*'],
                            body: [
                                [
                                    { text: [{ text: 'Nomor Polisi: ', bold: true }, v.nomor_polisi || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Nomor Chassis: ', bold: true }, v.nomor_chassis || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Model: ', bold: true }, v.model || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] }
                                ],
                                [
                                    { text: [{ text: 'Tahun: ', bold: true }, v.tahun_pembuatan || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Warna: ', bold: true }, v.warna || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Nomor Mesin: ', bold: true }, v.nomor_mesin || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] }
                                ],
                                [
                                    { text: [{ text: 'Tgl Beli: ', bold: true }, v.tanggal_pembelian || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Supplier: ', bold: true }, v.kode_sup || '-'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] },
                                    { text: [{ text: 'Total Transaksi: ', bold: true }, txs.length + ' Baris'], fillColor: '#f1f5f9', border: [0, 0, 0, 0] }
                                ]
                            ]
                        },
                        margin: [0, vIdx > 0 ? 10 : 0, 0, 8],
                        fontSize: 8.5
                    });

                    // Transactions Table
                    let tableHeaders = [];
                    let colWidths = [];

                    if (includePrice) {
                        tableHeaders = ['No Job', 'Tgl Job', 'KM', 'Tipe', 'Service', 'Product', 'Deskripsi', 'Qty', 'Harga', 'Total', 'Tax', 'Ket'];
                        colWidths = [70, 45, 35, 35, 45, 65, 80, 20, 50, 65, 45, '*'];
                    } else {
                        tableHeaders = ['No Job', 'Tgl Job', 'KM', 'Tipe', 'Service', 'Product', 'Deskripsi', 'Qty', 'Tgl Close', 'Keterangan'];
                        colWidths = [80, 50, 40, 40, 55, 85, 100, 25, 55, '*'];
                    }

                    let bodyRows = [
                        tableHeaders.map(h => ({ text: h, style: 'tableHeader' }))
                    ];

                    if (txs.length === 0) {
                        bodyRows.push([
                            { text: 'Tidak ada riwayat perbaikan tercatat untuk periode ini.', colSpan: tableHeaders.length, alignment: 'center', italics: true, color: '#94a3b8', margin: [0, 5, 0, 5] },
                            ...Array(tableHeaders.length - 1).fill('')
                        ]);
                    } else {
                        txs.forEach(function(row) {
                            if (includePrice) {
                                let hargaFormatted = row.harga ? Number(row.harga).toLocaleString('id-ID') : (row.is_detail ? '' : '-');
                                let totalFormatted = row.harga_total ? Number(row.harga_total).toLocaleString('id-ID') : '';
                                if (row.workshop_harent) totalFormatted += ' - ' + row.workshop_harent;
                                let taxFormatted = row.harga_pajak ? Number(row.harga_pajak).toLocaleString('id-ID') : '';

                                bodyRows.push([
                                    { text: row.nomor_job || '', fontSize: 7, color: row.is_detail ? '#64748b' : '#0f172a' },
                                    { text: row.tanggal_job || '', fontSize: 7 },
                                    { text: String(row.posisi_km || ''), fontSize: 7 },
                                    { text: row.tipe || '', fontSize: 7 },
                                    { text: row.maintenance_service || '', fontSize: 7 },
                                    { text: row.product || '', fontSize: 7 },
                                    { text: row.deskripsi || '', fontSize: 7 },
                                    { text: String(row.jumlah || ''), fontSize: 7, alignment: 'center' },
                                    { text: hargaFormatted, fontSize: 7, alignment: 'right' },
                                    { text: totalFormatted, fontSize: 7, alignment: 'right' },
                                    { text: taxFormatted, fontSize: 7, alignment: 'right' },
                                    { text: row.keterangan || '', fontSize: 7 }
                                ]);
                            } else {
                                bodyRows.push([
                                    { text: row.nomor_job || '', fontSize: 7, color: row.is_detail ? '#64748b' : '#0f172a' },
                                    { text: row.tanggal_job || '', fontSize: 7 },
                                    { text: String(row.posisi_km || ''), fontSize: 7 },
                                    { text: row.tipe || '', fontSize: 7 },
                                    { text: row.maintenance_service || '', fontSize: 7 },
                                    { text: row.product || '', fontSize: 7 },
                                    { text: row.deskripsi || '', fontSize: 7 },
                                    { text: String(row.jumlah || ''), fontSize: 7, alignment: 'center' },
                                    { text: row.tanggal_close || '', fontSize: 7 },
                                    { text: row.keterangan || '', fontSize: 7 }
                                ]);
                            }
                        });

                        // Subtotal row for this vehicle if price included
                        if (includePrice && item.totalCost > 0) {
                            bodyRows.push([
                                { text: 'Subtotal ' + v.nomor_polisi + ':', colSpan: 9, bold: true, alignment: 'right', fillColor: '#f8fafc', fontSize: 7.5 },
                                ...Array(8).fill(''),
                                { text: 'Rp ' + Number(item.totalCost).toLocaleString('id-ID'), bold: true, alignment: 'right', fillColor: '#f8fafc', fontSize: 7.5 },
                                { text: 'Rp ' + Number(item.totalTax).toLocaleString('id-ID'), bold: true, alignment: 'right', fillColor: '#f8fafc', fontSize: 7.5 },
                                { text: '', fillColor: '#f8fafc' }
                            ]);
                        }
                    }

                    docContent.push({
                        table: {
                            headerRows: 1,
                            widths: colWidths,
                            body: bodyRows
                        },
                        margin: [0, 0, 0, 15]
                    });

                    // Add page break between vehicles except for the last one
                    if (vIdx < data.groupedData.length - 1) {
                        docContent.push({ text: '', pageBreak: 'after' });
                    }
                });

                // Grand Total block at the very end
                if (includePrice) {
                    docContent.push({
                        table: {
                            widths: ['*', 180],
                            body: [
                                [
                                    { text: 'RINGKASAN TOTAL KESELURUHAN (' + data.groupedData.length + ' KENDARAAN)', bold: true, fillColor: '#e0e7ff', fontSize: 9 },
                                    { text: 'Rp ' + Number(data.overallGrandTotal).toLocaleString('id-ID'), bold: true, alignment: 'right', fillColor: '#e0e7ff', fontSize: 10 }
                                ]
                            ]
                        },
                        margin: [0, 10, 0, 0]
                    });
                }

                let docDefinition = {
                    pageOrientation: 'landscape',
                    pageSize: 'A4',
                    pageMargins: [20, 20, 20, 20],
                    content: docContent,
                    styles: {
                        mainTitle: {
                            fontSize: 14,
                            bold: true,
                            color: '#1e293b'
                        },
                        tableHeader: {
                            bold: true,
                            fontSize: 7.5,
                            color: '#334155',
                            fillColor: '#f1f5f9',
                            alignment: 'center'
                        }
                    },
                    defaultStyle: {
                        fontSize: 7.5
                    }
                };

                let filename = 'Batch_Maintenance_Report_' + (includePrice ? 'Lengkap_' : 'Tanpa_Harga_') + new Date().toISOString().slice(0, 10) + '.pdf';
                pdfMake.createPdf(docDefinition).download(filename);
            },

            generateSummaryPDFReport(data) {
                let rows = data.summaryRows || [];
                let docContent = [];

                // Title Page / Header
                docContent.push({
                    text: 'RINGKASAN BATCH MAINTENANCE HISTORY PER KENDARAAN (SUMMARY)',
                    style: 'mainTitle',
                    alignment: 'center',
                    margin: [0, 0, 0, 4]
                });

                let subtitle = 'Generated on: ' + (data.generatedAt || new Date().toLocaleString('id-ID')) + ' | Total Kendaraan: ' + (data.totalVehicles || rows.length);
                if (data.startDate || data.endDate) {
                    subtitle += ' | Periode: ' + (data.startDate || 'Awal') + ' s/d ' + (data.endDate || 'Akhir');
                }

                docContent.push({
                    text: subtitle,
                    alignment: 'center',
                    fontSize: 8.5,
                    color: '#64748b',
                    margin: [0, 0, 0, 14]
                });

                // Summary Table with 1 row per vehicle
                let tableHeaders = [
                    'No',
                    'Nomor Polisi',
                    'Nomor Chassis',
                    'Model',
                    'Thn / Warna',
                    'Supplier',
                    'Jml Job',
                    'Subtotal Biaya (Rp)',
                    'PPN (Rp)',
                    'Grand Total (Rp)'
                ];
                let colWidths = [22, 68, 105, 115, 75, 50, 45, 80, 65, 85];

                let bodyRows = [
                    tableHeaders.map((h, i) => ({
                        text: h,
                        style: 'tableHeader',
                        alignment: (i >= 6 ? (i === 6 ? 'center' : 'right') : 'left')
                    }))
                ];

                if (rows.length === 0) {
                    bodyRows.push([
                        { text: 'Tidak ada data kendaraan yang dipilih.', colSpan: tableHeaders.length, alignment: 'center', italics: true, color: '#94a3b8', margin: [0, 8, 0, 8] },
                        ...Array(tableHeaders.length - 1).fill('')
                    ]);
                } else {
                    rows.forEach(function(row, idx) {
                        let isEven = (idx % 2 === 0);
                        let bg = isEven ? '#ffffff' : '#f8fafc';

                        bodyRows.push([
                            { text: String(idx + 1), fontSize: 7.5, alignment: 'center', fillColor: bg },
                            { text: row.nomor_polisi, fontSize: 7.5, bold: true, color: '#1e3a8a', fillColor: bg },
                            { text: row.nomor_chassis, fontSize: 7, fillColor: bg },
                            { text: row.model, fontSize: 7.5, fillColor: bg },
                            { text: (row.tahun_pembuatan || '-') + ' / ' + (row.warna || '-'), fontSize: 7, fillColor: bg },
                            { text: row.kode_sup, fontSize: 7, fillColor: bg },
                            { text: String(row.job_count), fontSize: 7.5, alignment: 'center', fillColor: bg },
                            { text: row.cost_formatted, fontSize: 7.5, alignment: 'right', fillColor: bg },
                            { text: row.tax_formatted, fontSize: 7.5, alignment: 'right', fillColor: bg },
                            { text: row.grand_total_formatted, fontSize: 7.5, bold: true, alignment: 'right', fillColor: bg }
                        ]);
                    });

                    // Total Summary Row at bottom
                    bodyRows.push([
                        { text: 'TOTAL (' + rows.length + ' KENDARAAN)', bold: true, fontSize: 8, colSpan: 6, alignment: 'right', fillColor: '#e2e8f0' },
                        '', '', '', '', '',
                        { text: String(data.totalJobs || 0) + ' Jobs', bold: true, fontSize: 8, alignment: 'center', fillColor: '#e2e8f0' },
                        { text: data.overallCostFormatted || 'Rp 0', bold: true, fontSize: 8, alignment: 'right', fillColor: '#e2e8f0' },
                        { text: data.overallTaxFormatted || 'Rp 0', bold: true, fontSize: 8, alignment: 'right', fillColor: '#e2e8f0' },
                        { text: data.overallGrandFormatted || 'Rp 0', bold: true, fontSize: 8, alignment: 'right', fillColor: '#cbd5e1', color: '#0f172a' }
                    ]);
                }

                docContent.push({
                    table: {
                        headerRows: 1,
                        widths: colWidths,
                        body: bodyRows
                    },
                    layout: {
                        hLineWidth: function (i, node) { return 0.5; },
                        vLineWidth: function (i, node) { return 0.5; },
                        hLineColor: function (i, node) { return '#cbd5e1'; },
                        vLineColor: function (i, node) { return '#cbd5e1'; },
                        paddingLeft: function (i, node) { return 4; },
                        paddingRight: function (i, node) { return 4; },
                        paddingTop: function (i, node) { return 4; },
                        paddingBottom: function (i, node) { return 4; }
                    }
                });

                let docDefinition = {
                    pageSize: 'A4',
                    pageOrientation: 'landscape',
                    pageMargins: [25, 25, 25, 25],
                    content: docContent,
                    styles: {
                        mainTitle: {
                            fontSize: 13,
                            bold: true,
                            color: '#1e293b'
                        },
                        tableHeader: {
                            bold: true,
                            fontSize: 7.5,
                            color: '#ffffff',
                            fillColor: '#1e293b'
                        }
                    },
                    defaultStyle: {
                        fontSize: 7.5
                    }
                };

                let filename = 'Batch_Summary_Kendaraan_' + new Date().toISOString().slice(0, 10) + '.pdf';
                pdfMake.createPdf(docDefinition).download(filename);
            }
        };
    }
    </script>
@endsection
