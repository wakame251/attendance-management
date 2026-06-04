<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function stampCorrectionRequests(): HasMany
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function getBreakMinutesAttribute()
    {
        return $this->breaks->sum(function ($break) {

            if ($break->break_start && $break->break_end) {

                return \Carbon\Carbon::parse($break->break_end)
                    ->diffInMinutes(
                        \Carbon\Carbon::parse($break->break_start)
                    );
            }

            return 0;
        });
    }

    public function getWorkMinutesAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        return \Carbon\Carbon::parse($this->clock_out)
            ->diffInMinutes(
                \Carbon\Carbon::parse($this->clock_in)
            ) - $this->break_minutes;
    }
}