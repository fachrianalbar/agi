@extends('layouts.app')

@section('title', 'Create Customer')
@section('page-title', 'Create Customer')
@section('sweetalert-feedback', 'true')

@section('content')
<div class="page-section active">
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Create Customer</h1>
        <p class="page-header-subtitle">Register a new customer with login credentials</p>
      </div>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" class="card customer-form-card" id="customerForm">
      @csrf
      @include('pages.customers._form', ['customer' => null])
      <div class="form-actions">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Customer</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/customer.js')
@endpush
