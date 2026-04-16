@extends('layout')
@section('content')
<div class="card">
  <div class="card-header">Edit Page</div>
  <div class="card-body">
      
      <form action="{{ url('enrollments/' .$enrollments->id) }}" method="post">
        {!! csrf_field() !!}
        @method("PATCH")
        <input type="hidden" name="id" id="id" value="{{$enrollments->id}}" id="id" />
        

        <label>Enroll No</label></br>
        <input type="text" name="enroll_no" id="enroll_no" class="form-control" value="{{$enrollments->enroll_no}}"></br>

        <label>Batch</label></br>
        <input type="text" name="batch_id" id="batch_id" class="form-control" value="{{$enrollments->batch_id}}" readonly></br>

        <label>Student</label></br>
        <input type="text" name="student_id" id="student_id" class="form-control" value="{{$enrollments->student_id}}" readonly></br>

        <label>Join Date</label></br>
        <input type="date" name="join_date" id="join_date" class="form-control" value="{{$enrollments->join_date}}"></br>

        <label>Fee</label></br>
        <input type="number" name="fee" id="fee" step="0.01" class="form-control" value="{{$enrollments->fee}}"></br>

        <input type="submit" value="Update" class="btn btn-success"></br>

    </form>
  
  </div>
</div>
@stop