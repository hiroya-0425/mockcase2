<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'), // デフォルト固定パスワード
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();   // 管理者
        $this->user  = User::factory()->create();    // 一般ユーザー
    }
}
