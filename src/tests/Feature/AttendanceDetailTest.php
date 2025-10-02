<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面に自分の勤怠情報が表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => Carbon::create(2025, 10, 2),
            'start_time' => Carbon::create(2025, 10, 2, 9, 0),
            'end_time'   => Carbon::create(2025, 10, 2, 18, 0),
            'status'     => 'working',
        ]);

        $attendance->breaks()->create([
            'break_start' => Carbon::create(2025, 10, 2, 12, 0),
            'break_end'   => Carbon::create(2025, 10, 2, 13, 0),
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertStatus(200)
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('12:00')
            ->assertSee('13:00')
            ->assertSee($user->name)
            ->assertSeeText('2025年')
            ->assertSeeText('10月2日');
    }
}
