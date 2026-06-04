<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'user_name' => '山田 太郎',
                'email' => 'yamada@example.com',
            ],
            [
                'user_name' => '佐藤 花子',
                'email' => 'sato@example.com',
            ],
            [
                'user_name' => '鈴木 一郎',
                'email' => 'suzuki@example.com',
            ],
            [
                'user_name' => '高橋 美咲',
                'email' => 'takahashi@example.com',
            ],
            [
                'user_name' => '田中 健',
                'email' => 'tanaka@example.com',
            ],
        ];

        foreach ($users as $user) {

            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'user_name' => $user['user_name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
