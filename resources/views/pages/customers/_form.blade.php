{{-- Customer Form Partial -- follows frontend.md §5 and menus pattern --}}
@php
  $isEdit = isset($customer) && $customer->exists;
  $isActive = old('is_active', $customer->is_active ?? true);
@endphp

<div class="form-grid">
  <div class="form-group">
    <label for="name" class="form-label">Full Name</label>
    <input type="text" name="name" id="name" class="form-input @error('name') form-input-error @enderror" value="{{ old('name', $customer->name ?? '') }}" maxlength="200" required>
    @error('name') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="username" class="form-label">Username</label>
    <input type="text" name="username" id="username" class="form-input @error('username') form-input-error @enderror" value="{{ old('username', $customer->username ?? '') }}" maxlength="100" required>
    @error('username') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="email" class="form-label">Email Address</label>
    <input type="email" name="email" id="email" class="form-input @error('email') form-input-error @enderror" value="{{ old('email', $customer->email ?? '') }}" maxlength="200" required>
    @error('email') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="phone" class="form-label">Phone</label>
    <input type="text" name="phone" id="phone" class="form-input @error('phone') form-input-error @enderror" value="{{ old('phone', $customer->phone ?? '') }}" maxlength="30">
    @error('phone') <div class="form-error">{{ $message }}</div> @enderror
  </div>
</div>

<div class="form-group">
  <label for="password" class="form-label">{{ $isEdit ? 'New Password (leave blank to keep current)' : 'Password' }}</label>
  <input type="text" name="password" id="password" class="form-input @error('password') form-input-error @enderror" minlength="8" maxlength="255" {{ $isEdit ? '' : 'required' }}>
  <div class="form-hint">Stored as plain text — not used for authentication.</div>
  @error('password') <div class="form-error">{{ $message }}</div> @enderror
</div>

<div class="form-group">
  <label for="address" class="form-label">Address</label>
  <input type="text" name="address" id="address" class="form-input @error('address') form-input-error @enderror" value="{{ old('address', $customer->address ?? '') }}" maxlength="500" placeholder="Street address">
  @error('address') <div class="form-error">{{ $message }}</div> @enderror
</div>

<div class="form-grid">
  <div class="form-group">
    <label for="city" class="form-label">City</label>
    <input type="text" name="city" id="city" class="form-input" value="{{ old('city', $customer->city ?? '') }}" maxlength="100">
  </div>

  <div class="form-group">
    <label for="state" class="form-label">State / Province</label>
    <input type="text" name="state" id="state" class="form-input" value="{{ old('state', $customer->state ?? '') }}" maxlength="100">
  </div>

  <div class="form-group">
    <label for="postal_code" class="form-label">Postal Code</label>
    <input type="text" name="postal_code" id="postal_code" class="form-input" value="{{ old('postal_code', $customer->postal_code ?? '') }}" maxlength="20">
  </div>

  <div class="form-group">
    <label for="country" class="form-label">Country</label>
    <input type="text" name="country" id="country" class="form-input" value="{{ old('country', $customer->country ?? '') }}" maxlength="100">
  </div>
</div>

<div class="form-group">
  <label for="notes" class="form-label">Notes</label>
  <textarea name="notes" id="notes" class="form-textarea @error('notes') form-input-error @enderror" maxlength="2000" placeholder="Internal notes about this customer...">{{ old('notes', $customer->notes ?? '') }}</textarea>
  @error('notes') <div class="form-error">{{ $message }}</div> @enderror
</div>

<div class="form-group form-group-switch">
  <label class="form-label">Account Status</label>
  <label class="check-control">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
    <span>
      <strong>Active</strong>
      <small>Allow this customer to access the system.</small>
    </span>
  </label>
</div>
