<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'submission_id',
        'user_id',
        'attendance_id',
        'break_time_id',
        'requested_start_time',
        'requested_end_time',
        'reason',
        'remarks',
        'status',
        'admin_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class, 'break_time_id');
    }
}
