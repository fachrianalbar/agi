@extends('layouts.app')

@section('title', 'Create User')
@section('page-title', 'Create User')
@section('crud-assets', 'true')

@section('content')
    <div class="page-section active">
        <div class="page-container">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Create User</h1>
                    <p class="page-header-subtitle">Add a new system user with customer access</p>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>

            <form method="POST" action="{{ route('users.store') }}" class="card form-card" id="userForm">
                @csrf
                @include('pages.users._form', ['user' => null])
                <div class="form-actions">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
@endsection
