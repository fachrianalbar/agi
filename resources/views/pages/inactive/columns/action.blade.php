<div class="table-actions">
  <button
    type="button"
    class="table-action-btn js-load-inactive-fleets"
    title="View inactive fleets"
    aria-label="View inactive fleets"
    data-url="{{ route('inactive.vehicles', $customer) }}"
    data-customer-name="{{ $customer->name }}"
    data-customer-username="{{ $customer->username }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="9"/>
      <line x1="5.6" y1="5.6" x2="18.4" y2="18.4"/>
    </svg>
  </button>
  <button
    type="button"
    class="table-action-btn js-create-inactive-snapshot"
    title="Snapshot"
    aria-label="Snapshot inactive fleets"
    data-url="{{ route('inactive.vehicles', $customer) }}"
    data-customer-name="{{ $customer->name }}"
    data-customer-username="{{ $customer->username }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 7h3l2-3h6l2 3h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/>
      <circle cx="12" cy="13" r="4"/>
    </svg>
  </button>
</div>
