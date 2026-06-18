{{-- Fleet Form Partial -- follows frontend.md §5 --}}
@php
  $isEdit = isset($fleet) && $fleet->exists;
  $isActive = old('is_active', $fleet->is_active ?? true);
  $hasFuelSensor = old('has_fuel_sensor', $fleet->has_fuel_sensor ?? false);
  $fuelSensorStatus = old('fuel_sensor_status', $fleet->fuel_sensor_status ?? 'inactive');
  $selectedCustomer = old('customer_id', $fleet->customer_id ?? '');
  $fuelSensorInstalledAt = old(
      'fuel_sensor_installed_at',
      isset($fleet?->fuel_sensor_installed_at) ? $fleet->fuel_sensor_installed_at->format('Y-m-d') : ''
  );
@endphp

<div class="form-grid">
  <div class="form-group">
    <label for="vehicle_name" class="form-label">Vehicle Name</label>
    <input type="text" name="vehicle_name" id="vehicle_name" class="form-input @error('vehicle_name') form-input-error @enderror" value="{{ old('vehicle_name', $fleet->vehicle_name ?? '') }}" maxlength="200" required>
    @error('vehicle_name') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="device_name" class="form-label">Device Name</label>
    <input type="text" name="device_name" id="device_name" class="form-input @error('device_name') form-input-error @enderror" value="{{ old('device_name', $fleet->device_name ?? '') }}" maxlength="200" required>
    @error('device_name') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="customer_id" class="form-label">Customer</label>
    <select name="customer_id" id="customer_id" class="form-select js-select2 @error('customer_id') form-input-error @enderror" data-placeholder="Select a customer..." required>
      <option value="">Select a customer...</option>
      @foreach($customers as $customer)
        <option value="{{ $customer->id }}" @selected($selectedCustomer === $customer->id)>{{ $customer->name }} ({{ $customer->username }})</option>
      @endforeach
    </select>
    @error('customer_id') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="fuel_sensor_installed_at" class="form-label">Fuel Sensor Installation Date</label>
    <input type="date" name="fuel_sensor_installed_at" id="fuel_sensor_installed_at" class="form-input @error('fuel_sensor_installed_at') form-input-error @enderror" value="{{ $fuelSensorInstalledAt }}">
    @error('fuel_sensor_installed_at') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="fuel_sensor_status" class="form-label">Fuel Sensor Status</label>
    <select name="fuel_sensor_status" id="fuel_sensor_status" class="form-select @error('fuel_sensor_status') form-input-error @enderror">
      <option value="active" @selected($fuelSensorStatus === 'active')>Active</option>
      <option value="inactive" @selected($fuelSensorStatus === 'inactive')>Inactive</option>
    </select>
    <div class="form-hint">Fleets without an installed fuel sensor are saved as inactive.</div>
    @error('fuel_sensor_status') <div class="form-error">{{ $message }}</div> @enderror
  </div>
</div>

<div class="form-group form-group-switch">
  <label class="form-label">Fuel Sensor</label>
  <label class="check-control">
    <input type="hidden" name="has_fuel_sensor" value="0">
    <input type="checkbox" name="has_fuel_sensor" value="1" @checked($hasFuelSensor)>
    <span>
      <strong>Installed</strong>
      <small>This fleet has a fuel sensor installed.</small>
    </span>
  </label>
</div>

<div class="form-group form-group-switch">
  <label class="form-label">Status</label>
  <label class="check-control">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
    <span>
      <strong>Active</strong>
      <small>This fleet is currently operational.</small>
    </span>
  </label>
</div>
