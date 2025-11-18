<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateExport extends Model
{
    protected $fillable = [
        'student_id', 'course_offering_id', 'attendance_percent', 'eligible',
        'exported_at', 'issued_at', 'certificate_url'
    ];

    protected $casts = [
        'eligible' => 'boolean',
        'exported_at' => 'datetime',
        'issued_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function scopeEligible($query)
    {
        return $query->where('eligible', true);
    }

    public function scopePending($query)
    {
        return $query->where('eligible', true)->whereNull('issued_at');
    }
}
