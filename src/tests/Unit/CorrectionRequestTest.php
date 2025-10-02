<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ち一覧に自分の申請が表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $myRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => '出勤時間を修正してください',
        ]);

        $otherUser = User::factory()->create();
        $otherAttendance = Attendance::factory()->create(['user_id' => $otherUser->id]);
        $otherRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $otherUser->id,
            'status' => 'pending',
            'reason' => '他人の申請',
        ]);

        $this->actingAs($user)
            ->get('/stamp_correction_request/list')
            ->assertStatus(200)
            ->assertSee('出勤時間を修正してください') // ✅ 自分のだけ表示
            ->assertDontSee('他人の申請'); // ✅ 他人は表示されない
    }

    /** @test */
    public function 勤怠修正申請が保存される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(8, 0), // ← 09:00 とずらす
            'end_time'   => Carbon::today()->setTime(17, 0), // ← 19:00 とずらす
            'status' => 'finished',
        ]);

        $this->actingAs($user)
            ->post(route('attendance.requestCorrection', $attendance), [
            'attendance_id' => $attendance->id,
                'reason' => '退勤時間を間違えました',
                'remarks' => 'テスト申請',
                'requested_start_time' => '09:00',
                'requested_end_time'   => '19:00',
            ])
            ->assertRedirect(route('attendance.show', ['attendance' => $attendance->id]));

        $this->assertDatabaseHas('correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => '',
        ]);
    }

    /** @test */
    public function 承認待ちの申請詳細では修正不可メッセージが表示される()
    {
        $user = \App\Models\User::factory()->create();
        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 'corrected', // ← 承認待ち状態に設定
        ]);

        // 修正申請レコードを1件作っておく（pendingのままでOK）
        \App\Models\CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'remarks'       => 'テスト申請',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.show', $attendance))
            ->assertStatus(200)
            ->assertSee('承認待ちのため修正できません。'); // ← correctedのときに表示される文言
    }

    /** @test */
    public function 承認済み一覧に自分の申請が表示される()
    {
        $user = \App\Models\User::factory()->create();
        $attendance = \App\Models\Attendance::factory()->create(['user_id' => $user->id]);

        $approvedRequest = \App\Models\CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'approved',
            'reason'        => '承認済みテスト',
        ]);

        $otherUser = \App\Models\User::factory()->create();
        $otherAttendance = \App\Models\Attendance::factory()->create(['user_id' => $otherUser->id]);
        \App\Models\CorrectionRequest::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'user_id'       => $otherUser->id,
            'status'        => 'approved',
            'reason'        => '他人の承認済み申請',
        ]);

        $this->actingAs($user)
            ->get(route('requests.index', ['tab' => 'approved']))
            ->assertStatus(200)
            ->assertSee('承認済みテスト')
            ->assertDontSee('他人の承認済み申請');
    }
}
