<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者は勤怠詳細画面を表示できる()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'status'     => 'finished',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $attendance->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細')
            ->assertSee($user->name);
    }

    /** @test */
    public function 管理者は勤怠情報を修正できる()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'remarks'    => '元の備考',
            'status'     => 'finished',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.attendance.update', $attendance->id), [
                'start_time' => '10:00',
                'end_time'   => '19:00',
                'remarks'    => '管理者による修正',
            ])
            ->assertRedirect(route('admin.attendance.show', $attendance->id));

        $this->assertDatabaseHas('attendances', [
            'id'         => $attendance->id,
            'start_time' => Carbon::today()->setTime(10, 0),
            'end_time'   => Carbon::today()->setTime(19, 0),
            'remarks'    => '管理者による修正',
        ]);
    }

    /** @test */
    public function 勤怠修正にバリデーションが効く()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
        ]);

        // 出勤 > 退勤 の不正ケース
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.attendance.update', $attendance->id), [
                'start_time' => '20:00',
                'end_time'   => '10:00',
                'remarks'    => '不正テスト',
            ])
            ->assertSessionHasErrors(['end_time']);
    }
}
