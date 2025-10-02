<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者は当日の日次勤怠一覧を確認できる()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'status' => 'finished',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/attendances?date=' . Carbon::today()->toDateString())
            ->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /** @test */
    public function 管理者は前日翌日の勤怠情報を確認できる()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $yesterday = Carbon::yesterday()->toDateString();
        $tomorrow  = Carbon::tomorrow()->toDateString();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $yesterday,
            'start_time' => Carbon::yesterday()->setTime(10, 0),
            'end_time'   => Carbon::yesterday()->setTime(19, 0),
            'status' => 'finished',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $tomorrow,
            'start_time' => Carbon::tomorrow()->setTime(8, 30),
            'end_time'   => Carbon::tomorrow()->setTime(17, 30),
            'status' => 'finished',
        ]);

        // 前日
        $this->actingAs($admin, 'admin')
            ->get("/admin/attendances?date={$yesterday}")
            ->assertStatus(200)
            ->assertSee('10:00')
            ->assertSee('19:00');

        // 翌日
        $this->actingAs($admin, 'admin')
            ->get("/admin/attendances?date={$tomorrow}")
            ->assertStatus(200)
            ->assertSee('08:30')
            ->assertSee('17:30');
    }

    /** @test */
    public function 管理者は勤怠詳細画面に遷移できる()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'status' => 'finished',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/attendances/{$attendance->id}")
            ->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }
}
