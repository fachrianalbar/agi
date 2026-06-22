@extends('layouts.app')

@section('title', 'Fleet Transactions')
@section('page-title', 'Fleet Transactions')
@section('crud-assets', 'true')

@section('content')
    <div class="page-section active js-crud-page" id="fleetTransactionIndexPage" data-csrf-token="{{ csrf_token() }}"
        data-success-message="{{ session('success') }}" data-info-message="{{ session('info') }}"
        data-error-message="{{ session('error') }}">
        <div class="page-container">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Fleet Transactions</h1>
                    <p class="page-header-subtitle">Upload and manage daily vehicle fuel performance transactions</p>
                </div>
                <div class="page-header-actions">
                    <button type="button" class="btn btn-secondary btn-sm" data-modal-target="fleetTransactionImportModal">
                        <x-menu-icon name="receipt" />
                        Upload File
                    </button>
                    <a href="{{ route('fleet-transactions.create') }}" class="btn btn-primary btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        New Transaction
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Daily Transactions</h3>
                        <p class="card-subtitle">Rows are linked to fleet master data by vehicle name during import.</p>
                    </div>
                </div>

                <div class="data-table-container">
                    <table class="table js-data-table" id="fleetTransactionTable"
                        data-url="{{ route('fleet-transactions.data') }}" data-order='[[3,"desc"]]'
                        data-plural-label="transactions" data-search-placeholder="Search transactions...">
                        <thead>
                            <tr>
                                <th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
                                <th data-column="action" data-orderable="false" data-searchable="false"></th>
                                <th data-column="fleet_name" data-name="vehicle_name_snapshot">Fleet</th>
                                <th data-column="transaction_date">Date</th>
                                <th data-column="customer_name" data-orderable="false">Customer</th>
                                <th data-column="odometer_km" data-name="odometer_km">Odometer</th>
                                <th data-column="usage_l" data-name="usage_l">Usage</th>
                                <th data-column="cost_rp" data-name="cost_rp">Cost</th>
                                <th data-column="refuel_l" data-name="refuel_l">Refuel</th>
                                <th data-column="km_per_l" data-name="km_per_l" data-align="center">KM/L</th>
                                <th data-column="l_per_km" data-name="l_per_km" data-align="center">L/KM</th>
                                <th data-column="status" data-name="km_per_l" data-align="center" data-orderable="false">
                                    Status</th>
                                <th data-column="running_duration" data-name="running_duration_seconds">Running</th>
                                <th data-column="idle_duration" data-name="idle_duration_seconds">Idle</th>
                                <th data-column="stop_duration" data-name="stop_duration_seconds">Stop</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <x-modal id="fleetTransactionImportModal" title="Upload Transactions">
            <form method="POST" action="{{ route('fleet-transactions.import') }}" class="js-async-form"
                data-success-title="Import complete" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="transaction_file" class="form-label">Daily Performance File</label>
                        <input type="file" name="file" id="transaction_file" class="form-input"
                            accept=".xls,.html,.htm" required>
                        <div class="form-hint">
                            Upload the Daily Performance Analysis Report export. Vehicle names in the file must already
                            exist in Fleet.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary" data-loading-text="Importing...">
                        Import Transactions
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
@endsection
