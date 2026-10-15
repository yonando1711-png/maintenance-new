@extends('layouts.app')

@section('content')
<div x-data="transaksiPage()" x-init="initTable()" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden theme-transition mb-6">
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                Maintenance History
                <span x-show="nomorPolisiFilter || namaCustomerFilter" class="text-sm font-normal text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 px-3 py-1 rounded-lg ml-3">
                    <span x-text="nomorPolisiFilter ? nomorPolisiFilter + ' - ' : ''"></span>
                    <span x-text="namaCustomerFilter ? namaCustomerFilter : 'All Vehicles'"></span>
                </span>
                <span x-show="!nomorPolisiFilter && !namaCustomerFilter" class="text-sm font-normal text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 px-3 py-1 rounded-lg ml-3">
                    All Vehicles
                </span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Operational Cost Report</p>
        </div>
    </div>

    <div class="p-6">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label for="nomor_polisi_select" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Nomor Polisi</label>
                    <select id="nomor_polisi_select" x-model="nomorPolisiFilter" class="w-full select2-nomor-polisi">
                        <option></option>
                    </select>
                </div>

                <div>
                    <label for="nama_customer_select" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Nama Customer</label>
                    <select id="nama_customer_select" x-model="namaCustomerFilter" class="w-full select2-customer">
                        <option></option>
                    </select>
                </div>
                
                <div>
                    <label for="start_date_transaksi" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Start Date</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="text" x-model="startDateDisplay" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm date-picker" id="start_date_transaksi" autocomplete="off" placeholder="DD-MM-YYYY">
                    </div>
                </div>

                <div>
                    <label for="end_date_transaksi" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">End Date</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="text" x-model="endDateDisplay" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm date-picker" id="end_date_transaksi" autocomplete="off" placeholder="DD-MM-YYYY">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button @click="resetFilters()" 
                        class="h-[40px] px-6 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-xl font-bold uppercase tracking-wider text-sm transition-colors flex items-center gap-2 whitespace-nowrap shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset
                </button>
                <button @click="reloadTable()" 
                        class="h-[40px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white border border-indigo-600 rounded-xl font-bold uppercase tracking-wider text-sm transition-all flex items-center gap-2 whitespace-nowrap shadow-md hover:shadow-indigo-200 dark:hover:shadow-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Search
                </button>
            </div>
        </div>

        <div x-show="mobilDetail" x-cloak class="bg-white dark:bg-slate-800 border-l-4 border-indigo-500 rounded-2xl shadow-sm mb-8 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800/80 px-5 py-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider text-sm">Detail Mobil</h3>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div class="space-y-3">
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Nomor Polisi:</span> <span x-text="mobilDetail?.nomor_polisi || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Tahun Pembuatan:</span> <span x-text="mobilDetail?.tahun_pembuatan || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Tanggal Pembelian:</span> <span x-text="mobilDetail?.tanggal_pembelian || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Nomor Chassis:</span> <span x-text="mobilDetail?.nomor_chassis || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Warna:</span> <span x-text="mobilDetail?.warna || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Kode Supplier:</span> <span x-text="mobilDetail?.kode_sup || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Model:</span> <span x-text="mobilDetail?.model || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                    <div class="flex justify-between md:block"><span class="text-slate-500 font-medium">Nomor Mesin:</span> <span x-text="mobilDetail?.nomor_mesin || '-'" class="font-bold text-slate-800 dark:text-slate-200 md:ml-2"></span></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Maintenance History Cost</div>
                <div id="kpi-cost" class="text-2xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($grandTotals['grandTotal'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:border-indigo-300 transition-colors">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">Total Tax (PPN)</div>
                <div id="kpi-tax" class="text-2xl font-black text-slate-500 dark:text-slate-400">Rp {{ number_format($grandTotals['hargaPajak'], 0, ',', '.') }}</div>
            </div>
        </div>

        <style>
            [x-cloak] { display: none !important; }
            /* Frozen Table - matching total_stock pattern */
            .frozen-table-container {
                max-height: calc(100vh - 460px);
                min-height: 300px;
                overflow: auto;
                position: relative;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.75rem;
            }
            .dark .frozen-table-container {
                border-color: rgb(30 41 59);
            }
            .frozen-table {
                border-collapse: separate;
                border-spacing: 0;
                width: 100%;
            }
            /* Sticky Header Row */
            .frozen-table thead {
                position: sticky;
                top: 0;
                z-index: 20;
            }
            .frozen-table thead th {
                background: rgb(248 250 252);
                color: rgb(100 116 139);
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgb(226 232 240);
                white-space: nowrap;
            }
            .dark .frozen-table thead th {
                background: rgb(2 6 23);
                color: rgb(148 163 184);
                border-bottom-color: rgb(30 41 59);
            }
            /* Sticky First Column */
            .frozen-table th.sticky-col,
            .frozen-table td.sticky-col {
                position: sticky;
                left: 0;
                z-index: 10;
            }
            .frozen-table thead th.sticky-col {
                z-index: 30;
                background: rgb(248 250 252);
            }
            .dark .frozen-table thead th.sticky-col {
                background: rgb(2 6 23);
            }
            .frozen-table tbody td.sticky-col {
                background: rgb(255 255 255);
            }
            .dark .frozen-table tbody td.sticky-col {
                background: rgb(15 23 42);
            }
            .frozen-table tbody tr:hover td.sticky-col {
                background: rgb(248 250 252 / 0.8);
            }
            .dark .frozen-table tbody tr:hover td.sticky-col {
                background: rgb(30 41 59 / 0.8);
            }
            /* Shadow effect for frozen column on scroll */
            .frozen-table th.sticky-col::after,
            .frozen-table td.sticky-col::after {
                content: '';
                position: absolute;
                top: 0; right: -8px; bottom: 0;
                width: 8px;
                background: linear-gradient(to right, rgba(0,0,0,0.06), transparent);
                pointer-events: none;
            }
            .dark .frozen-table th.sticky-col::after,
            .dark .frozen-table td.sticky-col::after {
                background: linear-gradient(to right, rgba(0,0,0,0.25), transparent);
            }
            /* Body cells */
            .frozen-table tbody td {
                padding: 0.625rem 1rem;
                font-size: 0.8125rem;
                color: rgb(51 65 85);
                border-bottom: 1px solid rgb(241 245 249);
                vertical-align: top;
                max-width: 260px;
                word-break: break-word;
            }
            .dark .frozen-table tbody td {
                color: rgb(203 213 225);
                border-bottom-color: rgb(30 41 59);
            }
            .frozen-table tbody tr:hover td {
                background: rgb(248 250 252 / 0.5);
            }
            .dark .frozen-table tbody tr:hover td {
                background: rgb(30 41 59 / 0.5);
            }
            /* Detail rows (sub-lines) */
            .frozen-table tbody tr.is-detail-row td {
                background: rgb(248 250 252);
                color: rgb(100 116 139);
                font-size: 0.75rem;
                border-bottom-color: rgb(241 245 249);
            }
            .dark .frozen-table tbody tr.is-detail-row td {
                background: rgb(15 23 42 / 0.6);
                color: rgb(148 163 184);
            }
            .frozen-table tbody tr.is-detail-row td.sticky-col {
                background: rgb(248 250 252);
            }
            .dark .frozen-table tbody tr.is-detail-row td.sticky-col {
                background: rgb(15 23 42);
            }
            /* Column resize handle */
            .frozen-table thead th {
                position: relative;
                overflow: visible;
            }
            .frozen-table thead th .col-resize-handle {
                position: absolute;
                right: 0;
                top: 0;
                bottom: 0;
                width: 6px;
                cursor: col-resize;
                z-index: 40;
                background: transparent;
                transition: background 0.15s;
            }
            .frozen-table thead th .col-resize-handle:hover,
            .frozen-table thead th .col-resize-handle.resizing {
                background: rgb(99 102 241 / 0.5);
            }
            .frozen-table thead th .col-resize-handle::after {
                content: '';
                position: absolute;
                right: 2px;
                top: 25%;
                bottom: 25%;
                width: 2px;
                border-radius: 1px;
                background: rgb(148 163 184 / 0.4);
                transition: background 0.15s;
            }
            .frozen-table thead th .col-resize-handle:hover::after,
            .frozen-table thead th .col-resize-handle.resizing::after {
                background: rgb(99 102 241);
            }

            /* DataTables Buttons overrides */
            .dt-buttons .dt-button {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                color: #334155;
                padding: 0.5rem 1rem;
                border-radius: 0.75rem;
                font-weight: 500;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                transition: all 0.2s;
            }
            .dt-buttons .dt-button:hover {
                background-color: #f8fafc;
            }
            .dark .dt-buttons .dt-button {
                background-color: #1e293b;
                border-color: #334155;
                color: #cbd5e1;
            }
            .dark .dt-buttons .dt-button:hover {
                background-color: #334155;
            }
            .dt-button-collection {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                padding: 0.5rem;
                min-width: 200px;
            }
            .dark .dt-button-collection {
                background-color: #1e293b;
                border-color: #334155;
            }
            .dt-button-collection .dt-button {
                width: 100%;
                text-align: left;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.375rem 0.75rem;
                border-radius: 0.5rem;
                font-size: 0.8125rem;
            }
            .dt-button-collection .dt-button:hover {
                background-color: rgb(241 245 249) !important;
            }
            .dark .dt-button-collection .dt-button:hover {
                background-color: rgb(51 65 85) !important;
            }
            .dt-button-collection .dt-button.active::after {
                content: '✓';
                color: #4f46e5;
                font-weight: 700;
                margin-left: 0.5rem;
            }
            .dark .dt-button-collection .dt-button.active::after {
                color: #818cf8;
            }

            /* DataTables overrides */
            #transaksiTable_wrapper { padding: 1rem 0 0; }
            .dataTables_wrapper .dataTables_length select,
            .dataTables_wrapper .dataTables_filter input {
                border: 1px solid rgb(226 232 240);
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                padding: 0.375rem 0.75rem;
                margin-left: 0.5rem;
            }
            .dark .dataTables_wrapper .dataTables_length select,
            .dark .dataTables_wrapper .dataTables_filter input {
                background: rgb(30 41 59);
                border-color: rgb(51 65 85);
                color: rgb(203 213 225);
            }
            .dataTables_wrapper .dt-buttons .btn {
                background: white;
                border: 1px solid rgb(226 232 240);
                color: rgb(71 85 105);
                padding: 0.375rem 0.75rem;
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                font-weight: 500;
                margin-right: 0.375rem;
                box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
                transition: all 0.15s;
            }
            .dark .dataTables_wrapper .dt-buttons .btn {
                background: rgb(30 41 59);
                border-color: rgb(51 65 85);
                color: rgb(148 163 184);
            }
            .dataTables_wrapper .dt-buttons .btn:hover {
                background: rgb(241 245 249);
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.25rem 0.625rem;
                border: 1px solid rgb(226 232 240);
                margin: 0 2px;
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                background: white;
                color: rgb(71 85 105) !important;
                cursor: pointer;
                transition: all 0.15s;
            }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button {
                background: rgb(30 41 59);
                border-color: rgb(51 65 85);
                color: rgb(148 163 184) !important;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: rgb(79 70 229) !important;
                color: white !important;
                border-color: rgb(79 70 229) !important;
            }
            .dataTables_wrapper .dataTables_info {
                font-size: 0.75rem;
                color: rgb(100 116 139);
                padding-top: 1rem;
            }
        </style>

        <!-- DT controls rendered here (outside scroll container) -->
        <div id="dt-controls-top" class="mb-3"></div>

        <div class="frozen-table-container" id="mainTableContainer">
            <table id="transaksiTable" class="frozen-table" style="min-width: 1100px;">
                <thead>
                    <tr>
                        <th class="sticky-col" style="min-width:200px;">Nomor Job</th>
                        <th style="min-width:100px;">No Polisi</th>
                        <th style="min-width:80px;">Tipe</th>
                        <th style="min-width:100px;">Tanggal Job</th>
                        <th style="min-width:80px;">Posisi KM</th>
                        <th style="min-width:140px;">Maintenance/Service</th>
                        <th style="min-width:150px;">Product</th>
                        <th style="min-width:200px;">Deskripsi</th>
                        <th style="min-width:60px;">Jumlah</th>
                        <th style="min-width:100px;">Harga</th>
                        <th style="min-width:110px;">Harga Total</th>
                        <th style="min-width:110px;">Harga Pajak</th>
                        <th style="min-width:220px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- DT info/pagination rendered here (outside scroll container) -->
        <div id="dt-controls-bottom" class="mt-3"></div>
    </div>
</div>
@endsection

@section('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <style>
    /* Base styles (Light mode) */
    .select2-container--default .select2-selection--single {
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        height: 2.5rem !important;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155;
        font-size: 0.875rem;
        padding-left: 0.75rem;
        font-weight: 400;
        line-height: normal;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 2.5rem;
        right: 0.5rem;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border-top-width: 0;
        background-color: #ffffff;
        font-size: 0.875rem;
        overflow: hidden;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background-color: #f8fafc;
        color: #334155;
    }
    .select2-results__option {
        color: #334155;
        padding: 0.5rem 1rem;
    }
    .select2-results__option:hover {
        background-color: #e0e7ff;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
    }
    
    /* Dark mode overrides */
    .dark .select2-container--default .select2-selection--single {
        border-color: #475569;
        background-color: #334155;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #e2e8f0;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8;
    }
    .dark .select2-dropdown {
        border-color: #475569;
        background-color: #334155;
    }
    .dark .select2-search--dropdown .select2-search__field {
        border-color: #475569;
        background-color: #1e293b;
        color: #e2e8f0;
    }
    .dark .select2-results__option {
        color: #cbd5e1;
    }
    .dark .select2-results__option:hover {
        background-color: rgba(49, 46, 129, 0.4);
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
    .dark .daterangepicker .calendar-table .next span, 
    .dark .daterangepicker .calendar-table .prev span {
        border-color: #cbd5e1;
    }
    .dark .daterangepicker td.off, 
    .dark .daterangepicker td.off.in-range, 
    .dark .daterangepicker td.off.start-date, 
    .dark .daterangepicker td.off.end-date {
        background-color: #0f172a;
        border-color: transparent;
        color: #475569;
    }
    .dark .daterangepicker td.available:hover, 
    .dark .daterangepicker th.available:hover {
        background-color: #334155;
        color: #f8fafc;
    }
    .dark .daterangepicker td.in-range {
        background-color: rgba(79, 70, 229, 0.2);
        color: #e2e8f0;
    }
    .dark .daterangepicker td.active, 
    .dark .daterangepicker td.active:hover {
        background-color: #4f46e5;
        color: #ffffff;
    }
    .dark .daterangepicker .drp-buttons {
        border-top-color: #475569;
    }
    .dark .daterangepicker .drp-buttons .btn {
        font-weight: 500;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
    }
    .dark .daterangepicker .drp-buttons .btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }
    .dark .daterangepicker .drp-buttons .btn-default {
        background-color: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    .dark .daterangepicker select.monthselect, 
    .dark .daterangepicker select.yearselect {
        background-color: #334155;
        border-color: #475569;
        color: #e2e8f0;
        border-radius: 0.375rem;
        padding: 2px 4px;
    }
    .dark .daterangepicker:before {
        border-bottom-color: #475569 !important;
    }
    .dark .daterangepicker:after {
        border-bottom-color: #1e293b !important;
    }
    .dark .daterangepicker.drop-up:before {
        border-bottom-color: transparent !important;
        border-top-color: #475569 !important;
    }
    .dark .daterangepicker.drop-up:after {
        border-bottom-color: transparent !important;
        border-top-color: #1e293b !important;
    }

    /* Consistent Export Button Styles */
    .dt-buttons .btn-export {
        background-color: #64748b !important;
        border-color: #475569 !important;
        color: white !important;
        padding: 0.5rem 1rem !important;
        border-radius: 0.5rem !important;
        font-size: 0.875rem !important;
        margin-right: 0.5rem !important;
    }
    .dt-buttons .btn-export:hover {
        background-color: #475569 !important;
    }
    </style>

    <script>
        function transaksiPage() {
            return {
                nomorPolisiFilter: '{{ $nomor_polisi ?? "" }}',
                namaCustomerFilter: '{{ $nama_customer ?? "" }}',
                startDate: '{{ request("start_date_transaksi") ?? "" }}',
                endDate: '{{ request("end_date_transaksi") ?? "" }}',
                startDateDisplay: '{{ request("start_date_transaksi") ? \Carbon\Carbon::parse(request("start_date_transaksi"))->format("d-m-Y") : "" }}',
                endDateDisplay: '{{ request("end_date_transaksi") ? \Carbon\Carbon::parse(request("end_date_transaksi"))->format("d-m-Y") : "" }}',
                mobilDetail: @json($mobilDetail),
                table: null,

                initTable() {
                    const self = this;
                    
                    var grandTotal = {{ $grandTotals['grandTotal'] }};
                    var hargaTotal = {{ $grandTotals['hargaTotal'] }};
                    var hargaPajak = {{ $grandTotals['hargaPajak'] }};

                    // Initialize DataTable with server-side processing
                    this.table = $('#transaksiTable').DataTable({
                        "processing": true,
                        "serverSide": true,
                        ajax: {
                            url: "{{ route('maintenance.vehicle.transactions.data') }}",
                            "data": function(d) {
                                d.nama_customer = self.namaCustomerFilter;
                                d.nomor_polisi = self.nomorPolisiFilter;
                                d.start_date_transaksi = self.startDate;
                                d.end_date_transaksi = self.endDate;
                            },
                            dataSrc: function(json) {
                                // Update KPI cards from AJAX response
                                if (json.grandTotals) {
                                    $('#kpi-cost').text('Rp ' + json.grandTotals.grandTotal.toLocaleString('id-ID'));
                                    $('#kpi-tax').text('Rp ' + json.grandTotals.hargaPajak.toLocaleString('id-ID'));
                                }
                                // Update mobilDetail from AJAX response
                                self.mobilDetail = json.mobilDetail;
                                return json.data;
                            }
                        },
                        "columns": [{
                                "data": "nomor_job"
                            },
                    {
                        "data": "nomor_polisi"
                    },
                    {
                        "data": "tipe"
                    },
                    {
                        "data": "tanggal_job"
                    },
                    {
                        "data": "posisi_km"
                    },
                    {
                        "data": "maintenance_service"
                    },
                    {
                        "data": "product"
                    },
                    {
                        "data": "deskripsi"
                    },
                    {
                        "data": "jumlah"
                    },
                    {
                        "data": "harga"
                    },
                    {
                        "data": "harga_total",
                        "render": function(data, type, row) {
                            var base = (data === '' || data === null) ? '' : (typeof data ===
                                'number' ? data.toLocaleString('id-ID') : data);
                            if (row.workshop_harent) {
                                return base + ' - ' + row.workshop_harent;
                            }
                            return base;
                        }
                    },
                    {
                        "data": "harga_pajak"
                    },
                    {
                        "data": "keterangan"
                    }
                ],
                "pageLength": 50,
                "lengthMenu": [
                    [25, 50, 100, 200],
                    [25, 50, 100, 200]
                ],
                "order": [],
                "searching": true,
                "ordering": false,
                "columnDefs": [],
                "createdRow": function(row, data, dataIndex) {
                    if (data.is_detail) {
                        $(row).addClass('is-detail-row');
                        $('td:eq(0)', row).addClass('sticky-col');
                    } else {
                        $('td:eq(0)', row).addClass('sticky-col');
                    }
                },
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search...",
                    "lengthMenu": "_MENU_ entries"
                },
                "dom": '<"flex flex-wrap items-center justify-between gap-4 mb-4"B<"flex items-center gap-4"lf>>rt<"flex flex-wrap items-center justify-between gap-4 mt-4"ip>',
                "buttons": [{
                        extend: 'colvis',
                        className: 'btn-export',
                        text: '<span class="flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>Columns</span>'
                    },
                    {
                        text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Export XLSX</span>',
                        className: 'btn-export',
                        action: function(e, dt, button, config) {
                            exportAllToXLSX();
                        }
                    },
                    {
                        text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Export with Tanggal Update Spareparts</span>',
                        className: 'btn-export',
                        action: function(e, dt, button, config) {
                            exportAllToXLSXWithTanggalUpdate();
                        }
                    },
                    {
                        text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Export CSV</span>',
                        className: 'btn-export',
                        action: function(e, dt, button, config) {
                            exportAllToCSV();
                        }
                    },
                    {
                        text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>PDF</span>',
                        className: 'btn-export',
                        action: function(e, dt, button, config) {
                            exportAllToPDF();
                        }
                    },
                    {
                        text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>PDF Tanpa Harga</span>',
                        className: 'btn-export',
                        action: function(e, dt, button, config) {
                            exportAllToPDFTanpaHarga();
                        }
                    }
                ]
            });
        },

        reloadTable() {
            // If searching by customer ONLY (no vehicle selected), redirect to the vehicle list view
            if (this.namaCustomerFilter && !this.nomorPolisiFilter) {
                let url = new URL("{{ route('maintenance.dashboard') }}", window.location.origin);
                url.searchParams.append('nama_customer', this.namaCustomerFilter);
                if (this.startDate) url.searchParams.append('start_date_transaksi', this.startDate);
                if (this.endDate) url.searchParams.append('end_date_transaksi', this.endDate);
                window.location.href = url.href;
                return;
            }

            if (this.table) {
                this.table.ajax.reload();
            }
        },

        resetFilters() {
            this.startDate = '';
            this.endDate = '';
            this.startDateDisplay = '';
            this.endDateDisplay = '';
            this.nomorPolisiFilter = '';
            this.namaCustomerFilter = '';
            $('.select2-nomor-polisi').val(null).trigger('change');
            $('.select2-customer').val(null).trigger('change');
            $('#start_date_transaksi').val('');
            $('#end_date_transaksi').val('');
            this.reloadTable();
        }
    }
}

// Export ALL data functions (global scope so DataTable button callbacks can access them)
        function triggerExport(format, withTanggalUpdate = false) {
            let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
            let url = new URL("{{ route('maintenance.vehicle.transactions.export') }}", window.location.origin);
            
            if (alpineData.namaCustomerFilter) url.searchParams.append('nama_customer', alpineData.namaCustomerFilter);
            if (alpineData.nomorPolisiFilter) url.searchParams.append('nomor_polisi', alpineData.nomorPolisiFilter);
            if (alpineData.startDate) url.searchParams.append('start_date_transaksi', alpineData.startDate);
            if (alpineData.endDate) url.searchParams.append('end_date_transaksi', alpineData.endDate);
            
            url.searchParams.append('format', format);
            if (withTanggalUpdate) {
                url.searchParams.append('tanggal_update', '1');
            }
            
            window.location.href = url.href;
        }

        function exportAllToXLSX() { triggerExport('xlsx', false); }
        function exportAllToXLSXWithTanggalUpdate() { triggerExport('xlsx', true); }
        function exportAllToCSV() { triggerExport('csv', false); }

            function exportAllToPDF() {
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                $.ajax({
                    url: "{{ route('maintenance.vehicle.transactions.export') }}",
                    data: {
                        nama_customer: alpineData.namaCustomerFilter,
                        nomor_polisi: alpineData.nomorPolisiFilter,
                        start_date_transaksi: alpineData.startDate,
                        end_date_transaksi: alpineData.endDate
                    },
                    success: function(response) {
                        var customerDetail = (response.nomor_polisi || response.customer)
                            ? [(response.nomor_polisi || ''), (response.customer || '')].filter(Boolean).join(' - ')
                            : 'All Vehicles';

                        var headerContent = [];
                        if (response.mobilDetail) {
                            headerContent.push({
                                text: response.mobilDetail.nomor_polisi || 'Maintenance History',
                                style: 'header',
                                margin: [0, 0, 0, 10]
                            });

                            headerContent.push({
                                table: {
                                    widths: ['*', '*', '*'],
                                    body: [
                                        [
                                            { text: [{ text: 'Nomor Polisi: ', bold: true }, response.mobilDetail.nomor_polisi || '-'], border: [0, 0, 0, 0] },
                                            { text: [{ text: 'Nomor Chassis: ', bold: true }, response.mobilDetail.nomor_chassis || '-'], border: [0, 0, 0, 0] },
                                            { text: [{ text: 'Model: ', bold: true }, response.mobilDetail.model || '-'], border: [0, 0, 0, 0] }
                                        ],
                                        [
                                            { text: [{ text: 'Tahun Pembuatan: ', bold: true }, response.mobilDetail.tahun_pembuatan || '-'], border: [0, 0, 0, 0] },
                                            { text: [{ text: 'Warna: ', bold: true }, response.mobilDetail.warna || '-'], border: [0, 0, 0, 0] },
                                            { text: [{ text: 'Nomor Mesin: ', bold: true }, response.mobilDetail.nomor_mesin || '-'], border: [0, 0, 0, 0] }
                                        ],
                                        [
                                            { text: [{ text: 'Tanggal Pembelian: ', bold: true }, response.mobilDetail.tanggal_pembelian || '-'], border: [0, 0, 0, 0] },
                                            { text: [{ text: 'Kode Supplier: ', bold: true }, response.mobilDetail.kode_sup || '-'], border: [0, 0, 0, 0] },
                                            { text: '', border: [0, 0, 0, 0] }
                                        ]
                                    ]
                                },
                                margin: [0, 0, 0, 15],
                                fontSize: 9
                            });
                        } else {
                            headerContent.push({
                                text: 'Data Report Transaksi',
                                style: 'header',
                                margin: [0, 0, 0, 4]
                            });
                            headerContent.push({
                                text: customerDetail,
                                margin: [0, 0, 0, 10]
                            });
                        }

                        var docDefinition = {
                            pageOrientation: 'landscape',
                            pageSize: 'A4',
                            pageMargins: [20, 20, 20, 20],
                            content: headerContent.concat([{
                                    table: {
                                        headerRows: 1,
                                        widths: [80, 45, 40, 45, 35, 45, 70, 80, 20, 50, 80, 45, '*'],
                                        body: [
                                            [
                                                {text: 'Nomor Job', style: 'tableHeader'},
                                                {text: 'No Polisi', style: 'tableHeader'},
                                                {text: 'Tipe', style: 'tableHeader'},
                                                {text: 'Tgl Job', style: 'tableHeader'},
                                                {text: 'Pos KM', style: 'tableHeader'},
                                                {text: 'Service', style: 'tableHeader'},
                                                {text: 'Product', style: 'tableHeader'},
                                                {text: 'Deskripsi', style: 'tableHeader'},
                                                {text: 'Qty', style: 'tableHeader'},
                                                {text: 'Harga', style: 'tableHeader'},
                                                {text: 'Total', style: 'tableHeader'},
                                                {text: 'Tax', style: 'tableHeader'},
                                                {text: 'Ket', style: 'tableHeader'}
                                            ]
                                        ].concat(response.data.map(function(row) {
                                            // Format values to match view display
                                            var hargaFormatted = row.harga === '-' ?
                                                '-' : (typeof row.harga ===
                                                    'number' ? row.harga
                                                    .toLocaleString('id-ID') : row
                                                    .harga);
                                            var hargaTotalFormatted = row
                                                .harga_total === '' ? '' : (
                                                    typeof row.harga_total ===
                                                    'number' ? row.harga_total
                                                    .toLocaleString('id-ID') : row
                                                    .harga_total);
                                            if (row.workshop_harent) {
                                                hargaTotalFormatted += ' - ' + row
                                                    .workshop_harent;
                                            }
                                            var hargaPajakFormatted = row
                                                .harga_pajak === '' ? '' : (
                                                    typeof row.harga_pajak ===
                                                    'number' ? row.harga_pajak
                                                    .toLocaleString('id-ID') : row
                                                    .harga_pajak);

                                            return [{
                                                    text: row.nomor_job || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.nomor_polisi || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.tipe || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.tanggal_job || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.posisi_km !== null && row.posisi_km !== undefined ? String(row.posisi_km) : '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.maintenance_service || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.product || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.deskripsi || '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.jumlah !== null && row.jumlah !== undefined ? String(row.jumlah) : '',
                                                    fillColor: null
                                                },
                                                {
                                                    text: hargaFormatted,
                                                    fillColor: null
                                                },
                                                {
                                                    text: hargaTotalFormatted,
                                                    fillColor: null
                                                },
                                                {
                                                    text: hargaPajakFormatted,
                                                    fillColor: null
                                                },
                                                {
                                                    text: row.keterangan || '',
                                                    fillColor: null
                                                }
                                            ];
                                        })).concat([
                                            [
                                                {text: 'GRAND TOTAL', bold: true, colSpan: 9, alignment: 'right'}, {}, {}, {}, {}, {}, {}, {}, {},
                                                {text: ''},
                                                {text: response.hargaTotal ? response.hargaTotal.toLocaleString('id-ID') : '', bold: true},
                                                {text: response.hargaPajak ? response.hargaPajak.toLocaleString('id-ID') : '', bold: true},
                                                {text: response.grandTotal ? 'Grand Total: ' + response.grandTotal.toLocaleString('id-ID') : '', bold: true}
                                            ]
                                        ])
                                    },
                                    layout: {
                                        hLineWidth: function(i) { return i === 0 || i === 1 ? 1 : 0.5; },
                                        vLineWidth: function() { return 0; },
                                        hLineColor: function(i) { return i === 0 || i === 1 ? '#4F46E5' : '#e2e8f0'; },
                                        fillColor: function(rowIndex) {
                                            if (rowIndex === 0) return '#4F46E5'; // indigo header
                                            // Footer row
                                            var totalRows = response.data.length + 1;
                                            if (rowIndex === totalRows) return '#EEF2FF';
                                            return rowIndex % 2 === 0 ? '#F8FAFC' : null;
                                        }
                                    }
                                }
                            ]),
                            styles: {
                                header: {
                                    fontSize: 15,
                                    bold: true,
                                    color: '#1E293B',
                                    margin: [0, 0, 0, 4]
                                },
                                tableHeader: {
                                    bold: true,
                                    fontSize: 8,
                                    color: 'white',
                                    fillColor: '#4F46E5',
                                    alignment: 'left'
                                },
                                tableFooter: {
                                    bold: true,
                                    fontSize: 8,
                                    color: '#4F46E5',
                                    fillColor: '#EEF2FF'
                                }
                            },
                            defaultStyle: {
                                fontSize: 7.5,
                                color: '#334155'
                            }
                        };
                        var pdfFileName = 'history_maintenance_';
                        if (response.nomor_polisi) {
                            pdfFileName += response.nomor_polisi;
                        } else if (response.customer) {
                            pdfFileName += response.customer;
                        } else {
                            pdfFileName += 'all_vehicles';
                        }
                        pdfMake.createPdf(docDefinition).download(pdfFileName + '.pdf');
                    },
                    error: function() {
                        Toast.fire({
                            icon: 'error',
                            title: 'Error exporting data. Please try again.'
                        });
                    }
                });
        }

        function exportAllToPDFTanpaHarga() {
            let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
            $.ajax({
                url: "{{ route('maintenance.vehicle.transactions.export') }}",
                data: {
                    nama_customer: alpineData.namaCustomerFilter,
                    nomor_polisi: alpineData.nomorPolisiFilter,
                    start_date_transaksi: alpineData.startDate,
                    end_date_transaksi: alpineData.endDate
                },
                success: function(response) {
                    var customerDetail = (response.nomor_polisi || response.customer)
                        ? [(response.nomor_polisi || ''), (response.customer || '')].filter(Boolean).join(' - ')
                        : 'All Vehicles';

                    var headerContent = [];
                    if (response.mobilDetail) {
                        headerContent.push({
                            text: response.mobilDetail.nomor_polisi || 'Maintenance History',
                            style: 'header',
                            margin: [0, 0, 0, 10]
                        });

                        headerContent.push({
                            table: {
                                widths: ['*', '*', '*'],
                                body: [
                                    [
                                        { text: [{ text: 'Nomor Polisi: ', bold: true }, response.mobilDetail.nomor_polisi || '-'], border: [0, 0, 0, 0] },
                                        { text: [{ text: 'Nomor Chassis: ', bold: true }, response.mobilDetail.nomor_chassis || '-'], border: [0, 0, 0, 0] },
                                        { text: [{ text: 'Model: ', bold: true }, response.mobilDetail.model || '-'], border: [0, 0, 0, 0] }
                                    ],
                                    [
                                        { text: [{ text: 'Tahun Pembuatan: ', bold: true }, response.mobilDetail.tahun_pembuatan || '-'], border: [0, 0, 0, 0] },
                                        { text: [{ text: 'Warna: ', bold: true }, response.mobilDetail.warna || '-'], border: [0, 0, 0, 0] },
                                        { text: [{ text: 'Nomor Mesin: ', bold: true }, response.mobilDetail.nomor_mesin || '-'], border: [0, 0, 0, 0] }
                                    ],
                                    [
                                        { text: [{ text: 'Tanggal Pembelian: ', bold: true }, response.mobilDetail.tanggal_pembelian || '-'], border: [0, 0, 0, 0] },
                                        { text: [{ text: 'Kode Supplier: ', bold: true }, response.mobilDetail.kode_sup || '-'], border: [0, 0, 0, 0] },
                                        { text: '', border: [0, 0, 0, 0] }
                                    ]
                                ]
                            },
                            margin: [0, 0, 0, 15],
                            fontSize: 9
                        });
                    } else {
                        headerContent.push({
                            text: 'Data Report Transaksi',
                            style: 'header',
                            margin: [0, 0, 0, 4]
                        });
                        headerContent.push({
                            text: customerDetail,
                            margin: [0, 0, 0, 10]
                        });
                    }

                    var docDefinition = {
                        pageOrientation: 'landscape',
                        pageSize: 'A4',
                        pageMargins: [20, 20, 20, 20],
                        content: headerContent.concat([{
                                table: {
                                    headerRows: 1,
                                    widths: [105, 55, 45, 50, 45, 60, 90, 140, 30, '*'],
                                    body: [
                                        [
                                            {text: 'Nomor Job', style: 'tableHeader'},
                                            {text: 'No Polisi', style: 'tableHeader'},
                                            {text: 'Tipe', style: 'tableHeader'},
                                            {text: 'Tgl Job', style: 'tableHeader'},
                                            {text: 'Pos KM', style: 'tableHeader'},
                                            {text: 'Service', style: 'tableHeader'},
                                            {text: 'Product', style: 'tableHeader'},
                                            {text: 'Deskripsi', style: 'tableHeader'},
                                            {text: 'Qty', style: 'tableHeader'},
                                            {text: 'Ket', style: 'tableHeader'}
                                        ]
                                    ].concat(response.data.map(function(row) {
                                        return [{
                                                text: row.nomor_job || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.nomor_polisi || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.tipe || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.tanggal_job || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.posisi_km !== null && row.posisi_km !== undefined ? String(row.posisi_km) : '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.maintenance_service || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.product || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.deskripsi || '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.jumlah !== null && row.jumlah !== undefined ? String(row.jumlah) : '',
                                                fillColor: null
                                            },
                                            {
                                                text: row.keterangan || '',
                                                fillColor: null
                                            }
                                        ];
                                    })).concat([
                                        [
                                            {text: 'TOTAL RECORDS: ' + response.data.length, bold: true, colSpan: 10, alignment: 'right', style: 'tableFooter'},
                                            {}, {}, {}, {}, {}, {}, {}, {}, {}
                                        ]
                                    ])
                                },
                                layout: {
                                    hLineWidth: function(i) { return i === 0 || i === 1 ? 1 : 0.5; },
                                    vLineWidth: function() { return 0; },
                                    hLineColor: function(i) { return i === 0 || i === 1 ? '#4F46E5' : '#e2e8f0'; },
                                    fillColor: function(rowIndex) {
                                        if (rowIndex === 0) return '#4F46E5'; // indigo header
                                        var totalRows = response.data.length + 1;
                                        if (rowIndex === totalRows) return '#EEF2FF';
                                        return rowIndex % 2 === 0 ? '#F8FAFC' : null;
                                    }
                                }
                            }
                        ]),
                        styles: {
                            header: {
                                fontSize: 15,
                                bold: true,
                                color: '#1E293B',
                                margin: [0, 0, 0, 4]
                            },
                            tableHeader: {
                                bold: true,
                                fontSize: 8,
                                color: 'white',
                                fillColor: '#4F46E5',
                                alignment: 'left'
                            },
                            tableFooter: {
                                bold: true,
                                fontSize: 8,
                                color: '#4F46E5',
                                fillColor: '#EEF2FF'
                            }
                        },
                        defaultStyle: {
                            fontSize: 7.5,
                            color: '#334155'
                        }
                    };
                    var pdfFileName = 'history_maintenance_';
                    if (response.nomor_polisi) {
                        pdfFileName += response.nomor_polisi;
                    } else if (response.customer) {
                        pdfFileName += response.customer;
                    } else {
                        pdfFileName += 'all_vehicles';
                    }
                    pdfMake.createPdf(docDefinition).download(pdfFileName + '.pdf');
                },
                error: function() {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error exporting data. Please try again.'
                    });
                }
            });
        }

$(document).ready(function() {
            // Select2 for Nomor Polisi
            $('.select2-nomor-polisi').select2({
                placeholder: 'Cari nomor polisi...',
                allowClear: true,
                ajax: {
                    url: "{{ route('maintenance.nomor_polisi.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on('change', function(e) {
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                alpineData.nomorPolisiFilter = $(this).val();
            });

            // Select2 for Customer
            $('.select2-customer').select2({
                placeholder: 'Cari kode/nama customer...',
                allowClear: true,
                ajax: {
                    url: "{{ route('maintenance.nama_customer.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on('change', function(e) {
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                alpineData.namaCustomerFilter = $(this).val();
            });

            // Split date pickers
            $('#start_date_transaksi').daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                showDropdowns: true,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'DD-MM-YYYY'
                }
            }, function(start) {
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                alpineData.startDate = start.format('YYYY-MM-DD');
                alpineData.startDateDisplay = start.format('DD-MM-YYYY');
            });

            $('#end_date_transaksi').daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                showDropdowns: true,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'DD-MM-YYYY'
                }
            }, function(start) {
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                alpineData.endDate = start.format('YYYY-MM-DD');
                alpineData.endDateDisplay = start.format('DD-MM-YYYY');
            });

            $('#start_date_transaksi, #end_date_transaksi').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY'));
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                if (ev.target.id === 'start_date_transaksi') {
                    alpineData.startDate = picker.startDate.format('YYYY-MM-DD');
                    alpineData.startDateDisplay = picker.startDate.format('DD-MM-YYYY');
                } else {
                    alpineData.endDate = picker.startDate.format('YYYY-MM-DD');
                    alpineData.endDateDisplay = picker.startDate.format('DD-MM-YYYY');
                }
            });

            $('#start_date_transaksi, #end_date_transaksi').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                let alpineData = Alpine.$data(document.querySelector('[x-data="transaksiPage()"]'));
                if (ev.target.id === 'start_date_transaksi') {
                    alpineData.startDate = '';
                    alpineData.startDateDisplay = '';
                } else {
                    alpineData.endDate = '';
                    alpineData.endDateDisplay = '';
                }
            });
        });
    </script>

    <script>
    // ── Column Resize Logic ──────────────────────────────────────────────
    (function() {
        function initColumnResize(tableId) {
            var table = document.getElementById(tableId);
            if (!table) return;

            var headers = table.querySelectorAll('thead th');
            headers.forEach(function(th) {
                // Remove any old handles first
                var oldHandle = th.querySelector('.col-resize-handle');
                if (oldHandle) oldHandle.remove();

                var handle = document.createElement('div');
                handle.classList.add('col-resize-handle');
                th.appendChild(handle);

                var startX, startWidth, thEl;

                handle.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    thEl = th;
                    startX = e.pageX;
                    startWidth = th.offsetWidth;
                    handle.classList.add('resizing');

                    var overlay = document.createElement('div');
                    overlay.id = 'resize-overlay';
                    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;cursor:col-resize;';
                    document.body.appendChild(overlay);

                    function onMouseMove(e) {
                        var newWidth = startWidth + (e.pageX - startX);
                        if (newWidth < 50) newWidth = 50;
                        thEl.style.width = newWidth + 'px';
                        thEl.style.minWidth = newWidth + 'px';
                    }

                    function onMouseUp() {
                        handle.classList.remove('resizing');
                        var ov = document.getElementById('resize-overlay');
                        if (ov) ov.remove();
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    }

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        }

        // Initialize after DataTable draws
        $(document).ready(function() {
            $('#transaksiTable').on('draw.dt', function() {
                initColumnResize('transaksiTable');
            });
            // Initial call
            setTimeout(function() { initColumnResize('transaksiTable'); }, 200);
        });
    })();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
@endsection
