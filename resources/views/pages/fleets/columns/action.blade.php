<div class="table-actions">
  <button
    type="button"
    class="table-action-btn is-disabled"
    title="Position unavailable"
    aria-label="View last position on map"
    aria-disabled="true"
    disabled
    data-enrichment-ref="{{ $positionReference }}"
    data-enrichment-source-key="{{ $fleet->device_name }}"
    data-enrichment-map="map"
    data-map-modal-target="fleetMapModal"
    data-map-title="{{ $fleet->vehicle_name }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3"/></svg>
  </button>
  <button
    type="button"
    class="table-action-btn js-edit-fleet"
    title="Edit"
    aria-label="Edit {{ $fleet->vehicle_name }}"
    data-update-url="{{ route('fleets.update', $fleet) }}"
    data-vehicle-name="{{ $fleet->vehicle_name }}"
    data-device-name="{{ $fleet->device_name }}"
    data-customer-id="{{ $fleet->customer_id }}"
    data-fuel-sensor-installed-at="{{ $fleet->fuel_sensor_installed_at?->format('Y-m-d') }}"
    data-fuel-sensor-status="{{ $fleet->fuel_sensor_status }}"
    data-has-fuel-sensor="{{ $fleet->has_fuel_sensor ? 'true' : 'false' }}"
    data-is-active="{{ $fleet->is_active ? 'true' : 'false' }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 01-2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
  </button>
  <button
    type="button"
    class="table-action-btn table-action-danger js-delete-record"
    title="Delete"
    data-record-label="fleet"
    data-record-name="{{ $fleet->vehicle_name }}"
    data-url="{{ route('fleets.destroy', $fleet) }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
  </button>
</div>
