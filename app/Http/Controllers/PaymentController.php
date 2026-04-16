<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Enrollment;
class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with('enrollment')->get();
        return view ('payments.index')->with('payments', $payments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $enrollments = Enrollment::pluck('enroll_no','id');
        return view('payments.create', compact('enrollments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'paid_date' => 'required|date',
            'amount' => 'required|numeric',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $input = $request->only(['enrollment_id', 'paid_date', 'amount']);

        if ($request->hasFile('receipt')) {
            $input['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        Payment::create($input);

        return redirect('payments')->with('flash_message', 'Payment Added!');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payments = Payment::with('enrollment')->findOrFail($id);
        return view('payments.show')->with('payments', $payments);
    }

    /**
     * Display an admin-only listing of payments with receipt verification.
     */
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $payments = Payment::find($id);
        $enrollments = Enrollment::pluck('enroll_no','id');
        return view('payments.edit', compact('payments','enrollments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payments = Payment::find($id);

        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'paid_date' => 'required|date',
            'amount' => 'required|numeric',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $input = $request->only(['enrollment_id', 'paid_date', 'amount']);

        if ($request->hasFile('receipt')) {
            $input['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $payments->update($input);
        return redirect('payments')->with('flash_message', 'Payment Updated!');  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Payment::destroy($id);
        return redirect('payments')->with('flash_message', 'Payment deleted!');
    }
}
