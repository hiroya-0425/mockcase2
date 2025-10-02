<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use App\Models\Admin;

class AdminRequestApproveTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create(); // ★ 管理者を Admin モデルから作る
        $this->user = User::factory()->create();   // 一般ユーザー
        $this->attendance = Attendance::factory()->create([
            'user_id'   => $this->user->id,
            'work_date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'remarks'    => '修正前の備考',
        ]);
    }

    /** @test */
    public function 管理者は修正申請の詳細画面を確認できる()
    {
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time'   => Carbon::today()->setTime(18, 0),
            'remarks' => '遅れて出社',
            'status'  => 'pending',
            'submission_id' => \Illuminate\Support\Str::uuid(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.requests.show', $request->submission_id))
            ->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee('遅れて出社')
            ->assertSee('勤怠詳細');
    }

    /** @test */
    public function 管理者は修正申請を承認できる()
    {
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time'   => Carbon::today()->setTime(18, 0),
            'remarks' => '遅れて出社',
            'status'  => 'pending',
            'submission_id' => \Illuminate\Support\Str::uuid(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.requests.approve', $request->submission_id))
            ->assertRedirect();

        // 承認済みに変わっている
        $this->assertDatabaseHas('correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // 勤怠テーブルが修正内容で更新されている
        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time'   => Carbon::today()->setTime(18, 0),
            'remarks'    => '遅れて出社',
        ]);
    }

    /** @test */
    public function 一般ユーザー側の一覧も承認済みに変わる()
    {
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time'   => Carbon::today()->setTime(18, 0),
            'remarks' => '遅れて出社',
            'status'  => 'pending',
            'submission_id' => \Illuminate\Support\Str::uuid(),
        ]);

        // 管理者が承認
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.requests.approve', $request->submission_id));

        // ユーザーがマイページで確認
        $this->actingAs($this->user)
            ->get(route('requests.index', ['tab' => 'approved']))
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee('遅れて出社');
    }
}
