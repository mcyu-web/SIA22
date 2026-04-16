<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use App\Models\Enrollment;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $enrolments = Enrollment::all();
        return view ('enrollments.index')->with('enrollments', $enrolments);
    }

  
    public function create()
    {

        $batches = Batch::pluck('name','id');
        $students = Student::pluck('name','id');
        return view('enrollments.create', compact('batches','students'));
    
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'enroll_no' => 'required|string|max:255',
            'batch_id' => 'required|exists:batches,id',
            'student_id' => 'required|exists:students,id',
            'join_date' => 'required|date',
            'fee' => 'required|numeric',
        ]);

        $input = $request->only(['enroll_no', 'batch_id', 'student_id', 'join_date', 'fee']);
        Enrollment::create($input);
        return redirect('enrollments')->with('flash_message', 'Enrolment Added!');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $enrolments = Enrollment::find($id);
        return view('enrollments.show')->with('enrollments', $enrolments);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $enrolments = Enrollment::find($id);
        return view('enrollments.edit')->with('enrollments', $enrolments);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $enrolments = Enrollment::find($id);
        $input = $request->all();
        $enrolments->update($input);
        return redirect('enrollments')->with('flash_message', 'Enrolment Updated!');  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Enrollment::destroy($id);
        return redirect('enrollments')->with('flash_message', 'Enrolment deleted!'); 
    }
}
