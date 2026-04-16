@extends('layout')
@section('content')
<div class="card">
  <div class="card-header">Payment Details</div>
  <div class="card-body">
        <h5 class="card-title">Enrollment No : {{ $payments->enrollment->enroll_no }}</h5>
        <p class="card-text">Paid Date : {{ $payments->paid_date }}</p>
        <p class="card-text">Amount : {{ $payments->amount }}</p>
        <p class="card-text">Receipt :
            @if($payments->receipt_path)
                <a href="{{ asset('storage/' . $payments->receipt_path) }}" target="_blank">View receipt</a>
            @else
                No receipt uploaded
            @endif
        </p>
  </div>
</div>
@endsection