@extends('layout')

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Dashboard</h4>
        </div>
        <div class="card-body">
            <h5>Welcome, {{ auth()->user()->name }}!</h5>
            <p>You are logged in as <strong>{{ ucfirst(auth()->user()->role) }}</strong>.</p>

            @if(auth()->user()->role === 'admin')
                <div class="alert alert-info">
                    <strong>Admin Access:</strong> You can manage all resources including students, teachers, courses, batches, enrollments, and payments.
                </div>
            @else
                <div class="alert alert-info">
                    <strong>Student Access:</strong> You can view courses and manage your payments.
                </div>
            @endif
        </div>
    </div>
@endsection
