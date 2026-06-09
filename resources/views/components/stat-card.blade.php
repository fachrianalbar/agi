{{-- Statistics Card --}}
@props([
    'label' => 'Stat',
    'value' => '0',
    'icon' => 'blue',
    'change' => null,
    'changeType' => 'up',
    'id' => null,
])

<div class="stat-card">
  <div class="stat-card-icon {{ $icon }}">
    {{ $iconSlot ?? '' }}
  </div>
  <div>
    <div class="stat-card-value" @if($id) id="{{ $id }}" @endif>{{ $value }}</div>
    <div class="stat-card-label">{{ $label }}</div>
    @if($change)
      <div class="stat-card-change {{ $changeType }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          @if($changeType === 'up')
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
          @else
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>
          @endif
        </svg>
        {{ $change }}
      </div>
    @endif
  </div>
</div>
