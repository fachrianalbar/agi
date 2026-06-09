@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers')
@section('sweetalert-feedback', 'true')

@section('content')
<div
  class="page-section active"
  id="customerIndexPage"
  data-table-url="{{ route('customers.data') }}"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Customers</h1>
        <p class="page-header-subtitle">Manage registered customers and their credentials</p>
      </div>
      <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Customer
      </a>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">All Customers</h3>
          <p class="card-subtitle">Manage customer accounts, contact details, and status.</p>
        </div>
      </div>

      <div class="data-table-container">
        <table class="table" id="customerTable">
          <thead>
            <tr>
              <th></th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Location</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/customer.js')
@endpush
