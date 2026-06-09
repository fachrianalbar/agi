@props([
    'editUrl',
    'deleteUrl',
    'recordLabel' => 'record',
    'recordName' => '',
])

<div class="table-actions">
  {{ $slot }}
  <a href="{{ $editUrl }}" class="table-action-btn" title="Edit">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
  </a>
  <button
    type="button"
    class="table-action-btn table-action-danger js-delete-record"
    title="Delete"
    data-record-label="{{ $recordLabel }}"
    data-record-name="{{ $recordName }}"
    data-url="{{ $deleteUrl }}"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
  </button>
</div>
