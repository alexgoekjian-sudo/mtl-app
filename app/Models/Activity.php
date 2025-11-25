<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'related_entity_type',
        'related_entity_id',
        'activity_type',
        'subject',
        'body',
        'created_by_user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the owning entity (Lead, Student, or Enrollment)
     */
    public function relatedEntity()
    {
        return $this->morphTo('related_entity', 'related_entity_type', 'related_entity_id');
    }

    /**
     * Get the user who created this activity
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope to filter by entity type
     */
    public function scopeForEntityType($query, $type)
    {
        return $query->where('related_entity_type', $type);
    }

    /**
     * Scope to filter by activity type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
