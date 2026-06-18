@php
  $value = $position[$field] ?? ['text' => 'Unavailable', 'state' => 'error'];
  $hasSnapshot = ($value['state'] ?? null) !== 'error';
  $badge = $value['badge'] ?? null;
@endphp
<span
  @class([
    'enrichment-value',
    'enrichment-loading' => ! $hasSnapshot,
    'enrichment-address' => $field === 'address',
    'enrichment-error' => ($value['state'] ?? null) === 'error',
    'badge' => $badge !== null,
    'badge-'.$badge => $badge !== null,
  ])
  data-enrichment-ref="{{ $positionReference }}"
  data-enrichment-source-key="{{ $fleet->device_name }}"
  data-enrichment-field="{{ $field }}"
  data-enrichment-fallback='@json($value, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
  data-enrichment-has-snapshot="{{ $hasSnapshot ? 'true' : 'false' }}"
>
  {{ $value['text'] ?? 'Unavailable' }}
</span>
