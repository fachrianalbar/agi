<x-crud-actions
  :edit-url="route('fleets.edit', $fleet)"
  :delete-url="route('fleets.destroy', $fleet)"
  record-label="fleet"
  :record-name="$fleet->vehicle_name"
>
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
</x-crud-actions>
