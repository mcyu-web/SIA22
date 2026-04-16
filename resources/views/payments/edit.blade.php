@extends('layout')
@section('content')
<div class="card">
  <div class="card-header">Edit Page</div>
  <div class="card-body">
      @if ($errors->any())
          <div class="alert alert-danger">
              <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
      @endif
      <form action="{{ url('payments/' . $payments->id) }}" method="post" enctype="multipart/form-data">
        {!! csrf_field() !!}
        @method('PATCH')

        <input type="hidden" name="id" id="id" value="{{ $payments->id }}" />

        <label>Enrollment No</label></br>
        <select name="enrollment_id" id="enrollment_id" class="form-control">
            @foreach($enrollments as $id => $enrollno)
                <option value="{{ $id }}" {{ $payments->enrollment_id == $id ? 'selected' : '' }}>{{ $enrollno }}</option>
            @endforeach
        </select>

        <label>Paid date</label></br>
        <input type="date" name="paid_date" id="paid_date" value="{{ $payments->paid_date }}" class="form-control"></br>

        <label>Amount</label></br>
        <input type="number" name="amount" id="amount" value="{{ $payments->amount }}" step="0.01" class="form-control"></br>

        <label>Receipt</label></br>
        <input type="file" name="receipt" id="receipt" class="form-control-file"></br>
        @if($payments->receipt_path)
            <p>Current receipt: <a href="{{ asset('storage/' . $payments->receipt_path) }}" target="_blank">View</a></p>
        @endif

        <input type="submit" value="Update" class="btn btn-success"></br>
    </form>
  
  </div>
</div>
@stop