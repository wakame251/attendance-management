<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionRequestBreak extends Model
{
    protected $fillable = [
        'stamp_correction_request_id',
        'break_start',
        'break_end',
    ];

    public function stampCorrectionRequest(): BelongsTo
    {
        return $this->belongsTo(StampCorrectionRequest::class);
    }
}