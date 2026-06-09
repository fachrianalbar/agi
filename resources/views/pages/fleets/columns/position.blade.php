<span
  @class([
    'enrichment-value',
    'enrichment-loading',
    'enrichment-address' => $field === 'address',
  ])
  data-enrichment-ref="{{ $positionReference }}"
  data-enrichment-source-key="{{ $fleet->device_name }}"
  data-enrichment-field="{{ $field }}"
>
  Loading...
</span>
