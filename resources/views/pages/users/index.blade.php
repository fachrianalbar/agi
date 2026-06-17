@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('crud-assets', 'true')

@section('content')
    <div class="page-section active js-crud-page" id="userIndexPage" data-csrf-token="{{ csrf_token() }}"
        data-success-message="{{ session('success') }}" data-info-message="{{ session('info') }}">
        <div class="page-container">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Users</h1>
                    <p class="page-header-subtitle">Manage system users and their customer access</p>
                </div>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    New User
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">All Users</h3>
                        <p class="card-subtitle">Manage user accounts, roles, and customer assignments.</p>
                    </div>
                </div>

                <div class="data-table-container">
                    <table class="table js-data-table" id="userTable" data-url="{{ route('users.data') }}"
                        data-order='[[2,"asc"]]' data-plural-label="users">
                        <thead>
                            <tr>
                                <th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
                                <th data-column="action" data-orderable="false" data-searchable="false"></th>
                                <th data-column="name">Name</th>
                                <th data-column="username">Username</th>
                                <th data-column="email">Email</th>
                                <th data-column="customer_name" data-name="customers.name" data-orderable="false">Customer
                                </th>
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
