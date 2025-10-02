<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正しい情報で管理者ログインできる()
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/attendances'); // ログイン後の遷移先
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /** @test */
    public function メールアドレス未入力はエラーになる()
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function パスワード未入力はエラーになる()
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function 存在しない管理者情報ではログインできない()
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'notfound@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest('admin');
    }
}
