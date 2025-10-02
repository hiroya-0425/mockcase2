<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminLogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者が正常にログアウトできる()
    {
        // 管理者を作成してログイン
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $this->actingAs($admin, 'admin');

        // ログアウト処理を実行
        $response = $this->post('/admin/logout');

        // ログアウト後のリダイレクトを確認
        $response->assertRedirect('/admin/login');

        // セッションに管理者が残っていないことを確認
        $this->assertGuest('admin');
    }

    /** @test */
    public function 未ログイン状態でログアウトを実行するとログイン画面へリダイレクトされる()
    {
        // 未ログインでログアウトリクエスト
        $response = $this->post('/admin/logout');

        // ログイン画面へリダイレクト
        $response->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }
}
