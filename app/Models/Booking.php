<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'lead_id', 'student_id', 'booking_provider', 'external_booking_id',
        'booking_type', 'scheduled_at', 'assigned_teacher_id', 'assigned_level',
        'teacher_notes', 'pt_opt_result', 'status', 'webhook_payload'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'webhook_payload' => 'array'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedTeacher()
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }
}
