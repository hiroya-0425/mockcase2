<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤ボタンを押すとステータスが出勤中になる()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'start']) // 出勤打刻用ルート
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'working',
        ]);
    }

    /** @test */
    public function 出勤中に休憩ボタンを押すと休憩開始が記録される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'working',
            'work_date' => today(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_in'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_end' => null, // 休憩中
        ]);
    }

    /** @test */
    public function 出勤中に退勤すると退勤済になる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'working',
            'work_date' => today(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'end'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'finished',
        ]);
    }

    /** @test */
    public function 同じ日に2回出勤できないこと()
    {
        $user = User::factory()->create();

        // 1回目の出勤
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'start'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'working',
        ]);

        // 2回目の出勤
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'start'])
            ->assertSessionHasErrors(); // エラーになる想定

        // DBに2件目が作られていないこと
        $this->assertEquals(1, Attendance::where('user_id', $user->id)->count());
    }

    /** @test */
    public function 出勤中のときだけ休憩できる()
    {
        $user = User::factory()->create();

        // 出勤中の状態を作る
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'working',
            'work_date' => today(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => null,
        ]);

        // 出勤中なら休憩開始できる
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_in'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        // ステータスが「working」以外のときは休憩できない
        $attendance->update(['status' => 'finished']); // 退勤済にしておく

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_in'])
            ->assertRedirect('/attendance');

        // 新しい休憩が作られていないことを確認
        $this->assertDatabaseCount('break_times', 1);
    }

    /** @test */
    public function 休憩開始したら休憩終了できる()
    {
        $user = User::factory()->create();

        // 出勤中の勤怠を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'working',
            'work_date' => today(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => null,
        ]);

        // 休憩開始
        $attendance->breaks()->create([
            'break_start' => now(),
        ]);

        // 休憩終了アクション
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_out'])
            ->assertRedirect('/attendance');

        // DBに休憩終了時間が記録されているか
        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_end' => null, // 未終了は存在しないはず
        ]);
    }

    /** @test */
    public function 休憩中は退勤できない()
    {
        $user = User::factory()->create();

        // 出勤中の勤怠を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'working',
            'work_date' => today(),
            'start_time' => now()->setTime(9, 0),
            'end_time' => null,
        ]);

        // 休憩開始
        $attendance->breaks()->create([
            'break_start' => now(),
        ]);

        // 退勤ボタンを押す
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'end'])
            ->assertSessionHasErrors(['attendance']);

        // ✅ DBに「end_time」が入っていないことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'end_time' => null,
        ]);
    }

    /** @test */
    public function 退勤後は休憩できない()
    {
        $user = User::factory()->create();

        // 退勤済みの勤怠を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'finished',
            'end_time' => now(),
        ]);

        // 退勤後に休憩開始リクエスト
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_in'])
            ->assertRedirect('/attendance');

        // ✅ break_times に新規休憩レコードが作成されていないこと
        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /** @test */
    public function 退勤後にもう一度退勤できないこと()
    {
        $user = User::factory()->create();

        // 退勤済みの勤怠を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'finished',
            'start_time' => now()->subHours(8),
            'end_time' => now(),
        ]);

        // 退勤後にもう一度退勤アクションを実行
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'end'])
            ->assertRedirect('/attendance');

        // ✅ end_time が変わらず、status も finished のままであること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'finished',
            'end_time' => $attendance->end_time, // 更新されない
        ]);
    }
}
