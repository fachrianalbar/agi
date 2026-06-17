@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('crud-assets', 'true')

@section('content')
    <div class="page-section active">
        <div class="page-container">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Edit User</h1>
                    <p class="page-header-subtitle">Update user details for {{ $user->name }}</p>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>

            <form method="POST" action="{{ route('users.update', $user) }}" class="card form-card" id="userForm">
                @csrf
                @method('PUT')
                @include('pages.users._form')
                <div class="form-actions">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
