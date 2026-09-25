@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden theme-transition mb-6">
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Report</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Operational Cost Report Filter</p>
    </div>

    <div class="p-6">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-6 mb-6">
            <form action="{{ route('maintenance.dashboard') }}" method="GET" id="filterForm">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Plate Number -->
                    <div>
                        <label for="nomor_polisi" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Nomor Polisi</label>
                        <select class="w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors shadow-sm select2-plate" id="nomor_polisi" name="nomor_polisi">
                            <option></option>
                            @if (request('nomor_polisi'))
                                <option value="{{ request('nomor_polisi') }}" selected>{{ request('nomor_polisi') }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Customer -->
                    <div>
                        <label for="nama_customer" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Nama Customer</label>
                        <select class="w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors shadow-sm select2-customer" id="nama_customer" name="nama_customer">
                            <option></option>
                            @if (request('nama_customer'))
                                @php
                                    $cust = \App\Models\customer::where('kode_customer', request('nama_customer'))->first();
                                @endphp
                                <option value="{{ request('nama_customer') }}" selected>
                                    {{ $cust ? $cust->kode_customer . ' - ' . $cust->nama_customer : request('nama_customer') }}
                                </option>
                            @endif
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date_display" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">Start Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <input type="text" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm" id="start_date_display" autocomplete="off" placeholder="DD-MM-YYYY" value="{{ request('start_date_transaksi') ? \Carbon\Carbon::parse(request('start_date_transaksi'))->format('d-m-Y') : '' }}">
                        </div>
                        <input type="hidden" id="start_date_transaksi" name="start_date_transaksi" value="{{ request('start_date_transaksi') }}">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date_display" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">End Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <input type="text" class="pl-9 w-full text-sm border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors h-10 shadow-sm" id="end_date_display" autocomplete="off" placeholder="DD-MM-YYYY" value="{{ request('end_date_transaksi') ? \Carbon\Carbon::parse(request('end_date_transaksi'))->format('d-m-Y') : '' }}">
                        </div>
                        <input type="hidden" id="end_date_transaksi" name="end_date_transaksi" value="{{ request('end_date_transaksi') }}">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="btnReset" 
                            class="h-[40px] px-6 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-xl font-bold uppercase tracking-wider text-sm transition-colors flex items-center gap-2 whitespace-nowrap shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset
                    </button>
                    <button type="submit" class="h-[40px] px-8 bg-indigo-600 hover:bg-indigo-700 text-white border border-indigo-600 rounded-xl font-bold uppercase tracking-wider text-sm transition-all flex items-center gap-2 whitespace-nowrap shadow-md hover:shadow-indigo-200 dark:hover:shadow-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Search
                    </button>
                </div>
            </form>
        </div>

        @if(isset($vehicleResults) && $vehicleResults->count() > 0)
            <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Daftar Mobil: <span class="text-indigo-600 dark:text-indigo-400">{{ request('nama_customer') ? (\App\Models\customer::where('kode_customer', request('nama_customer'))->value('nama_customer') ?? request('nama_customer')) : 'Semua Customer / Kendaraan' }}</span></h3>
                </div>
                <div class="p-6">
                    <style>
                        /* Frozen Table styles */
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
                        .frozen-table th.sticky-col::after,
                        .frozen-table td.sticky-col::after {
                            content: '';
                            position: absolute;
                            top: 0; right: -8px; bottom: 0;
                            width: 8px;
                            background: linear-gradient(to right, rgba(0,0,0,0.06), transparent);
                            pointer-events: none;
                        }
                        .frozen-table tbody td {
                            padding: 0.625rem 1rem;
                            font-size: 0.8125rem;
                            color: rgb(51 65 85);
                            border-bottom: 1px solid rgb(241 245 249);
                            vertical-align: middle;
                        }
                        .dark .frozen-table tbody td {
                            color: rgb(203 213 225);
                            border-bottom-color: rgb(30 41 59);
                        }
                    </style>
                    <div class="frozen-table-container">
                        <table id="vehicleListTable" class="frozen-table" style="min-width: 1400px;">
                            <thead>
                                <tr>
                                    <th class="sticky-col">Nomor Polisi</th>
                                    <th>Nomor Chassis</th>
                                    <th>Model</th>
                                    <th>Tahun</th>
                                    <th>Warna</th>
                                    <th>Nomor Mesin</th>
                                    <th>Beli</th>
                                    <th>Tanggal Start Sewa</th>
                                    <th>KM Start Sewa</th>
                                    <th>Supplier</th>
                                    <th class="text-rose-600 dark:text-rose-400">Last Tgl</th>
                                    <th class="text-rose-600 dark:text-rose-400">Last KM</th>
                                    <th class="text-rose-600 dark:text-rose-400">Total Cost</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($vehicleResults as $v)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="sticky-col font-bold text-slate-900 dark:text-white">{{ $v->nomor_polisi ?: '-' }}</td>
                                <td>{{ $v->nomor_chassis ?: '-' }}</td>
                                <td>{{ $v->model ?: '-' }}</td>
                                <td>{{ $v->tahun_pembuatan ?: '-' }}</td>
                                <td>{{ $v->warna ?: '-' }}</td>
                                <td>{{ $v->nomor_mesin ?: '-' }}</td>
                                <td>{{ $v->tanggal_pembelian ?: '-' }}</td>
                                <td>{{ $v->tanggal_start_sewa ? \Carbon\Carbon::parse($v->tanggal_start_sewa)->format('Y-m-d') : '-' }}</td>
                                <td>{{ is_numeric($v->km_start_sewa) ? number_format((float)$v->km_start_sewa, 0, ',', '.') : ($v->km_start_sewa ?: '-') }}</td>
                                <td>{{ $v->kode_sup ?: '-' }}</td>
                                <td class="text-rose-600 dark:text-rose-400 font-bold">{{ $v->last_job_date ?: '-' }}</td>
                                <td class="text-rose-600 dark:text-rose-400 font-bold">{{ is_numeric($v->last_job_km) ? number_format((float)$v->last_job_km, 0, ',', '.') : ($v->last_job_km ?: '-') }}</td>
                                <td class="text-rose-600 dark:text-rose-400 font-black">Rp {{ number_format((float)$v->total_cost, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('maintenance.vehicle.transactions', [
                                        'nama_customer' => request('nama_customer'),
                                        'nomor_polisi' => $v->nomor_polisi,
                                        'start_date_transaksi' => request('start_date_transaksi'),
                                        'end_date_transaksi' => request('end_date_transaksi')
                                    ]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div class="mt-6 flex justify-start">
                        <a href="{{ route('maintenance.vehicle.transactions', [
                                        'nama_customer' => request('nama_customer'),
                                        'start_date_transaksi' => request('start_date_transaksi'),
                                        'end_date_transaksi' => request('end_date_transaksi')
                                    ]) }}" class="px-6 py-3 bg-slate-600 text-white text-sm font-medium rounded-xl hover:bg-slate-700 transition-colors shadow-sm inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                            Show All Transactions for {{ request('nama_customer') ? (\App\Models\customer::where('kode_customer', request('nama_customer'))->value('nama_customer') ?? request('nama_customer')) : 'Semua Customer / Kendaraan' }}
                        </a>
                    </div>
                </div>
            </div>
        @elseif(isset($vehicleResults) && (request('nama_customer') || request('start_date_transaksi') || request('end_date_transaksi')))
            <div class="mt-8 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-6 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-amber-800 dark:text-amber-500 mb-1">No Vehicles Found</h3>
                <p class="text-amber-700 dark:text-amber-400/80">No vehicles or maintenance records found for the selected filters.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<!-- Export Dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

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
</style>

<script>
$(document).ready(function() {
    $('.select2-plate').select2({
        placeholder: 'Cari nomor polisi...',
        allowClear: true,
        ajax: {
            url: "{{ route('maintenance.nomor_polisi.search') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        }
    });

    $('.select2-customer').select2({
        placeholder: 'Cari kode/nama customer...',
        allowClear: true,
        ajax: {
            url: "{{ route('maintenance.nama_customer.search') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        }
    });

    // Start Date Picker
    $('#start_date_display').daterangepicker({
        singleDatePicker: true,
        autoUpdateInput: false,
        showDropdowns: true,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD-MM-YYYY'
        }
    });

    // End Date Picker
    $('#end_date_display').daterangepicker({
        singleDatePicker: true,
        autoUpdateInput: false,
        showDropdowns: true,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD-MM-YYYY'
        }
    });

    $('#start_date_display, #end_date_display').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY'));
        if (ev.target.id === 'start_date_display') {
            $('#start_date_transaksi').val(picker.startDate.format('YYYY-MM-DD'));
        } else {
            $('#end_date_transaksi').val(picker.startDate.format('YYYY-MM-DD'));
        }
    });

    $('#start_date_display, #end_date_display').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        if (ev.target.id === 'start_date_display') {
            $('#start_date_transaksi').val('');
        } else {
            $('#end_date_transaksi').val('');
        }
    });

    // Reset button
    $('#btnReset').on('click', function() {
        $('#nomor_polisi').val(null).trigger('change');
        $('#nama_customer').val(null).trigger('change');
        $('#start_date_display').val('');
        $('#start_date_transaksi').val('');
        $('#end_date_display').val('');
        $('#end_date_transaksi').val('');
        $('#filterForm').submit();
    });

    if ($('#vehicleListTable').length > 0) {
        $('#vehicleListTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
            "autoWidth": false,
            "scrollX": false,
            "language": {
                "search": "",
                "searchPlaceholder": "Search...",
                "lengthMenu": "_MENU_ entries"
            },
            "dom": '<"flex flex-wrap items-center justify-between gap-4 mb-4"B<"flex items-center gap-4"lf>>rt<"flex flex-wrap items-center justify-between gap-4 mt-4"ip>',
            "buttons": [
                {
                    extend: 'excelHtml5',
                    title: '',
                    filename: 'Vehicle maintenance record',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    },
                    text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Excel</span>',
                    className: 'btn-export'
                },
                {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    },
                    text: '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>PDF</span>',
                    className: 'btn-export'
                }
            ],
            drawCallback: function() {
                $('.dataTables_filter input').addClass('px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-indigo-500');
            }
        });
    }
});
</script>

<style>
/* Adjust DataTables export buttons to look like the screenshot */
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

/* Pagination styles */
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
</style>
@endsection
