@extends('layout')
@section('content')
<div class="card">
    <div class="card-header">Admin Payments</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Enrollment No</th>
                        <th>Paid Date</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                        <th>Student</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $payment->enrollment->enroll_no }}</td>
                        <td>{{ $payment->paid_date }}</td>
                        <td>{{ $payment->amount }}</td>
                        <td>
                            @if($payment->receipt_path)
                                <a href="{{ asset('storage/' . $payment->receipt_path) }}" target="_blank">View</a>
                            @else
                                No receipt
                            @endif
                        </td>
                        <td>{{ optional($payment->enrollment->student)->name ?? 'Unknown' }}</td>
                        <td>
                            <a href="{{ url('/payments/' . $payment->id) }}" class="btn btn-info btn-sm">View</a>
                            <a href="{{ url('/payments/' . $payment->id . '/edit') }}" class="btn btn-primary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No payments found yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection