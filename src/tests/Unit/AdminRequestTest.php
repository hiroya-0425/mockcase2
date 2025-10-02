<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;

class AdminRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        // 管理者と一般ユーザー作成
        $this->admin = User::factory()->create();
        $this->user  = User::factory()->create();
    }

    /** @test */
    public function 管理者は承認待ち一覧を確認できる()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.requests.index', ['tab' => 'pending']))
            ->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee($this->user->name)
            ->assertSee($request->reason ?? $request->remarks ?? '-');
    }

    /** @test */
    public function 管理者は承認済み一覧を確認できる()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.requests.index', ['tab' => 'approved']))
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee($this->user->name)
            ->assertSee($request->reason ?? $request->remarks ?? '-');
    }

    /** @test */
    public function 管理者は申請の詳細画面に遷移できる()
    {
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id]);
        $request = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.requests.show', $request->submission_id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細')
            ->assertSee($this->user->name);
    }
}
