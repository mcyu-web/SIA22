<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $fillable = ['enrollment_id', 'paid_date', 'amount', 'receipt_path'];
    use HasFactory;


    public function enrollment()
    {
       return $this->belongsTo(Enrollment::class);
    }

}
