<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email', 'recipient_name', 'subject', 'body_html', 'body_text',
        'template_name', 'related_entity_type', 'related_entity_id', 'status',
        'sent_at', 'error_message', 'sent_by_user_id'
    ];

    protected $casts = [
        'sent_at' => 'datetime'
    ];

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
