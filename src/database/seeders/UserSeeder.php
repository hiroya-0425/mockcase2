<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
