<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);

                // 出勤しない日もランダムで作る
                if (rand(0, 10) < 2) { // 2/10 の確率で欠勤
                    continue;
                }

                $start = Carbon::create($date->year, $date->month, $date->day, rand(9, 10), rand(0, 59));
                $end   = (clone $start)->addHours(8)->addMinutes(rand(0, 30));

                $attendance = Attendance::create([
                    'user_id'   => $user->id,
                    'work_date' => $date->toDateString(),
                    'start_time' => $start,
                    'end_time'  => $end,
                    'remarks'   => '自動生成データ',
                    'status'    => 'finished',
                ]);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start'   => (clone $start)->addHours(3),
                    'break_end'     => (clone $start)->addHours(4),
                ]);
            }
        }
    }
}
