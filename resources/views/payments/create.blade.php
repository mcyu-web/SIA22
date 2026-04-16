@extends('layout')
@section('content')
 
<div class="card">
  <div class="card-header">Payments</div>

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
      <form action="{{ url('payments') }}" method="post" enctype="multipart/form-data">
        {!! csrf_field() !!}
        
      <label>Enrollment No</label></br>
        <select name="enrollment_id" id="enrollment_id" class="form-control">
            @foreach($enrollments as $id => $enroll_no)
            <option value="{{ $id }}" {{ old('enrollment_id') == $id ? 'selected' : '' }}>{{ $enroll_no }}</option>
            @endforeach
      </select>
       
      <label>Paid Date</label></br>
        <input type="date" name="paid_date" id="paid_date" value="{{ old('paid_date') }}" class="form-control"></br>
      
      <label>Amount</label></br>
      <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" class="form-control"></br>

      <label>Receipt</label></br>
      <input type="file" name="receipt" id="receipt" class="form-control-file"></br>

      <input type="submit" value="Save" class="btn btn-success"></br>
    </form>
   
  </div>
</div>
 
@stop