@extends('layouts.app')

@section('title', 'Menu Management')
@section('page-title', 'Menu Management')
@section('sweetalert-feedback', 'true')

@section('content')
<div
  class="page-section active"
  id="menuIndexPage"
  data-table-url="{{ route('menus.data') }}"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Menu Management</h1>
        <p class="page-header-subtitle">Configure the sidebar navigation stored in the database</p>
      </div>
      <a href="{{ route('menus.create') }}" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Menu
      </a>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Sidebar Menus</h3>
          <p class="card-subtitle">Lower sort values are displayed first within each section.</p>
        </div>
      </div>

      <div class="menu-table-container">
        <table class="table" id="menuTable">
          <thead>
            <tr>
              <th></th>
              <th>Menu</th>
              <th>Section</th>
              <th>Destination</th>
              <th>Parent</th>
              <th>Order</th>
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
  @vite('resources/js/menu.js')
@endpush
