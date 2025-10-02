<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者はスタッフ一覧を確認できる()
    {
        $admin = Admin::factory()->create();
        $users = User::factory()->count(3)->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.index'))
            ->assertStatus(200);

        // 全ユーザーの氏名・メールアドレスが表示されること
        foreach ($users as $user) {
            $this->get(route('admin.staff.index'))
                ->assertSee($user->name)
                ->assertSee($user->email);
        }
    }

    /** @test */
    public function 管理者はスタッフ詳細から月次勤怠一覧に遷移できる()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendance', $user->id))
            ->assertStatus(200)
            ->assertSee($user->name); // 月次勤怠画面にユーザー名が表示される想定
    }
}
