<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id', 'course_offering_id', 'status', 'enrolled_at', 'dropped_at',
        'mid_course_level', 'mid_course_notes', 'is_trial'
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'dropped_at' => 'datetime',
        'is_trial' => 'boolean'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id', 'student_id')
                    ->whereHas('session', function($q) {
                        $q->where('course_offering_id', $this->course_offering_id);
                    });
    }
}
