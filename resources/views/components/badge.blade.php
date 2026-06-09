{{-- Status Badge --}}
@props([
    'type' => 'neutral',
    'dot' => true,
])

@php
$classes = [
    'success' => 'badge-success',
    'warning' => 'badge-warning',
    'danger'  => 'badge-danger',
    'info'    => 'badge-info',
    'neutral' => 'badge-neutral',
];
@endphp

<span class="badge {{ $classes[$type] ?? $classes['neutral'] }}">
  @if($dot)
    <span class="badge-dot"></span>
  @endif
  {{ $slot }}
</span>
