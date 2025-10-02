<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminMonthlyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();   // 管理者は Admin モデル
        $this->staff = User::factory()->create();    // 一般ユーザー
    }

    /** @test */
    public function 管理者は指定ユーザーの当月勤怠一覧を確認できる()
    {
        Attendance::factory()->create([
            'user_id'   => $this->staff->id,
            'work_date' => Carbon::now()->startOfMonth()->addDays(1),
        ]);

        $this->actingAs($this->admin, 'admin')  // ← 第二引数 'admin' が超重要
            ->get(route('admin.staff.attendance', ['user' => $this->staff->id]))
            ->assertStatus(200)
            ->assertSee($this->staff->name)
            ->assertSee(Carbon::now()->format('Y年n月'));
    }

    /** @test */
    public function 管理者は前月翌月の勤怠一覧を確認できる()
    {
        $lastMonth = Carbon::now()->subMonth()->startOfMonth()->addDays(3); // 例: 9/4
        $nextMonth = Carbon::now()->addMonth()->startOfMonth()->addDays(5);

        Attendance::factory()->create(['user_id' => $this->staff->id, 'work_date' => $lastMonth]);
        Attendance::factory()->create(['user_id' => $this->staff->id, 'work_date' => $nextMonth]);

        // 前月
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.staff.attendance', [
                'user' => $this->staff->id,
                'month' => $lastMonth->format('Y-m')
            ]))
            ->assertStatus(200)
            ->assertSee($lastMonth->isoFormat('MM/DD(ddd)'));  // ← 修正ポイント

        // 翌月
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.staff.attendance', [
                'user' => $this->staff->id,
                'month' => $nextMonth->format('Y-m')
            ]))
            ->assertStatus(200)
            ->assertSee($nextMonth->isoFormat('MM/DD(ddd)'));
    }

    /** @test */
    public function 管理者は月次勤怠一覧から日次詳細に遷移できる()
    {
        $attendance = Attendance::factory()->create([
            'user_id'   => $this->staff->id,
            'work_date' => Carbon::now()->startOfMonth()->addDays(2),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.staff.attendance', ['user' => $this->staff->id]))
            ->assertSee(route('admin.attendance.show', $attendance->id));
    }
}
