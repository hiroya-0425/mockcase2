<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(),
            'submission_id' => $this->faker->uuid,
            'status' => 'pending',
            'requested_start_time' => null,
            'requested_end_time' => null,
            'remarks' => null,
            'reason' => '出勤時間を修正してください',
        ];
    }
}
