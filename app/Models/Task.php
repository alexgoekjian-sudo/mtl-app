<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title', 'body', 'assigned_to_user_id', 'related_entity_type', 'related_entity_id',
        'due_at', 'status', 'priority', 'completed_at', 'created_by_user_id'
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }
}
