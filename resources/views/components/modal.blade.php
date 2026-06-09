{{-- Modal Component --}}
@props([
    'id' => 'modal',
    'title' => 'Modal',
    'size' => null,
])

<div class="modal-overlay" id="{{ $id }}" data-modal aria-hidden="true">
  <div @class(['modal', "modal-{$size}" => $size]) role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title">
    <div class="modal-header">
      <h2 class="modal-title" id="{{ $id }}Title">{{ $title }}</h2>
      <button type="button" class="modal-close close-modal-btn" data-modal-close aria-label="Close modal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    {{ $slot }}
  </div>
</div>
