<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'provider', 'event_type', 'external_id', 'payload', 'status',
        'retry_count', 'last_retry_at', 'error_message', 'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'last_retry_at' => 'datetime',
        'processed_at' => 'datetime'
    ];

    public function markProcessed()
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now()
        ]);
    }

    public function markFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now()
        ]);
    }
}
