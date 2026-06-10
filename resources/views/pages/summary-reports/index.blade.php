@extends('layouts.app')

@section('title', 'Summary Report')
@section('page-title', 'Summary Report')
@section('crud-assets', 'true')

@php
  $selectedCustomer = (string) ($filters['customer_id'] ?? '');
  $selectedDevice = (string) ($filters['device_name'] ?? '');
@endphp

@section('content')
<div
  class="page-section active js-crud-page"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
  data-error-message="{{ $errorMessage ?? session('error') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Summary Report</h1>
        <p class="page-header-subtitle">Generate daily GPS summaries by customer, fleet, and date range.</p>
      </div>
    </div>

    <form method="POST" action="{{ route('summary-reports.generate') }}" class="card form-card">
      @csrf
      <div class="card-header">
        <div>
          <h3 class="card-title">Report Parameters</h3>
          <p class="card-subtitle">Select a customer first, then choose one of the customer's fleets.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="summary_customer_id" class="form-label">Customer</label>
          <select
            name="customer_id"
            id="summary_customer_id"
            class="form-select js-select2 @error('customer_id') form-input-error @enderror"
            data-placeholder="Select a customer..."
            required
          >
            <option value="">Select a customer...</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" @selected($selectedCustomer === $customer->id)>
                {{ $customer->name }} ({{ $customer->username }})
              </option>
            @endforeach
          </select>
          @error('customer_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="summary_device_name" class="form-label">Fleet</label>
          <select
            name="device_name"
            id="summary_device_name"
            class="form-select js-select2 @error('device_name') form-input-error @enderror"
            data-placeholder="Select a fleet..."
            data-dependent-parent="#summary_customer_id"
            data-dependent-url="{{ route('summary-reports.fleets') }}"
            data-dependent-param="customer_id"
            data-selected-value="{{ $selectedDevice }}"
            data-empty-label="Select a customer first..."
            required
          >
            <option value="">{{ $selectedCustomer ? 'Select a fleet...' : 'Select a customer first...' }}</option>
            @foreach($fleets as $fleet)
              <option value="{{ $fleet->device_name }}" @selected($selectedDevice === $fleet->device_name)>
                {{ $fleet->vehicle_name }} ({{ $fleet->device_name }})
              </option>
            @endforeach
          </select>
          @error('device_name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="summary_start_time" class="form-label">Start Time</label>
          <input
            type="datetime-local"
            name="start_time"
            id="summary_start_time"
            class="form-input @error('start_time') form-input-error @enderror"
            value="{{ $filters['start_time'] ?? '' }}"
            required
          >
          @error('start_time') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="summary_end_time" class="form-label">End Time</label>
          <input
            type="datetime-local"
            name="end_time"
            id="summary_end_time"
            class="form-input @error('end_time') form-input-error @enderror"
            value="{{ $filters['end_time'] ?? '' }}"
            required
          >
          @error('end_time') <div class="form-error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Generate Report</button>
      </div>
    </form>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Daily Summary Result</h3>
          <p class="card-subtitle">Data is loaded directly from Total Kilat GPS for the selected fleet.</p>
        </div>
      </div>

      @if(is_array($reports) && count($reports) > 0)
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Date</th>
                <th>Vehicle Name</th>
                <th>Device Name</th>
                <th>Start Time</th>
                <th>Start Location</th>
                <th>End Time</th>
                <th>End Location</th>
                <th>Running Time</th>
                <th>Idle Time</th>
                <th>Travelling</th>
                <th>Parking</th>
                <th>Odometer</th>
                <th>Usage</th>
                <th>Max Speed</th>
                <th>Geofence Times</th>
              </tr>
            </thead>
            <tbody>
              @foreach($reports as $report)
                <tr>
                  <td class="table-cell-center">{{ $loop->iteration }}</td>
                  <td>{{ $report['date'] }}</td>
                  <td>{{ $report['vehicle_name'] }}</td>
                  <td>{{ $report['device_name'] }}</td>
                  <td>{{ $report['start_time'] }}</td>
                  <td>{{ $report['start_location'] }}</td>
                  <td>{{ $report['end_time'] }}</td>
                  <td>{{ $report['end_location'] }}</td>
                  <td>{{ $report['running_time'] }}</td>
                  <td>{{ $report['idle_time'] }}</td>
                  <td>{{ $report['travelling'] }}</td>
                  <td>{{ $report['parking'] }}</td>
                  <td>{{ $report['odometer'] }}</td>
                  <td>{{ $report['usage'] }}</td>
                  <td>{{ $report['max_speed'] }}</td>
                  <td>{{ $report['geofence_times'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @elseif(is_array($reports))
        <div class="empty-state">
          <div class="empty-state-icon">
            <x-menu-icon name="analytics" />
          </div>
          <div class="empty-state-title">No report data found</div>
          <p class="empty-state-desc">The GPS provider returned no daily summary rows for this filter.</p>
        </div>
      @else
        <div class="empty-state">
          <div class="empty-state-icon">
            <x-menu-icon name="analytics" />
          </div>
          <div class="empty-state-title">Set report parameters</div>
          <p class="empty-state-desc">Choose customer, fleet, start time, and end time to generate a summary report.</p>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
