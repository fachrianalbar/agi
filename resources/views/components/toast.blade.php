{{-- Toast Notification Container --}}
<div class="toast-container" id="toastContainer">

  {{-- Display validation errors as toasts --}}
  @if ($errors->any())
    @foreach ($errors->all() as $error)
      <div class="toast toast-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span>{{ $error }}</span>
      </div>
    @endforeach
  @endif

  {{-- Display flash success message --}}
  @if (session('success'))
    <div class="toast toast-success">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  {{-- Display flash info message --}}
  @if (session('info'))
    <div class="toast toast-info">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <span>{{ session('info') }}</span>
    </div>
  @endif
</div>

{{-- Auto-dismiss flash toasts --}}
@if (session('success') || session('info') || $errors->any())
<script>
  setTimeout(() => {
    document.querySelectorAll('.toast').forEach(t => {
      t.style.animation = 'fadeOut 0.3s ease-out forwards';
      setTimeout(() => t.remove(), 300);
    });
  }, 4000);
</script>
@endif
