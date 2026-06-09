@extends('layouts.app')

@section('title', 'Fleets')
@section('page-title', 'Fleets')
@section('crud-assets', 'true')

@section('content')
<div
  class="page-section active js-crud-page"
  id="fleetIndexPage"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
  data-error-message="{{ session('error') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Fleets</h1>
        <p class="page-header-subtitle">Manage vehicle fleets and their tracking devices</p>
      </div>
      <div class="page-header-actions">
        <button type="button" class="btn btn-secondary btn-sm" data-modal-target="fleetSyncModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-5V2"/><path d="M4 17h5v5"/><path d="M5.5 9a7 7 0 0 1 11.8-3L20 7"/><path d="M4 17l2.7 1A7 7 0 0 0 18.5 15"/></svg>
          Synchronize
        </button>
        <a href="{{ route('fleets.create') }}" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Fleet
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">All Fleets</h3>
          <p class="card-subtitle">View and manage vehicle-device assignments.</p>
        </div>
      </div>

      <div class="data-table-container">
        <table
          class="table js-data-table"
          id="fleetTable"
          data-url="{{ route('fleets.data') }}"
          data-enrichment-url="{{ route('fleets.latest-positions') }}"
          data-order='[[2,"asc"]]'
          data-plural-label="fleets"
        >
          <thead>
            <tr>
              <th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
              <th data-column="action" data-orderable="false" data-searchable="false"></th>
              <th data-column="vehicle_name">Vehicle Name</th>
              <th data-column="device_name">Device Name</th>
              <th data-column="customer_name" data-orderable="false">Customer</th>
              <th data-column="mileage" data-orderable="false" data-searchable="false">Mileage</th>
              <th data-column="vehicle_status" data-orderable="false" data-searchable="false" data-align="center">Vehicle Status</th>
              <th data-column="engine" data-orderable="false" data-searchable="false" data-align="center">Engine</th>
              <th data-column="last_update" data-orderable="false" data-searchable="false">Last Update</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <x-modal id="fleetSyncModal" title="Synchronize Fleets">
    <form
      method="POST"
      action="{{ route('fleets.sync') }}"
      class="js-async-form"
      data-success-title="Synchronization complete"
    >
      @csrf
      <div class="modal-body">
        <div class="form-group">
          <label for="sync_customer_id" class="form-label">Customer</label>
          <select
            name="customer_id"
            id="sync_customer_id"
            class="form-select js-select2"
            data-placeholder="Select a customer..."
            required
          >
            <option value="">Select a customer...</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}">
                {{ $customer->name }} ({{ $customer->username }})
              </option>
            @endforeach
          </select>
          <div class="form-hint">
            Fleet data will be inserted or updated using the customer's GPS account.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary" data-loading-text="Synchronizing...">
          Synchronize
        </button>
      </div>
    </form>
  </x-modal>

  <x-modal id="fleetMapModal" title="Last Vehicle Position" size="lg">
    <div class="map-modal-header">
      <span data-map-vehicle-name></span>
    </div>
    <div class="map-modal-body">
      <iframe
        class="map-modal-frame"
        data-map-frame
        src="about:blank"
        title="Vehicle location map"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
      ></iframe>
    </div>
  </x-modal>
</div>
@endsection
