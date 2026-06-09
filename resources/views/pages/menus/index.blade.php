@extends('layouts.app')

@section('title', 'Menu Management')
@section('page-title', 'Menu Management')
@section('crud-assets', 'true')

@section('content')
<div
  class="page-section active js-crud-page"
  id="menuIndexPage"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Menu Management</h1>
        <p class="page-header-subtitle">Configure flat sidebar menus grouped by section</p>
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

      <div class="data-table-container">
        <table
          class="table js-data-table"
          id="menuTable"
          data-url="{{ route('menus.data') }}"
          data-order='[[3,"asc"],[5,"asc"],[2,"asc"]]'
          data-plural-label="menus"
        >
          <thead>
            <tr>
              <th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
              <th data-column="action" data-orderable="false" data-searchable="false"></th>
              <th data-column="menu" data-name="name">Menu</th>
              <th data-column="section">Section</th>
              <th data-column="destination" data-orderable="false">Destination</th>
              <th data-column="sort_order">Order</th>
              <th data-column="status" data-name="is_active" data-align="center">Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
