<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'course_offering_id',
        'date',
        'start_time',
        'end_time',
        'teacher_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
    
    /**
     * Append date_only attribute for API responses
     */
    protected $appends = ['date_only'];
    
    /**
     * Get the date in Y-m-d format
     */
    public function getDateOnlyAttribute()
    {
        if (!$this->date) return null;
        return is_string($this->date) ? explode('T', $this->date)[0] : $this->date;
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
