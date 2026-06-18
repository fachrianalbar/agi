@php
  $isActive = $fleet->fuel_sensor_status === 'active';
@endphp

<x-badge :type="$isActive ? 'success' : 'neutral'">
  {{ $isActive ? 'Active' : 'Inactive' }}
</x-badge>
