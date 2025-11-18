<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'billing_contact_id', 'student_id', 'items', 'total', 
        'discount_percent', 'discount_reason', 'status', 'issued_date', 'due_date'
    ];

    protected $casts = [
        'items' => 'array',
        'issued_date' => 'date',
        'due_date' => 'date'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function totalPaid()
    {
        return $this->payments()
                    ->where('status', 'completed')
                    ->where('is_refund', false)
                    ->sum('amount');
    }

    public function totalRefunded()
    {
        return $this->payments()
                    ->where('status', 'completed')
                    ->where('is_refund', true)
                    ->sum('amount');
    }
}
