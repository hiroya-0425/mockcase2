<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'work_date' => $this->faker->date(),
            'start_time' => now()->setTime(9, 0),
            'end_time'   => now()->setTime(18, 0),
            'remarks' => null,
            'status' => 'working',
            'work_date' => today(),
        ];
    }
}
