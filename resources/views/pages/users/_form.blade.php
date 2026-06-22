@php
    $isEdit = isset($user) && $user->exists;
    $isActive = old('is_active', $user->is_active ?? true);
    $selectedCustomer = old('customer_id', $user->customer_id ?? '');
@endphp

<div class="form-grid">
    <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-input @error('name') form-input-error @enderror"
            value="{{ old('name', $user->name ?? '') }}" maxlength="200" required>
        @error('name')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email"
            class="form-input @error('email') form-input-error @enderror" value="{{ old('email', $user->email ?? '') }}"
            maxlength="200" required>
        @error('email')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" id="username"
            class="form-input @error('username') form-input-error @enderror"
            value="{{ old('username', $user->username ?? '') }}" maxlength="100" required>
        <div class="form-hint">Can be used to log in instead of email.</div>
        @error('username')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group">
    <label for="password"
        class="form-label">{{ $isEdit ? 'New Password (leave blank to keep current)' : 'Password' }}</label>
    <input type="password" name="password" id="password"
        class="form-input @error('password') form-input-error @enderror" {{ $isEdit ? '' : 'required' }} minlength="6">
    <div class="form-hint">Minimum 6 characters.</div>
    @error('password')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="customer_id" class="form-label">Customer Access</label>
    <select name="customer_id" id="customer_id" class="form-select js-select2" data-placeholder="All Customers"
        data-allow-clear="true">
        <option value=""></option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected($selectedCustomer == $customer->id)>{{ $customer->name }}</option>
        @endforeach
    </select>
    <div class="form-hint">Leave empty to grant access to all customers. Select a specific customer to restrict access.
    </div>
    @error('customer_id')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group form-group-switch">
    <label class="form-label">Account Status</label>
    <label class="check-control">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
        <span>
            <strong>Active</strong>
            <small>Allow this user to log in and access the system.</small>
        </span>
    </label>
</div>
