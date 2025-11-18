<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'status', 'is_refund', 'method', 
        'external_reference', 'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_refund' => 'boolean'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
