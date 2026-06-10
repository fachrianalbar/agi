@extends('layouts.app')

@section('title', 'Fleet History')
@section('page-title', 'Fleet History')
@section('crud-assets', 'true')

@php
  $selectedCustomer = (string) ($filters['customer_id'] ?? '');
  $selectedDevice = (string) ($filters['device_name'] ?? '');
  $historyRows = is_array($histories) ? collect($histories) : collect();
  $playbackPoints = $historyRows
      ->pluck('playback')
      ->filter(fn ($point) => is_array($point) && is_numeric($point['latitude'] ?? null) && is_numeric($point['longitude'] ?? null))
      ->values();
  $selectedFleet = $fleets->firstWhere('device_name', $selectedDevice);
  $playbackPayload = [
      'vehicleName' => $selectedFleet?->vehicle_name ?: ($selectedDevice ?: 'Selected Fleet'),
      'deviceName' => $selectedDevice,
      'range' => trim(($filters['start_time'] ?? '').' - '.($filters['end_time'] ?? ''), ' -'),
      'points' => $playbackPoints->all(),
  ];
@endphp

@section('content')
<div
  class="page-section active js-crud-page"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
  data-error-message="{{ $errorMessage ?? session('error') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Fleet History</h1>
        <p class="page-header-subtitle">Review GPS history points by customer, fleet, and a maximum 48-hour date range.</p>
      </div>
    </div>

    <form method="POST" action="{{ route('fleet-histories.generate') }}" class="card form-card">
      @csrf
      <div class="card-header">
        <div>
          <h3 class="card-title">History Parameters</h3>
          <p class="card-subtitle">Select a customer first, then choose one fleet and a date range up to 48 hours.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="history_customer_id" class="form-label">Customer</label>
          <select
            name="customer_id"
            id="history_customer_id"
            class="form-select js-select2 @error('customer_id') form-input-error @enderror"
            data-placeholder="Select a customer..."
            required
          >
            <option value="">Select a customer...</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" @selected($selectedCustomer === $customer->id)>
                {{ $customer->name }} ({{ $customer->username }})
              </option>
            @endforeach
          </select>
          @error('customer_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="history_device_name" class="form-label">Fleet</label>
          <select
            name="device_name"
            id="history_device_name"
            class="form-select js-select2 @error('device_name') form-input-error @enderror"
            data-placeholder="Select a fleet..."
            data-dependent-parent="#history_customer_id"
            data-dependent-url="{{ route('fleet-histories.fleets') }}"
            data-dependent-param="customer_id"
            data-selected-value="{{ $selectedDevice }}"
            data-empty-label="Select a customer first..."
            required
          >
            <option value="">{{ $selectedCustomer ? 'Select a fleet...' : 'Select a customer first...' }}</option>
            @foreach($fleets as $fleet)
              <option value="{{ $fleet->device_name }}" @selected($selectedDevice === $fleet->device_name)>
                {{ $fleet->vehicle_name }} ({{ $fleet->device_name }})
              </option>
            @endforeach
          </select>
          @error('device_name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="history_start_time" class="form-label">Start Time</label>
          <input
            type="datetime-local"
            name="start_time"
            id="history_start_time"
            class="form-input @error('start_time') form-input-error @enderror"
            value="{{ $filters['start_time'] ?? '' }}"
            required
          >
          @error('start_time') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label for="history_end_time" class="form-label">End Time</label>
          <input
            type="datetime-local"
            name="end_time"
            id="history_end_time"
            class="form-input @error('end_time') form-input-error @enderror"
            value="{{ $filters['end_time'] ?? '' }}"
            required
          >
          <div class="form-hint">Maximum range is 48 hours.</div>
          @error('end_time') <div class="form-error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Generate History</button>
      </div>
    </form>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Fleet History Result</h3>
          <p class="card-subtitle">Data is loaded directly from Total Kilat GPS for the selected fleet.</p>
        </div>
        @if($playbackPoints->isNotEmpty())
          <button type="button" class="btn btn-secondary btn-sm" data-modal-target="fleetPlaybackModal" data-playback-open>
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.75c0-.58.63-.94 1.13-.64l10.5 6.25a.75.75 0 0 1 0 1.28l-10.5 6.25A.75.75 0 0 1 8 18.25V5.75Z"/></svg>
            Playback
          </button>
        @endif
      </div>

      @if(is_array($histories) && count($histories) > 0)
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Datetime</th>
                <th>GPS Location</th>
                <th>GPS Valid</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Speed</th>
                <th>Direction</th>
                <th>Engine</th>
                <th>Odometer</th>
                <th>Temperature</th>
                <th>Max Speed</th>
                <th>Overspeed</th>
                <th>Harsh Accel</th>
                <th>Harsh Braking</th>
                <th>Harsh Cornering</th>
              </tr>
            </thead>
            <tbody>
              @foreach($histories as $history)
                <tr>
                  <td class="table-cell-center">{{ $loop->iteration }}</td>
                  <td>{{ $history['datetime'] }}</td>
                  <td>{{ $history['gps_location'] }}</td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['gps_valid'] ? 'success' : 'danger'">
                      {{ $history['gps_valid'] ? 'Valid' : 'Invalid' }}
                    </x-badge>
                  </td>
                  <td>{{ $history['latitude'] }}</td>
                  <td>{{ $history['longitude'] }}</td>
                  <td>{{ $history['speed'] }}</td>
                  <td>{{ $history['direction'] }}</td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['engine'] ? 'success' : 'neutral'">
                      {{ $history['engine'] ? 'On' : 'Off' }}
                    </x-badge>
                  </td>
                  <td>{{ $history['odometer'] }}</td>
                  <td>{{ $history['temperature'] }}</td>
                  <td>{{ $history['max_speed'] }}</td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['overspeed'] ? 'danger' : 'neutral'">
                      {{ $history['overspeed'] ? 'Yes' : 'No' }}
                    </x-badge>
                  </td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['harsh_acceleration'] ? 'danger' : 'neutral'">
                      {{ $history['harsh_acceleration'] ? 'Yes' : 'No' }}
                    </x-badge>
                  </td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['harsh_braking'] ? 'danger' : 'neutral'">
                      {{ $history['harsh_braking'] ? 'Yes' : 'No' }}
                    </x-badge>
                  </td>
                  <td class="table-cell-center">
                    <x-badge :type="$history['harsh_cornering'] ? 'danger' : 'neutral'">
                      {{ $history['harsh_cornering'] ? 'Yes' : 'No' }}
                    </x-badge>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @elseif(is_array($histories))
        <div class="empty-state">
          <div class="empty-state-icon">
            <x-menu-icon name="activity" />
          </div>
          <div class="empty-state-title">No history data found</div>
          <p class="empty-state-desc">The GPS provider returned no history rows for this filter.</p>
        </div>
      @else
        <div class="empty-state">
          <div class="empty-state-icon">
            <x-menu-icon name="activity" />
          </div>
          <div class="empty-state-title">Set history parameters</div>
          <p class="empty-state-desc">Choose customer, fleet, start time, and end time to generate fleet history.</p>
        </div>
      @endif
    </div>
  </div>

  @if($playbackPoints->isNotEmpty())
    <script type="application/json" id="fleet-history-playback-data">@json($playbackPayload)</script>

    <x-modal id="fleetPlaybackModal" title="Fleet Playback" size="lg">
      <div class="playback-modal-shell">
        <div class="map-modal-hero playback-modal-hero">
          <div class="map-modal-title-block">
            <span class="map-modal-eyebrow">Route Playback</span>
            <h3 class="map-modal-vehicle-name" data-playback-vehicle>{{ $playbackPayload['vehicleName'] }}</h3>
            <p class="map-modal-address" data-playback-address>{{ $playbackPoints->first()['address'] ?? 'Address unavailable' }}</p>
          </div>
          <div class="map-modal-status-list">
            <span class="badge badge-info" data-playback-counter>1 / {{ $playbackPoints->count() }} Points</span>
            <span class="badge badge-neutral" data-playback-engine>Engine unknown</span>
          </div>
        </div>

        <div class="playback-modal-content">
          <div class="playback-map-card">
            <div id="fleetHistoryPlaybackMap" class="playback-map" data-playback-map></div>
          </div>

          <aside class="map-modal-details playback-detail-panel" aria-label="Fleet playback details">
            <div class="map-detail-card">
              <span class="map-detail-label">Current Time</span>
              <strong class="map-detail-value" data-playback-datetime>Unavailable</strong>
            </div>
            <div class="map-detail-card">
              <span class="map-detail-label">Speed</span>
              <strong class="map-detail-value" data-playback-speed>Unavailable</strong>
            </div>
            <div class="map-detail-card">
              <span class="map-detail-label">Odometer</span>
              <strong class="map-detail-value" data-playback-odometer>Unavailable</strong>
            </div>
            <div class="map-detail-card">
              <span class="map-detail-label">Coordinate</span>
              <strong class="map-detail-value map-detail-coordinate" data-playback-coordinate>Unavailable</strong>
            </div>

            <div class="playback-control-card">
              <div class="playback-progress-meta">
                <span>Timeline</span>
                <strong data-playback-range>{{ $playbackPayload['range'] ?: 'Selected range' }}</strong>
              </div>
              <input
                type="range"
                class="playback-progress"
                min="0"
                max="{{ max($playbackPoints->count() - 1, 0) }}"
                value="0"
                step="1"
                data-playback-progress
                aria-label="Playback timeline"
              >
              <div class="playback-controls">
                <button type="button" class="btn btn-primary btn-sm" data-playback-toggle>Play</button>
                <button type="button" class="btn btn-secondary btn-sm" data-playback-reset>Restart</button>
                <select class="form-select playback-speed-select" data-playback-speed-rate aria-label="Playback speed">
                  <option value="1">1x</option>
                  <option value="2">2x</option>
                  <option value="4">4x</option>
                  <option value="8">8x</option>
                  <option value="12">12x</option>
                  <option value="16">16x</option>
                  <option value="20">20x</option>
                </select>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </x-modal>
  @endif
</div>
@endsection

@push('scripts')
  @vite('resources/js/fleet-history-playback.js')
@endpush
