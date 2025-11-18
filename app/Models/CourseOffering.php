<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOffering extends Model
{
    protected $table = 'course_offerings';

    protected $fillable = [
        'attendance_id','round','course_key','course_full_name','level','program','type','start_date','end_date','hours_total',
        'schedule','price','teacher_hourly_rate','classroom_cost','admin_overhead','capacity','location','online','book_included','course_book'
    ];

    protected $casts = [
        'schedule' => 'array',
        'online' => 'boolean',
        'book_included' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
