<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'id' => 1,
            'name' => '代居大哉',
            'email' => 'hiroya-ydh@example.jp',
            'password' => Hash::make('hirohiroya'),
        ]);
    }
}
