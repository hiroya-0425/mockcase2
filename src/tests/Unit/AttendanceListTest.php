<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠一覧に自分の勤怠データが表示される()
    {
        $user = User::factory()->create();
        $attendances = Attendance::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $weekMap = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'];

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendances[0]->work_date)->format('m/d') . '(' . $weekMap[Carbon::parse($attendances[0]->work_date)->format('D')] . ')')
            ->assertSee(Carbon::parse($attendances[1]->work_date)->format('m/d') . '(' . $weekMap[Carbon::parse($attendances[1]->work_date)->format('D')] . ')')
            ->assertSee(Carbon::parse($attendances[2]->work_date)->format('m/d') . '(' . $weekMap[Carbon::parse($attendances[2]->work_date)->format('D')] . ')');
    }

    /** @test */
    /** @test */
    public function 他人の勤怠データは表示されない()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // 自分の出勤
        $own = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        // 他人の出勤
        $otherAttendance = Attendance::factory()->create([
            'user_id' => $other->id,
            'work_date' => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertStatus(200)
            ->assertSee(
                $own->work_date->format('m/d') .
                    '(' . ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'][$own->work_date->format('D')] . ')'
            )
            ->assertDontSee("/attendance/detail/{$otherAttendance->id}"); // ← 他人の詳細リンクが出てないことを確認
    }

    /** @test */
    public function 勤怠一覧で指定した月のデータが表示される()
    {
        $user = User::factory()->create();

        $thisMonth = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->startOfMonth(),
        ]);

        $lastMonth = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->subMonth()->startOfMonth(),
        ]);

        $weekMap = ['Sun' => '日', 'Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土'];

        $this->actingAs($user)
            ->get('/attendance/list?month=' . now()->format('Y-m'))
            ->assertStatus(200)
            ->assertSee(Carbon::parse($thisMonth->work_date)->format('m/d') . '(' . $weekMap[Carbon::parse($thisMonth->work_date)->format('D')] . ')')
            ->assertDontSee(Carbon::parse($lastMonth->work_date)->format('m/d') . '(' . $weekMap[Carbon::parse($lastMonth->work_date)->format('D')] . ')');
    }

    /** @test */
    public function 勤怠一覧から詳細ページに遷移できる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2025, 10, 2),
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertStatus(200)
            ->assertSee($attendance->work_date->format('Y年'))   // 年だけ
            ->assertSee($attendance->work_date->format('n月j日')); // 月日だけ
    }
}
