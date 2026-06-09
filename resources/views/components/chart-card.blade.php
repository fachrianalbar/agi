{{-- Chart Card Container --}}
@props([
    'title' => 'Chart',
    'subtitle' => null,
    'canvasId' => 'chart',
    'tabs' => null,
    'small' => false,
])

<div class="chart-placeholder @if($small) chart-placeholder--small @endif">
  <div class="chart-placeholder-header">
    <div>
      <h3 class="card-title">{{ $title }}</h3>
      @if($subtitle)
        <p class="card-subtitle">{{ $subtitle }}</p>
      @endif
    </div>
    @if($tabs)
      <div class="tabs">
        @foreach($tabs as $i => $tab)
          <button class="tab-item @if($i === 0) active @endif">{{ $tab }}</button>
        @endforeach
      </div>
    @endif
  </div>
  <div class="chart-canvas-area @if($small) chart-canvas-area--small @endif">
    <canvas id="{{ $canvasId }}"></canvas>
  </div>
</div>
