<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserLogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン中のユーザーが正常にログアウトできる()
    {
        // ユーザーを作成してログイン状態にする
        $user = User::factory()->create();

        $this->actingAs($user);

        // ログアウト処理を実行
        $response = $this->post('/logout');

        // 正常にログアウト → リダイレクト確認
        $response->assertRedirect('/login');

        // セッションからユーザーが消えていることを確認
        $this->assertGuest();
    }

    /** @test */
    public function 未ログイン状態でログアウトを実行するとログイン画面へリダイレクトされる()
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
