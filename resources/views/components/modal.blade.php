{{-- Modal Component --}}
@props([
    'id' => 'modal',
    'title' => 'Modal',
    'maxWidth' => '520px',
])

<div class="modal-overlay" id="{{ $id }}">
  <div class="modal" style="max-width: {{ $maxWidth }};">
    <div class="modal-header">
      <h2 class="modal-title">{{ $title }}</h2>
      <button class="modal-close close-modal-btn" onclick="document.getElementById('{{ $id }}').classList.remove('show'); document.body.style.overflow='';">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    {{ $slot }}
  </div>
</div>

{{-- Close modal on overlay click --}}
<script>
  document.getElementById('{{ $id }}')?.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
</script>
