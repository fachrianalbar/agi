@extends('layouts.app')

@section('title', 'Edit Customer')
@section('page-title', 'Edit Customer')
@section('sweetalert-feedback', 'true')

@section('content')
<div class="page-section active">
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Edit Customer</h1>
        <p class="page-header-subtitle">Update customer details for {{ $customer->name }}</p>
      </div>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="card customer-form-card" id="customerForm">
      @csrf
      @method('PUT')
      @include('pages.customers._form')
      <div class="form-actions">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/customer.js')
@endpush
